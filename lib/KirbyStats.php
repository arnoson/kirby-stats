<?php

namespace arnoson\KirbyStats;

use DateTimeImmutable;
use Kirby\Database\Database;

require_once __DIR__ . '/../vendor/autoload.php';

class KirbyStats {
  protected Counters $stats;

  protected const BROWSERS = [
    'Opera',
    'Edge',
    'InternetExplorer',
    'Firefox',
    'Safari',
    'Chrome',
  ];

  protected const OS = ['Windows', 'Apple', 'Linux', 'Android', 'iOS'];

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
    $analysis = (new Analyzer())->analyze();
    if ($analysis['bot'] || !$analysis['view'] || !$analysis['visit']) {
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
  }

  public function data(
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    int $interval
  ): array {
    return $this->stats->data($interval, $from, $to);
  }

  public function remove(): bool {
    return $this->stats->remove();
  }
}