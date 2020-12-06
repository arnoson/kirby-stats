<?php

namespace KirbyStats;

include_once __DIR__ . '/../helpers.php';
include_once __DIR__ . '/Stats.php';

use Exception;
use Kirby\Database\Db;

class SystemStats extends Stats {
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
      Date INTEGER NOT NULL,
      SystemId INTEGER NOT NULL,
      Count INTEGER DEFAULT 0
    );")) {
      throw new Exception("Couldn't create `SystemStats` table.");
    }
  }

  public static function increase($browser) {
    $day = getCurrentDay();
    $systemId = array_search($browser['name'], self::$systems);
    $bindings = [$day, $systemId];

    Db::execute("INSERT OR IGNORE INTO
        SystemStats(Day, SystemId)
      VALUES(?, ?);
    ", $bindings);

    Db::execute("UPDATE BrowserStats
      SET Count = Count + 1
      WHERE Day = ? AND SystemId = ?;
    ", $bindings);  
  }
}