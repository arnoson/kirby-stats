<?php

namespace KirbyStats;

use Kirby\Database\Db;
use Exception;

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/Stats.php';

class PageViewStats extends Stats {
  public static function setup() {
    if (!Db::execute("CREATE TABLE PageViewStats(
      Hour INTEGER NOT NULL,
      Path TEXT NOT NULL,
      Count INTEGER DEFAULT 0,
      PRIMARY KEY (Hour, Path)
    );")) {
      throw new Exception("Couldn't create PageViewStats table.");
    }    
  }

  public static function increase($Path) {
    $hour = getCurrentHour();
    $bindings = [$hour, $Path];

    Db::execute("INSERT OR IGNORE INTO
        PageViewStats(Hour, Path)
      VALUES(?, ?);
    ", $bindings);

    Db::execute("UPDATE PageViewStats
      SET Count = Count + 1
      WHERE Hour = ? AND Path = ?;
    ", $bindings);    
  }
}