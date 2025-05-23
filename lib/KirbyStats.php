<?php

namespace arnoson\KirbyStats;

use DateTime;
use DateTimeImmutable;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Kirby\Database\Database;
use Kirby\Filesystem\F;

class KirbyStats {
  protected Counters $stats;

  protected const BROWSERS = [
    'Opera',
    'MicrosoftEdge',
    'InternetExplorer',
    'Firefox',
    'Safari',
    'Chrome',
  ];

  protected const OS = ['Windows', 'Mac', 'Linux', 'Android', 'iOS'];

  public function __construct($interval = null) {
    $counters = array_merge(['views', 'visits'], self::BROWSERS, self::OS);
    $interval ??= Interval::fromName(
      option('arnoson.kirby-stats.interval', 'hour')
    );
    $database = new Database([
      'type' => 'sqlite',
      'database' => option('arnoson.kirby-stats.sqlite'),
    ]);

    $this->stats = new Counters($database, 'Stats', $counters, $interval);
  }

  public function processRequest(
    ?string $path = 'site',
    ?DateTimeImmutable $date = null
  ) {
    if ($debug = option('arnoson.kirby-stats.debug')) {
      $startTime = microtime(true);
    }

    if (kirby()->user() || !option('arnoson.kirby-stats.enabled')) {
      return;
    }

    $info = $this->getDeviceInfo();
    if ($info['bot']) {
      return;
    }

    // Use the Last-Modified/If-Modified-Since headers as a way to detect unique
    // daily visits. The browser caches the response until midnight,
    // so subsequent requests on the same day will include the previous
    // timestamp in If-Modified-Since.
    // See https://withcabin.com/blog/how-cabin-measures-unique-visitors-without-cookies
    // Thanks Cabin for sharing this! :)
    $ifModifiedSince = kirby()->request()->header('If-Modified-Since');
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

    // Each request is a view (reloads get filtered out on the client).
    $incrementCounters = ['views'];

    // We are only interested in collection browser/os infos per visit.
    if ($isVisit) {
      $incrementCounters[] = 'visits';

      if (in_array($info['browser'], self::BROWSERS)) {
        $incrementCounters[] = $info['browser'];
      }

      if (in_array($info['os'], self::OS)) {
        $incrementCounters[] = $info['os'];
      }
    }

    $this->stats->increase($path, $incrementCounters, $date);

    if ($debug) {
      $duration = microtime(true) - $startTime . 'μs';
      $agent = $_SERVER['HTTP_USER_AGENT'];
      $time = (new DateTime())->format('Y-m-d H:i:s');
      $os = $info['os'];
      $browser = $info['browser'];
      F::append(
        kirby()->root('base') . '/stats-log.txt',
        "[$time] $duration $path $os $browser $agent\n"
      );
    }
  }

  protected function getDeviceInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $device = new DeviceDetector($userAgent);
    $device->discardBotInformation();
    $device->parse();

    $isBot = $device->isBot() || (new CrawlerDetect())->isCrawler($userAgent);
    $os = $device->getOs('name');
    $os = $os === 'GNU/Linux' ? 'Linux' : $os;
    $browser = Browser::getBrowserFamily($device->getClient('name'));

    return [
      'bot' => $isBot,
      'browser' => $this->toColumnName($browser),
      'os' => $this->toColumnName($os),
    ];
  }

  protected function toColumnName(string $text) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $text);
  }

  public function data(
    int $interval,
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    ?string $path = null
  ): array {
    return $this->stats->data($interval, $from, $to, $path);
  }

  public function getFirstTime() {
    return $this->stats->getFirstTime();
  }

  public function remove(): bool {
    return $this->stats->remove();
  }
}
