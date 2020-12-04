<?php

use Kirby\Database\Db;
use Kirby\Toolkit\F;

class KirbyStats {
  /** @var Kirby\Database\Database */
  protected static $db;

  /** @var string */
  protected static $dbPath;

  protected static function dbPath() {
    return
      self::$dbPath ?? 
      self::$dbPath = dirname(__FILE__) . '/../stats.sqlite';
  }

  protected static function connect() {
    self::$db = Db::connect([
      'type' => 'sqlite',
      'database' => self::$dbPath
    ]);    
  }

  /**
   * Run the initial `install()` method if it there isn't a database file yet.
   */
  public static function init() {
    if (!F::exists(self::dbPath())) {
      self::install();
    }
  }

  /**
   * Create all necessary database tables.
   */
  public static function install() {
    self::connect();

    // Create tables for page views and visitors.

    if (!Db::execute("CREATE TABLE PageViews(
      Hour TEXT NOT NULL,
      PageId TEXT NOT NULL,
      Count INTEGER NOT NULL,
      PRIMARY KEY (Hour, PageId)
    );")) {
      throw new Exception("Couldn't create page_views table.");
    }

    if(!Db::execute("CREATE TABLE PageVisits(
      Hour TEXT NOT NULL,
      PageId TEXT NOT NULL,
      Count INTEGER NOT NULL,
      PRIMARY KEY (Hour, PageId)
    );")) {
      throw new Exception("Couldn't create page_visits table.");
    }

    // Create tables for browser statistics.
    
    if(!Db::execute("CREATE TABLE BrowserStats(
      Date TEXT NOT NULL,
      BrowserId INTEGER NOT NULL,
      BrowserVersion TEXT NOT NULL,
      count INTEGER NOT NULL,
      PRIMARY KEY (date, BrowserId, BrowserVersion)
    );")) {
      throw new Exception("Couldn't create `BrowserStats` table.");
    }

    if(!Db::execute("CREATE TABLE BrowserList(
      Id INTEGER PRIMARY KEY AUTOINCREMENT,
      Name TEXT NOT NULL
    );")) {
      throw new Exception("Couldn't create `BrowserList` table.");
    }

    if (!Db::execute("INSERT INTO
        BrowserList(name)
      VALUES
        ('Opera'),
        ('Edge'),
        ('Internet Explorer'),
        ('Firefox'),
        ('Safari'),
        ('Chrome');
    ")) {
      throw new Exception("Couldn't insert values into `BrowserList` table.");
    }

    // Create tables for system statistics.
    
    if (!Db::execute("CREATE TABLE SystemStats(
      Date TEXT NOT NULL,
      SystemId INTEGER NOT NULL,
      Count INTEGER
    );")) {
      throw new Exception("Couldn't create `SystemStats` table.");
    }

    if (!Db::execute("CREATE TABLE SystemList(
      Id INTEGER PRIMARY KEY AUTOINCREMENT,
      Name TEXT
    );")) {
      throw new Exception("Couldn't create `SystemList` table.");
    } 

    if (!Db::execute("INSERT INTO
        SystemList(name)
      VALUES
        ('Windows'),
        ('Apple'),
        ('Linux'),
        ('Android'),
        ('iOS');
    ")) {
      throw new Exception("Couldn't insert values into `SystemList` table.");
    }
  }
}