<?php

namespace KirbyStats;

require_once __DIR__ . '/../vendor/autoload.php';

use Kirby\Database\Db;
use Kirby\Toolkit\F;
use KirbyStats\Stats\BrowserStats;
use KirbyStats\Stats\PageViewStats;
use KirbyStats\Stats\PageVisitStats;
use KirbyStats\Stats\SystemStats;
use KirbyStats\Analyzer\ReferrerAnalyzer;

class KirbyStats {
  /** @var Kirby\Database\Database */
  protected static $db;

  /** @var string */
  protected static $dbPath;

  protected static function dbPath() {
    return option('arnoson.kirby-stats.sqlite');
  }

  public static function connect() {
    self::$db = Db::connect([
      'type' => 'sqlite',
      'database' => self::dbPath()
    ]);    
  }

  /**
   * Create database and all necessary tables if there isn't a database yet.
   */
  public static function setup() {
    if (!F::exists(self::dbPath())) {
      self::connect();
      PageViewStats::setup();
      PageVisitStats::setup();
      BrowserStats::setup();
      SystemStats::setup();
    }
  }

  public static function analyze($path): array {
    $result = (new ReferrerAnalyzer())->analyze();

    self::connect();

    if ($result['view']) {
      PageViewStats::increase($path);
    }

    if ($result['visit']) {
      PageVisitStats::increase($path);
      BrowserStats::increase($result['browser']);
      // SystemStats::increase($result['system']);
    }

    return $result;
  }
}