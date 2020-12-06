<?php

namespace KirbyStats;

include_once __DIR__ . '/../helpers.php';
include_once __DIR__ . '/Stats.php';

use Exception;
use Kirby\Database\Db;

class BrowserStats extends Stats {
  public static $browsers = [
    'Opera',
    'Edge',
    'Internet Explorer',
    'Firefox',
    'Safari',
    'Chrome',
    'Other'
  ];

  public static function setup() {
    if(!Db::execute("CREATE TABLE BrowserStats(
      Day INTEGER NOT NULL,
      BrowserId INTEGER NOT NULL,
      MajorVersion INTEGER NOT NULL,
      count INTEGER DEFAULT 0,
      PRIMARY KEY (Day, BrowserId, MajorVersion)
    );")) {
      throw new Exception("Couldn't create `BrowserStats` table.");
    }
  }

  public static function increase($browser) {
    $day = getCurrentDay();
    $browserId = array_search($browser['name'], self::$browsers);
    $bindings = [$day, $browserId, $browser['majorVersion']];

    Db::execute("INSERT OR IGNORE INTO
        BrowserStats(Day, BrowserId, MajorVersion)
      VALUES(?, ?, ?);
    ", $bindings);

    Db::execute("UPDATE BrowserStats
      SET Count = Count + 1
      WHERE Day = ? AND BrowserId = ? AND MajorVersion = ?;
    ", $bindings);  
  }
}