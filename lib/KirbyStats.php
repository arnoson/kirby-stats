<?php

namespace arnoson\KirbyStats;

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

  public function __construct() {
    $counters = array_merge(self::BROWSERS, self::OS, ['Views', 'Visits']);
    $interval = option('arnoson.kirby-stats.interval', 'hourly');
    $database = new Database([
      'type' => 'sqlite',
      'database' => option('arnoson.kirby-stats.sqlite'),
    ]);

    $this->stats = new Counters($database, 'Stats', $interval, $counters);
  }

  public function log($path) {
    $analysis = (new Analyzer())->analyze();
    if (!$analysis['view'] || !$analysis['visit']) {
      return;
    }

    $counters = [];
    if ($analysis['visit']) {
      $counters[] = 'Visits';

      if (in_array($analysis['browser'], self::BROWSERS)) {
        $counters[] = $analysis['browser'];
      }

      if (in_array($analysis['os'], self::OS)) {
        $counters[] = $analysis['os'];
      }
    } elseif ($analysis['view']) {
      $counters[] = 'Views';
    }

    $this->stats->increase($path, $counters);
  }
}