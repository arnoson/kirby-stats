<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Analyzer/ReferrerAnalyzer.php';
require_once __DIR__ . '/Stats/PageViewStats.php';
require_once __DIR__ . '/Stats/PageVisitStats.php';
require_once __DIR__ . '/Stats/BrowserStats.php';
require_once __DIR__ . '/Stats/SystemStats.php';

use Kirby\Database\Db;
use Kirby\Toolkit\F;
use KirbyStats\BrowserStats;
use KirbyStats\PageViewStats;
use KirbyStats\PageVisitStats;
use KirbyStats\ReferrerAnalyzer;
use KirbyStats\SystemStats;

class KirbyStats {
  /** @var Kirby\Database\Database */
  protected static $db;

  /** @var string */
  protected static $dbPath;

  protected static function dbPath() {
    return (
      self::$dbPath ?? 
      self::$dbPath = dirname(__FILE__) . '/../stats.sqlite'
    );
  }

  protected static function connect() {
    self::$db = Db::connect([
      'type' => 'sqlite',
      'database' => self::$dbPath
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

  public static function analyze($path) {
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
  }
}