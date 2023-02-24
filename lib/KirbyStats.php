<?php

namespace arnoson\KirbyStats;

use DateTime;
use DateTimeImmutable;
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

  public function handle($path, DateTimeImmutable $date = null) {
    if ($debug = option('arnoson.kirby-stats.debug')) {
      $startTime = microtime(true);
    }

    if (kirby()->user()) {
      return;
    }

    $analysis = (new Analyzer())->analyze();
    if ($analysis['bot'] || !($analysis['view'] || $analysis['visit'])) {
      return;
    }

    $counters = [];

    if ($analysis['view']) {
      $counters[] = 'views';
    }

    if ($analysis['visit']) {
      $counters[] = 'visits';

      if (in_array($analysis['browser'], self::BROWSERS)) {
        $counters[] = $analysis['browser'];
      }

      if (in_array($analysis['os'], self::OS)) {
        $counters[] = $analysis['os'];
      }
    }

    $this->stats->increase($path, $counters, $date);

    if ($debug) {
      $duration = microtime(true) - $startTime . 'μs';
      $agent = $_SERVER['HTTP_USER_AGENT'];
      $time = (new DateTime())->format('Y-m-d H:i:s');
      $os = $analysis['os'];
      $browser = $analysis['browser'];
      F::append(
        kirby()->root('base') . '/stats-log.txt',
        "[$time] $duration $path $os $browser $agent\n"
      );
    }
  }

  public function data(
    int $interval,
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    string $path = null
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
