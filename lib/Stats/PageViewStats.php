<?php

namespace KirbyStats\Stats;

use Kirby\Database\Db;
use Exception;

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/Stats.php';

class PageViewStats extends Stats {
  public static $interval = Stats::INTERVAL_HOURLY;

  public static function setup() {
    if (!Db::execute("CREATE TABLE PageViewStats(
      Time INTEGER NOT NULL,
      Path TEXT NOT NULL,
      Count INTEGER DEFAULT 0,
      PRIMARY KEY (Time, Path)
    );")) {
      throw new Exception("Couldn't create PageViewStats table.");
    }
  }

  public static function increase($path) {
    $time = self::getCurrentIntervalTime();
    $bindings = [$time, $path];

    Db::execute("INSERT OR IGNORE 
      INTO PageViewStats(Time, Path)
      VALUES(?, ?);
    ", $bindings);

    Db::execute("UPDATE PageViewStats
      SET Count = Count + 1
      WHERE Time = ? AND Path = ?;
    ", $bindings);    
  }

  /**
   * @param \DateTime $from
   * @param \DateTime $to
   */
  public static function stats($from, $to) {
    $bindings = [$from->getTimestamp(), $to->getTimestamp()];
    
    $result = Db::query("SELECT *
      FROM PageViewStats
      WHERE Time BETWEEN ? AND ?;
    ", $bindings);

    return $result->toArray();
  }
}