<?php

namespace arnoson\KirbyStats;

use DatePeriod;
use DateTimeImmutable;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Kirby\Toolkit\Collection;
use Kirby\Database\Database;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class KirbyStats {
  protected static $mockOptions = [];

  public static function mockOptions(array $options) {
    static::$mockOptions = $options;
  }

  protected static function option(string $key, $default = null) {
    return A::get(static::$mockOptions, $key, $default) ??
      option("arnoson.kirby-stats.$key", $default);
  }

  protected static ?Database $db = null;

  protected static function db() {
    if (static::$db) {
      return static::$db;
    }

    static::$db = new Database([
      'type' => 'sqlite',
      'database' => static::option('sqlite'),
    ]);

    static::$db->createTable('traffic', [
      'time' => ['type' => 'int', 'key' => 'primary'],
      'uuid' => ['type' => 'text', 'key' => 'primary'],
      'interval' => ['type' => 'int', 'key' => 'primary'],
      'views' => ['type' => 'int'],
      'visits' => ['type' => 'int'],
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

    $isVisit = static::handleVisitTracking();
    static::increaseTraffic($uuid, isVisit: $isVisit, date: $date);

    // Collecting meta data only makes sense for visits.
    if ($isVisit) {
      $os = $device->getOs('name');
      $os = $os === 'GNU/Linux' ? 'Linux' : $os;

      $browser = $device->getClient('name');
      static::increaseMeta($uuid, 'browser', $browser, date: $date);
      static::increaseMeta($uuid, 'os', $os, date: $date);
    }
  }

  public static function handleVisitTracking(): bool {
    // Use the Last-Modified/If-Modified-Since headers as a way to detect unique
    // daily visits. The browser caches the response until midnight,
    // so subsequent requests on the same day will include the previous
    // timestamp in If-Modified-Since.
    // See https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies
    // Thanks Cabin for sharing this! :)
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
    $clientTimestamp = $ifModifiedSince ? strtotime($ifModifiedSince) : null;
    $midnight = strtotime('today GMT');
    $isVisit = false;

    if ($clientTimestamp && $clientTimestamp >= $midnight) {
      // Subsequent visit (view): increment by 1 second.
      $newTimestamp = $clientTimestamp + 1;
    } else {
      // First visit today.
      $newTimestamp = $midnight;
      $isVisit = true;
    }

    $tomorrow = strtotime('tomorrow GMT');
    header('Cache-Control: no-cache');
    header('Expires: ' . gmdate('D, d M Y H:i:s', $tomorrow) . ' GMT');
    header(
      'Last-Modified: ' . gmdate('D, d M Y H:i:s', $newTimestamp) . ' GMT'
    );

    return $isVisit;
  }

  public static function increaseTraffic(
    string $uuid,
    bool $isVisit,
    DateTimeImmutable $date
  ) {
    $interval = Interval::fromName(static::option('interval.traffic', 'hour'));
    $time = $interval->startOf($date)->getTimestamp();

    if ($isVisit) {
      $query = 'INSERT INTO traffic (time, uuid, interval, views, visits)
        VALUES (?, ?, ?, 1, 1)
        ON CONFLICT(time, uuid, interval)
        DO UPDATE SET views = views + 1, visits = visits + 1';
      static::db()->execute($query, [$time, $uuid, $interval->value]);
    } else {
      $query = 'INSERT INTO traffic (time, uuid, interval, views, visits)
        VALUES (?, ?, ?, 1, 0)
        ON CONFLICT(time, uuid, interval)
        DO UPDATE SET views = views + 1';
      static::db()->execute($query, [$time, $uuid, $interval->value]);
    }
  }

  public static function increaseMeta(
    string $uuid,
    string $category,
    string $key,
    DateTimeImmutable $date
  ) {
    $interval = Interval::fromName(static::option('interval.meta', 'day'));
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
    ?Interval $dataInterval = Interval::HOUR,
    ?string $uuid = null
  ) {
    $from = $dataInterval->startOf($from);
    $to = $dataInterval->startOf($to);
    $data = [];

    // ? Should we only consider meta data of the site if no uuid is set?
    $where = 'time BETWEEN ? AND ?';
    $bindings = [$from->getTimestamp(), $to->getTimestamp()];
    if ($uuid) {
      $where .= ' AND uuid = ?';
      $bindings[] = $uuid;
    }

    // Meta is simply summed over the request time.
    $whereStatement = $where ? "WHERE $where" : '';
    $query = "SELECT uuid, category, key, SUM(value) AS total
      FROM meta
      $whereStatement
      GROUP BY category, key";

    /** @var Collection */
    $meta = static::db()->query($query, $bindings);

    foreach ($meta as $row) {
      $uuid = $row->uuid();
      $category = $row->category();
      $key = $row->key();

      $data[$uuid] ??= ['meta' => []];
      $data[$uuid]['meta'][$category] ??= [];
      $data[$uuid]['meta'][$category][$key] ??= 0;
      $data[$uuid]['meta'][$category][$key] += intval($row->total());
    }

    // Traffic data is grouped by time interval so we can display it as a chart.
    /** @var Collection */
    $traffic = static::db()
      ->table('traffic')
      ->select('*')
      ->where($where, $bindings)
      ->all();

    foreach ($traffic as $row) {
      $uuid = $row->uuid();
      $time = intval($row->time());
      $interval = Interval::from(intval($row->interval()));
      $views = intval($row->views());
      $visits = intval($row->visits());

      $data[$uuid] ??= [];
      $data[$uuid]['traffic'] ??= [];

      // Since the intervals can be changed via config, it could happen that
      // the values are stored in a different interval and we have to normalize
      // them accordingly.
      if ($interval === $dataInterval) {
        // Intervals are matching.
        $data[$uuid]['traffic'][$time] ??= ['views' => 0, 'visits' => 0, 'label' => $dataInterval->label($time)]; // prettier-ignore
        $data[$uuid]['traffic'][$time]['views'] += $views;
        $data[$uuid]['traffic'][$time]['visits'] += $visits;
      } elseif ($interval->value < $dataInterval->value) {
        // Stored interval is smaller so we add the value to the corresponding
        // larger interval.
        $time = $dataInterval->startOf($time)->getTimestamp();
        $data[$uuid]['traffic'][$time] ??= ['views' => 0, 'visits' => 0, 'label' => $dataInterval->label($time)]; // prettier-ignore
        $data[$uuid]['traffic'][$time]['views'] += $views;
        $data[$uuid]['traffic'][$time]['visits'] += $visits;
      } elseif ($interval->value > $dataInterval->value) {
        // Stored interval is larger so we have to split the value up and create
        // a number of synthetic smaller intervals.
        $start = $interval->startOf($time);
        $end = $interval->endOf($time);
        $periodInterval = $dataInterval->interval();
        $period = new DatePeriod($start, $periodInterval, $end);
        $periodsCount = iterator_count($period);
        $viewsPerPeriod = (int) round($views / $periodsCount);
        $visitsPerPeriod = (int) round($visits / $periodsCount);
        foreach ($period as $time) {
          $time = $time->getTimestamp();
          $data[$uuid]['traffic'][$time] ??= ['views' => 0, 'visits' => 0, 'label' => $dataInterval->label($time)]; // prettier-ignore
          $data[$uuid]['traffic'][$time]['views'] += $viewsPerPeriod;
          $data[$uuid]['traffic'][$time]['visits'] += $visitsPerPeriod;
        }
      }
    }

    // Add empty traffic values in between.
    $now = new DateTimeImmutable();
    foreach ($data as $uuid => &$entry) {
      // ? Is there a case where there is meta info but no traffic?
      if (!isset($entry['traffic'])) {
        continue;
      }

      $timestamps = array_keys($entry['traffic']);
      $entryStart = (new DateTimeImmutable())->setTimestamp(min($timestamps));
      $entryEnd = (new DateTimeImmutable())->setTimestamp(max($timestamps));
      $period = new DatePeriod($from, $dataInterval->interval(), $to);
      $traffic = [];

      foreach ($period as $time) {
        $timestamp = $time->getTimestamp();

        // Traffic data hasn't started yet or is already finished.
        if ($time < $entryStart || $time > $entryEnd) {
          $traffic[$timestamp] = [
            'label' => $dataInterval->label($time),
            'missing' => true,
          ];
          continue;
        }

        // Add empty values if missing.
        $traffic[$timestamp] = $entry['traffic'][$timestamp] ?? [
          'views' => 0,
          'visits' => 0,
          'label' => $dataInterval->label($time),
        ];

        // Data collection isn't finished yet.
        if ($now >= $time && $now < $time->add($dataInterval->interval())) {
          $traffic[$timestamp]['unfinished'] = true;
        }
      }
      $entry['traffic'] = $traffic;
    }

    // Sum total page traffic
    $period = new DatePeriod($from, $dataInterval->interval(), $to);
    foreach ($period as $time) {
      $timestamp = $time->getTimestamp();
      $totalViews = 0;
      $totalVisits = 0;
      foreach ($data as $uuid => &$entry) {
        $traffic = $entry['traffic'][$timestamp];
        $isMissing = $traffic['missing'] ?? false;
        if (Str::startsWith($uuid, 'page://') && !$isMissing) {
          $totalViews += $traffic['views'];
          $totalVisits += $traffic['visits'];
        }
      }
      $data['site://']['traffic'][$timestamp]['totalViews'] = $totalViews;
      $data['site://']['traffic'][$timestamp]['totalVisits'] = $totalVisits;
    }

    return $data;
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
