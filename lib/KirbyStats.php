<?php

namespace arnoson\KirbyStats;

use DatePeriod;
use DateTimeImmutable;
use DateTimeZone;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Kirby\Cms\Language;
use Kirby\Toolkit\Collection;
use Kirby\Database\Database;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Http\Route;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class KirbyStats {
  protected static $mockOptions = [];
  protected static $mockTime = null;

  public static function mockOptions(array $options) {
    static::$mockOptions = $options;
  }

  public static function mockTime(DateTimeImmutable $date) {
    static::$mockTime = $date
      ->setTimezone(new DateTimeZone('UTC'))
      ->getTimestamp();
  }

  public static function resetMockTime() {
    static::$mockTime = null;
  }

  protected static function now(): int {
    return static::$mockTime ?? time();
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
      'id' => ['type' => 'text', 'key' => 'primary'],
      'interval' => ['type' => 'int', 'key' => 'primary'],
      'views' => ['type' => 'int'],
      'visits' => ['type' => 'int'],
      'visitors' => ['type' => 'int'],
    ]);

    static::$db->createTable('meta', [
      'time' => ['type' => 'int', 'key' => 'primary'],
      'id' => ['type' => 'text', 'key' => 'primary'],
      'interval' => ['type' => 'int', 'key' => 'primary'],
      'category' => ['type' => 'int', 'key' => 'primary'],
      'key' => ['type' => 'text', 'key' => 'primary'],
      'value' => ['type' => 'int'],
    ]);

    return static::$db;
  }

  public static function processRequest(
    string $path,
    DateTimeImmutable $date = new DateTimeImmutable(),
  ) {
    if (kirby()->user() || !static::option('enabled')) {
      return;
    }

    [$path, $language] = static::parseLanguage($path);
    $id = $path ?: site()->homePageId();

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if (!$userAgent) {
      return;
    }

    $device = new DeviceDetector($userAgent);
    $device->discardBotInformation();
    $device->parse();

    $isBot = $device->isBot() || (new CrawlerDetect())->isCrawler($userAgent);
    if ($isBot) {
      return;
    }

    $isSite = $id === 'site://';
    $isVisit = static::handleVisitTracking();

    if ($isSite) {
      // A visit to the site means a new unique visitor.
      if ($isVisit) {
        static::increaseTraffic($id, $date, isVisitor: true);
      }
    } else {
      // Each page tracks its own views and visits.
      static::increaseTraffic($id, $date, isView: true, isVisit: $isVisit);

      // Collecting meta data only makes sense for visits.
      if ($isVisit) {
        $os = OperatingSystem::getOsFamily($device->getOs('name'));
        // GNU/Linux is more accurate, but we want to keep the labels simple.
        $os = $os === 'GNU/Linux' ? 'Linux' : $os;
        static::increaseMeta($id, 'os', $os, $date);

        // Microsoft Edge is classified as Internet Explorer, which might be
        // technically correct, but doesn't make sense to me. So we also
        // distinguish between IE and Edge.
        $client = $device->getClient('name');
        $browser =
          $client === 'Microsoft Edge'
            ? 'Microsoft Edge'
            : Browser::getBrowserFamily($device->getClient('name'));
        static::increaseMeta($id, 'browser', $browser, $date);

        if ($language) {
          static::increaseMeta($id, 'language', $language->code(), $date);
        }
      }
    }
  }

  public static function handleVisitTracking(): bool {
    // Use the Last-Modified/If-Modified-Since headers as a way to detect unique
    // daily visits.
    // See https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies
    // Thanks Cabin for sharing this! :)

    $response = kirby()->response();
    $sessionDuration = static::option('sessionDuration');

    $now = static::now();
    // Note: we have to read the header value directly form `$_SERVER` since
    // Kirby's `request()->header()` helper can't be easily mocked for testing.
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
    $sessionStart = $ifModifiedSince ? strtotime($ifModifiedSince) : $now;

    $elapsedSession = $now - $sessionStart;
    $sessionHasEnded = $elapsedSession > $sessionDuration;

    if ($sessionHasEnded) {
      $sessionStart = $now;
    }

    $response->header(
      'Last-Modified',
      gmdate('D, d M Y H:i:s', $sessionStart) . ' GMT',
    );
    // Use the session duration max-age as a fallback.
    $response->header(
      'Cache-Control',
      "public, max-age=$sessionDuration, no-cache",
    );

    $isVisit = $sessionStart === $now;
    if (option('debug')) {
      $response->header('Kirby-Stats-Elapsed-Session', $elapsedSession);
      $response->header('Kirby-Stats-Visit', $isVisit);
    }

    return $isVisit;
  }

  public static function increaseTraffic(
    string $id,
    DateTimeImmutable $date,
    bool $isView = false,
    bool $isVisit = false,
    bool $isVisitor = false,
  ) {
    $interval = Interval::fromName(static::option('interval', 'hour'));
    $time = $interval->startOf($date)->getTimestamp();

    $views = $isView ? 1 : 0;
    $visits = $isVisit ? 1 : 0;
    $visitors = $isVisitor ? 1 : 0;
    $update = "SET views = views + $views, visits = visits + $visits, visitors = visitors + $visitors";

    $query = "INSERT INTO traffic (time, id, interval, views, visits, visitors)
      VALUES (?, ?, ?, ?, ?, ?)
      ON CONFLICT(time, id, interval)
      DO UPDATE $update";

    $bindings = [$time, $id, $interval->value, $views, $visits, $visitors];
    static::db()->execute($query, $bindings);
  }

  public static function increaseMeta(
    string $id,
    string $category,
    string $key,
    DateTimeImmutable $date,
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

    $query = 'INSERT INTO meta (time, id, interval, category, key, value)
      VALUES (?, ?, ?, ?, ?, 1)
      ON CONFLICT(time, id, interval, category, key)
      DO UPDATE SET value = value + 1;';

    static::db()->execute($query, [
      $time,
      $id,
      $interval->value,
      $category,
      $key,
    ]);
  }

  public static function data(
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    Interval $interval = Interval::HOUR,
    ?string $id = null,
  ) {
    $id ??= 'site://';
    $isSite = $id === 'site://';
    $from = $interval->startOf($from);
    $to = $interval->startOf($to);
    $fromTime = $from->getTimestamp();
    $toTime = $to->getTimestamp();

    // Meta
    $where = $isSite ? '' : 'AND id = ?';
    $bindings = $isSite ? [$fromTime, $toTime] : [$fromTime, $toTime, $id];
    $query = "SELECT id, category, key, SUM(value) AS total
      FROM meta
      WHERE time BETWEEN ? AND ? $where
      GROUP BY category, key";

    /** @var Collection */
    $rows = static::db()->query($query, $bindings) ?: [];
    $meta = ['browser' => [], 'os' => [], 'language' => []];
    foreach ($rows as $row) {
      $category = $row->category();
      $key = $row->key();

      if ($category === 'language') {
        $key = kirby()->languages()->findBy('code', $key)?->name() ?? $key;
      }

      $meta[$category] ??= [];
      $meta[$category][$key] = intval($row->total());
    }

    // Traffic
    $where = $isSite ? '' : 'AND id = ?';
    $bindings = $isSite ? [$fromTime, $toTime] : [$fromTime, $toTime, $id];
    $query = "SELECT * FROM traffic
        WHERE time BETWEEN ? AND ? $where
        ORDER BY time ASC";

    /** @var Collection */
    $rows = static::db()->query($query, $bindings);
    $traffic = static::normalizeTraffic($rows, $interval);
    $traffic = static::fillMissingTraffic($traffic, $interval, $from, $to);

    // Total traffic for page(s)
    $where = $isSite ? '' : 'AND id = ?';
    $bindings = $isSite ? [$fromTime, $toTime] : [$fromTime, $toTime, $id];
    $query = "SELECT id, SUM(views) AS total_views, SUM(visits) AS total_visits, SUM(visitors) AS total_visitors from traffic
      WHERE time BETWEEN ? AND ? $where
      GROUP BY id";

    /** @var Collection */
    $rows = static::db()->query($query, $bindings) ?: [];
    $totalTraffic = [];
    foreach ($rows as $row) {
      $rowId = $row->id();
      $page = page($rowId);

      $name = null;
      if ($page) {
        $parts = [$page->title()->value()];
        while ($page = $page->parent()) {
          $parts[] = $page->title()->value();
        }
        $name = implode(' / ', array_reverse($parts));
      } elseif ($rowId === 'site://') {
        $name = site()->title()->value();
      }

      $totalTraffic[$rowId] = [
        'id' => $rowId,
        'name' => $name ?? $rowId,
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
    Interval $interval,
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
    DateTimeImmutable $to,
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

  protected static function parseLanguage(string $path) {
    if (!kirby()->multilang()) {
      return [$path, null];
    }

    $candidates = [];
    $rootLanguage = null;

    foreach (kirby()->languages() as $language) {
      if ($language->baseurl() !== kirby()->url()) {
        continue;
      }

      if ($language->path() === '') {
        $rootLanguage = $language;
        continue;
      }

      $candidates[] = [
        'language' => $language,
        'length' => Str::length($language->path()),
      ];
    }

    if ($path === '' && $rootLanguage) {
      return [$path, $rootLanguage];
    }

    // Sort candidates by descending path length to prefer longest-prefix
    // matches.
    usort($candidates, fn($a, $b) => $b['length'] <=> $a['length']);

    foreach ($candidates as $entry) {
      /** @var Language $language */
      $language = $entry['language'];
      $pattern = $language->pattern();

      // create a temporary Route and use its parse() method
      $route = new Route($pattern, 'GET', function () {});
      $arguments = $route->parse($route->pattern(), $path);
      if (!$arguments) {
        continue;
      }

      $remaining = $arguments[0] ?? '';
      return [$remaining, $language];
    }

    if ($rootLanguage) {
      return [$path, $rootLanguage];
    }

    return [$path, kirby()->defaultLanguage()];
  }
}
