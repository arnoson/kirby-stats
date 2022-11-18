<?php

namespace arnoson\KirbyStats;

use DateTime;
use Error;
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

  public function period(string $period) {
    $to = new DateTime('tomorrow midnight');
    $interval = null;

    if ($period === 'day') {
      $from = (clone $to)->modify('-1 day');
      $interval = Counters::INTERVALS['hourly'];
    } elseif ($period === '7d') {
      $from = (clone $to)->modify('-7 days');
      $interval = Counters::INTERVALS['daily'];
    } elseif ($period === '30d') {
      $from = (clone $to)->modify('-30 days');
      $interval = Counters::INTERVALS['daily'];
    } else {
      throw new Error("period '$period' not defined");
    }

    return $this->stats->select($from, $to, $interval);
  }
}