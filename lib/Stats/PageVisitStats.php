<?php

namespace KirbyStats\Stats;

use Kirby\Database\Db;
use Exception;

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/Stats.php';

class PageVisitStats extends Stats {
  public static $interval = Stats::INTERVAL_HOURLY;

  public static function setup() {
    if (!Db::execute("CREATE TABLE PageVisitStats(
      Time INTEGER NOT NULL,
      Path TEXT NOT NULL,
      Count INTEGER DEFAULT 0,
      PRIMARY KEY (Time, Path)
    );")) {
      throw new Exception("Couldn't create PageVisitStats table.");
    }    
  }

  public static function increase($Path) {
    $time = self::getCurrentIntervalTime();
    $bindings = [$time, $Path];

    Db::execute("INSERT OR IGNORE INTO
        PageVisitStats(Time, Path)
      VALUES(?, ?);
    ", $bindings);

    Db::execute("UPDATE PageVisitStats
      SET Count = Count + 1
      WHERE Time = ? AND Path = ?;
    ", $bindings);    
  }
}