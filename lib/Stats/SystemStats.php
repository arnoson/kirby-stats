<?php

namespace KirbyStats\Stats;

include_once __DIR__ . '/../helpers.php';
include_once __DIR__ . '/Stats.php';

use Exception;
use Kirby\Database\Db;

class SystemStats extends Stats {
  public static $interval = Stats::INTERVAL_DAILY;

  public static $systems = [
    'Windows',
    'Apple',
    'Linux',
    'Android',
    'iOS',
    'Other'
  ];

  public static function setup() {
    if (!Db::execute("CREATE TABLE SystemStats(
      Time INTEGER NOT NULL,
      SystemId INTEGER NOT NULL,
      Count INTEGER DEFAULT 0,
      PRIMARY KEY (Time, SystemId)
    );")) {
      throw new Exception("Couldn't create `SystemStats` table.");
    }
  }

  public static function increase($browser) {
    $time = self::getCurrentIntervalTime();
    $systemId = array_search($browser['name'], self::$systems);
    $bindings = [$time, $systemId];

    Db::execute("INSERT OR IGNORE INTO
        SystemStats(Time, SystemId)
      VALUES(?, ?);
    ", $bindings);

    Db::execute("UPDATE BrowserStats
      SET Count = Count + 1
      WHERE Time = ? AND SystemId = ?;
    ", $bindings);  
  }
}