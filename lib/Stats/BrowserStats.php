<?php

namespace KirbyStats\Stats;

include_once __DIR__ . '/../helpers.php';
include_once __DIR__ . '/Stats.php';

use Exception;
use Kirby\Database\Db;

class BrowserStats extends Stats {
  public static $interval = Stats::INTERVAL_DAILY;

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
      Time INTEGER NOT NULL,
      BrowserId INTEGER NOT NULL,
      MajorVersion INTEGER NOT NULL,
      Count INTEGER DEFAULT 0,
      PRIMARY KEY (Time, BrowserId, MajorVersion)
    );")) {
      throw new Exception("Couldn't create `BrowserStats` table.");
    }
  }

  public static function increase($browser) {
    $time = self::getCurrentIntervalTime();
    $browserId = array_search($browser['name'], self::$browsers);
    $bindings = [$time, $browserId, $browser['majorVersion']];

    Db::execute("INSERT OR IGNORE INTO
        BrowserStats(Time, BrowserId, MajorVersion)
      VALUES(?, ?, ?);
    ", $bindings);

    Db::execute("UPDATE BrowserStats
      SET Count = Count + 1
      WHERE Time = ? AND BrowserId = ? AND MajorVersion = ?;
    ", $bindings);  
  }
}