<?php

namespace arnoson\KirbyStats;

use DatePeriod;
use DateTimeImmutable;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Kirby\Toolkit\Collection;
use Kirby\Database\Database;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class KirbyStats {
  protected static $mockOptions = [];

  public static function mockOptions(array $options) {
    static::$mockOptions = $options;
  }

  public static function option(string $key, $default = null) {
    return A::get(static::$mockOptions, $key) ??
      option("arnoson.kirby-stats.$key", $default);
  }

  public static function interval() {
    return Interval::fromName(static::option('interval'));
  }

  protected static ?Database $db = null;

  protected static function db() {
    if (static::$db) {
      return static::$db;
    }

    $database = static::option('database');

    if (!F::exists($database)) {
      $dir = F::dirname($database);
      if (!is_dir($dir)) {
        Dir::make($dir);
      }
    }

    static::$db = new Database([
      'type' => 'sqlite',
      'database' => $database,
    ]);

    static::$db->createTable('traffic', [
      'time' => ['type' => 'int', 'key' => 'primary'],
      'uuid' => ['type' => 'text', 'key' => 'primary'],
      'interval' => ['type' => 'int', 'key' => 'primary'],
      'views' => ['type' => 'int'],
      'visits' => ['type' => 'int'],
      'visitors' => ['type' => 'int'],
    ]);

    static::$db->createTable('meta', [
      'time' => ['type' => 'int', 'key' => 'primary'],
      'uuid' => ['type' => 'text', 'key' => 'primary'],
      'interval' => ['type' => 'int', 'key' => 'primary'],
      'category' => ['type' => 'int', 'key' => 'primary'],
      'key' => ['type' => 'text', 'key' => 'primary'],
      'value' => ['type' => 'int'],
    ]);

    return static::$db;
  }

  public static function processRequest(
    string $uuid,
    DateTimeImmutable $date = new DateTimeImmutable()
  ) {
    if (kirby()->user() || !static::option('enabled')) {
      return;
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $device = new DeviceDetector($userAgent);
    $device->discardBotInformation();
    $device->parse();

    $isBot = $device->isBot() || (new CrawlerDetect())->isCrawler($userAgent);
    if ($isBot) {
      return;
    }

    $isSite = $uuid === 'site://';
    $isPage = Str::startsWith($uuid, 'page://');
    $isVisit = static::handleVisitTracking();

    if ($isSite) {
      // The site is special: it acts as an aggregator for all page traffic.
      // For each page visit, we also process the site. This means the site's
      // views represent the total number of page views.
      $isView = true;
      // If this is also a visit, it means the user visited the website for the
      // first time and therefore counts as a unique visitor.
      $isVisitor = $isVisit;
      static::increaseTraffic($uuid, $date, isView: $isView, isVisitor: $isVisitor); // prettier-ignore
    } elseif ($isPage) {
      // Each page tracks its own views and visits.
      static::increaseTraffic($uuid, $date, isView: true, isVisit: $isVisit);
      if ($isVisit) {
        // Also increase the total page visits.
        static::increaseTraffic('site://', $date, isVisit: true);
      }
    }

    // Collecting meta data only makes sense for visits.
    if ($isVisit) {
      $os = OperatingSystem::getOsFamily($device->getOs('name'));
      $os = $os === 'GNU/Linux' ? 'Linux' : $os;
      $browser = Browser::getBrowserFamily($device->getClient('name'));
      static::increaseMeta($uuid, 'browser', $browser, $date);
      static::increaseMeta($uuid, 'os', $os, $date);
    }
  }

  public static function handleVisitTracking(): bool {
    // Use the Last-Modified/If-Modified-Since headers as a way to detect unique
    // daily visits.
    // See https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies
    // Thanks Cabin for sharing this! :)
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
    $clientTimestamp = $ifModifiedSince ? strtotime($ifModifiedSince) : null;
    $midnight = strtotime('today');
    $isVisit = false;

    if ($clientTimestamp && $clientTimestamp >= $midnight) {
      // Subsequent visit (view): increment by 1 second.
      $newTimestamp = $clientTimestamp + 1;
    } else {
      // First visit today.
      $newTimestamp = $midnight;
      $isVisit = true;
    }

    $response = kirby()->response();
    $secondsUntilMidnight = strtotime('tomorrow') - time();
    $response->header(
      'Cache-Control',
      "public, max-age=$secondsUntilMidnight, no-cache"
    );
    $response->header(
      'Last-Modified',
      gmdate('D, d M Y H:i:s', $newTimestamp) . ' GMT'
    );

    return $isVisit;
  }

  public static function increaseTraffic(
    string $uuid,
    DateTimeImmutable $date,
    bool $isView = false,
    bool $isVisit = false,
    bool $isVisitor = false
  ) {
    $interval = Interval::fromName(static::option('interval', 'hour'));
    $time = $interval->startOf($date)->getTimestamp();

    $views = $isView ? 1 : 0;
    $visits = $isVisit ? 1 : 0;
    $visitors = $isVisitor ? 1 : 0;
    $update = "SET views = views + $views, visits = visits + $visits, visitors = visitors + $visitors";

    $query = "INSERT INTO traffic (time, uuid, interval, views, visits, visitors)
      VALUES (?, ?, ?, ?, ?, ?)
      ON CONFLICT(time, uuid, interval)
      DO UPDATE $update";

    $bindings = [$time, $uuid, $interval->value, $views, $visits, $visitors];
    static::db()->execute($query, $bindings);
  }

  public static function increaseMeta(
    string $uuid,
    string $category,
    string $key,
    DateTimeImmutable $date
  ) {
    // When storing traffic data hourly, for example, the most detailed view
    // that we show in the panel is daily, since the chart displays data per
    // hour during the day. Meta data (like browser or OS) is only shown as a
    // daily aggregate, not per hour. Therefore, it's sufficient to store meta
    // data at the next larger interval (e.g., daily if traffic is hourly),
    // rather than matching the traffic data's resolution.
    $trafficInterval = Interval::fromName(static::option('interval', 'day'));
    $interval = $trafficInterval->nextLargerInterval();
    $time = $interval->startOf($date)->getTimestamp();

    $query = 'INSERT INTO meta (time, uuid, interval, category, key, value)
      VALUES (?, ?, ?, ?, ?, 1)
      ON CONFLICT(time, uuid, interval, category, key)
      DO UPDATE SET value = value + 1;';

    static::db()->execute($query, [
      $time,
      $uuid,
      $interval->value,
      $category,
      $key,
    ]);
  }

  public static function data(
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    Interval $interval = Interval::HOUR,
    string $uuid = 'site://'
  ) {
    $from = $interval->startOf($from);
    $to = $interval->startOf($to);
    $fromTime = $from->getTimestamp();
    $toTime = $to->getTimestamp();

    // Meta
    $query = "SELECT uuid, category, key, SUM(value) AS total
      FROM meta
      WHERE time BETWEEN ? AND ? AND uuid = ?
      GROUP BY category, key";

    /** @var Collection */
    $rows = static::db()->query($query, [$fromTime, $toTime, $uuid]) ?: [];
    $meta = ['browser' => [], 'os' => []];
    foreach ($rows as $row) {
      $uuid = $row->uuid();
      $category = $row->category();
      $key = $row->key();
      $meta[$category] ??= [];
      $meta[$category][$key] = intval($row->total());
    }

    // Traffic
    /** @var Collection */
    $query = "SELECT * FROM traffic
      WHERE time BETWEEN ? AND ? AND uuid = ?
      ORDER BY time ASC";
    /** @var Collection */
    $rows = static::db()->query($query, [$fromTime, $toTime, $uuid]);
    $traffic = static::normalizeTraffic($rows, $interval);
    $traffic = static::fillMissingTraffic($traffic, $interval, $from, $to);

    // Total traffic for page(s)
    if ($uuid === 'site://') {
      // For site-wide stats, sum all page traffic.
      $query = "SELECT uuid, SUM(views) AS total_views, SUM(visits) AS total_visits, SUM(visitors) AS total_visitors from traffic
        WHERE time BETWEEN ? AND ?
        GROUP BY uuid";
      /** @var Collection */
      $rows = static::db()->query($query, [$fromTime, $toTime]) ?: [];
    } else {
      // For a specific uuid.
      $query = "SELECT uuid, SUM(views) AS total_views, SUM(visits) AS total_visits, SUM(visitors) AS total_visitors from traffic
        WHERE time BETWEEN ? AND ? AND uuid = ?
        GROUP BY uuid";
      /** @var Collection */
      $rows = static::db()->query($query, [$fromTime, $toTime, $uuid]) ?: [];
    }
    $totalTraffic = [];
    foreach ($rows as $row) {
      $uuid = $row->uuid();
      $page = page($uuid);

      if ($page) {
        $parts = [$page->title()->value()];
        while ($page = $page->parent()) {
          $parts[] = $page->title()->value();
        }
        $name = implode(' / ', array_reverse($parts));
      } elseif ($uuid === 'site://') {
        $name = site()->title()->value();
      }

      $totalTraffic[$uuid] = [
        'uuid' => $uuid,
        'id' => page($uuid)?->id() ?? $uuid,
        'name' => $name ?? $uuid,
        'views' => intval($row->total_views()),
        'visits' => intval($row->total_visits()),
        'visitors' => intval($row->total_visitors()),
      ];
    }

    return [
      'meta' => $meta,
      'traffic' => $traffic,
      'totalTraffic' => $totalTraffic,
    ];
  }

  protected static function normalizeTraffic(
    Collection|false $rows,
    Interval $interval
  ): array {
    if (!$rows) {
      return [];
    }
    $traffic = [];
    foreach ($rows as $row) {
      $time = intval($row->time());
      $views = intval($row->views());
      $visits = intval($row->visits());
      $visitors = intval($row->visitors());
      $rowInterval = Interval::from(intval($row->interval()));

      // Since the intervals can be changed via config, it could happen that
      // the values are stored in a different interval and we have to normalize
      // them accordingly.
      if ($rowInterval === $interval) {
        // Intervals are matching.
        $label = $interval->label($time);
        $traffic[$time] ??= ['views' => 0, 'visits' => 0, 'visitors' => 0, 'label' => $label]; // prettier-ignore
        $traffic[$time]['views'] += $views;
        $traffic[$time]['visits'] += $visits;
        $traffic[$time]['visitors'] += $visitors;
      } elseif ($rowInterval->value < $interval->value) {
        // Stored interval is smaller so we add the value to the corresponding
        // larger interval.
        $time = $interval->startOf($time)->getTimestamp();
        $label = $interval->label($time);
        $traffic[$time] ??= ['views' => 0, 'visits' => 0, 'visitors' => 0, 'label' => $label]; // prettier-ignore
        $traffic[$time]['views'] += $views;
        $traffic[$time]['visits'] += $visits;
        $traffic[$time]['visitors'] += $visitors;
      } elseif ($rowInterval->value > $interval->value) {
        // Stored interval is larger so we have to split the value up and create
        // a number of synthetic smaller intervals.
        $start = $rowInterval->startOf($time);
        $end = $rowInterval->endOf($time);
        $periodInterval = $interval->interval();
        $period = new DatePeriod($start, $periodInterval, $end);
        $periodsCount = iterator_count($period);
        $viewsPerPeriod = (int) round($views / $periodsCount);
        $visitsPerPeriod = (int) round($visits / $periodsCount);
        $visitorsPerPeriod = (int) round($visitors / $periodsCount);
        foreach ($period as $time) {
          $time = $time->getTimestamp();
          $label = $interval->label($time);
          $traffic[$time] ??= ['views' => 0, 'visits' => 0, 'visitors' => 0, 'label' => $label]; // prettier-ignore
          $traffic[$time]['views'] += $viewsPerPeriod;
          $traffic[$time]['visits'] += $visitsPerPeriod;
          $traffic[$time]['visitors'] += $visitorsPerPeriod;
        }
      }
    }
    return $traffic;
  }

  protected static function fillMissingTraffic(
    array $traffic,
    Interval $interval,
    DateTimeImmutable $from,
    DateTimeImmutable $to
  ): array {
    $filledTraffic = [];

    $now = new DateTimeImmutable();
    $timestamps = array_keys($traffic);
    $hasTraffic = !!count($traffic);

    if ($hasTraffic) {
      $start = (new DateTimeImmutable())->setTimestamp(min($timestamps));
      $end = (new DateTimeImmutable())->setTimestamp(max($timestamps));
    }

    $period = new DatePeriod($from, $interval->interval(), $to);
    foreach ($period as $time) {
      $timestamp = $time->getTimestamp();
      $label = $interval->label($time);

      // Traffic data hasn't started yet or is already finished.
      $isMissing = !$hasTraffic || ($time < $start || $time > $end);

      // Add empty values if missing.
      $filledTraffic[$timestamp] = $traffic[$timestamp] ?? [
        'views' => $isMissing ? null : 0,
        'visits' => $isMissing ? null : 0,
        'visitors' => $isMissing ? null : 0,
        'label' => $label,
      ];

      // Data collection isn't finished yet.
      if ($now >= $time && $now < $time->add($interval->interval())) {
        $traffic[$timestamp]['unfinished'] = true;
      }
    }
    return $filledTraffic;
  }

  public static function getFirstTime(): DateTimeImmutable {
    $row = static::db()
      ->table('traffic')
      ->select('time')
      ->order('time ASC')
      ->first();
    $timeStamp = $row ? intval($row->time()) : 0;
    return (new DateTimeImmutable())->setTimestamp($timeStamp);
  }

  public static function clear() {
    static::db()->dropTable('meta');
    static::db()->dropTable('traffic');
    static::$db = null;
  }
}
