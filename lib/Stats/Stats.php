<?php

namespace KirbyStats\Stats;

require_once __DIR__ . '/../helpers.php';

use \DateTime;
use Exception;

/**
 * The Stats base class. Provides all functionality to log page or site
 * statistics. All inherited classes must implement `shouldLog()`. If custom
 * columns are used `getColumnValues` must also be implemented.
 * 
 */
abstract class Stats {
  const INTERVAL_HOURLY = 0;
  const INTERVAL_DAILY = 1;
  const INTERVAL_WEEKLY = 2;
  const INTERVAL_MONTHLY = 3;
  const INTERVAL_YEARLY = 4;

  protected static $secondsInInterval = [
    self::INTERVAL_HOURLY => 3600,
    self::INTERVAL_DAILY => 86400,
    self::INTERVAL_WEEKLY => 604800,
    self::INTERVAL_MONTHLY => 2629800,
    self::INTERVAL_YEARLY => 31557600
  ];

  public $interval = self::INTERVAL_DAILY;

  public static $columns = [];

  /**
   * Wether or not to log statistics for each page or only for the site in
   * total.
   * 
   * @var bool
   */
  public $logPerPath = false;

  /**
   * Weather or not to throw exceptions.
   * 
   * @var bool
   */
  protected $debug = false;

  /**
   * The database table name.
   * 
   * @var string
   */
  public $tableName;

  /**
   * The database object.
   * 
   * @var \Kirby\Database\Database
   */
  protected $database;

  /**
   * Create a new Stats object.
   * 
   * @param \Kirby\Database\Database $database
   */
  public function __construct($database, $options) {
    $defaultOptions = [
      'interval' => Stats::INTERVAL_DAILY,
      'logPerPath' => false
    ];
    $options = array_merge($defaultOptions, $options);

    $this->database = $database;
    $this->tableName = (new \ReflectionClass($this))->getShortName();
    $this->debug = option('config', false);
    $this->interval = $options['interval'];
    $this->logPerPath = $options['logPerPath'];
  }

  /**
   * 
   */
  public function install(): bool {
    $commonColumns = [
      'Time' => ['type' => 'int', 'key' => 'primary'],
      'IntervalType' => ['type' => 'int', 'key' => 'primary'],
      'Count' => ['type' => 'int']
    ];
    if ($this->logPerPath) {
      $columns['Path'] = ['type' => 'text', 'key' => 'primary'];
    }

    $customColumns = array_map(function($column) {
      $column['key'] = 'primary';
      return $column;
    }, static::$columns);

    $columns = array_merge($commonColumns, $customColumns);
    return $this->database->createTable($this->tableName, $columns);
  }

  protected function getCurrentIntervalTime() {
    $time = (new DateTime())->getTimestamp();
    return $time - ($time % static::$secondsInInterval[$this->interval]);
  }

  /**
   * Execute a insert query and ignore constraints.
   */
  protected function insertOrIgnore(array $values): bool {
    $insert = $this->database->sql()->insert([
      'table' => $this->tableName,
      'values' => $values,
      'bindings' => []
    ]);

    // Kirby's `Sql` behaviour might change in the future, so to be safe we
    // first test if `OR IGNORE` is not present and than add it.
    if (!startsWith($insert['query'], 'INSERT OR IGNORE', false)) {
      $insert['query'] =
        preg_replace('/^INSERT/i', 'INSERT OR IGNORE', $insert['query']);
    }

    return $this->database->execute($insert['query'], $insert['bindings']);
  }

  /**
   * Execute an update query.
   */
  protected function update(array $values):bool {
    $update = $this->database->sql()->update([
      'table' => $this->tableName,
      'values' => $values,
      'bindings' => []
    ]);

    return $this->database->execute($update['query'], $update['bindings']);
  }

  public function increaseCounter($constraints): bool {
    $sql = $this->database->sql();
    $bindings = [];

    $countColumn = $sql->quoteIdentifier('Count');
    $query = [
      "UPDATE {$sql->tableName($this->tableName)}",
      "SET $countColumn = $countColumn + 1"
    ];
    $sql->extend($query, $bindings, $sql->where($constraints));
    
    return $this->database->execute($sql->query($query), $bindings);
  }

  protected function increase($constraints = [], string $path) {
    $constraints['Time'] = $this->getCurrentIntervalTime();
    $constraints['IntervalType'] = $this->interval;
    if ($this->logPerPath) {
      $constraints['Path'] = $path;
    }

    // First we make sure that a row exists for our constraints. We have to set
    // the default value for `Count` since defaults don't seem to work.
    if (
      !$this->insertOrIgnore(array_merge($constraints, ['Count' => 0])) &&
      $this->debug
    ) {
      throw new Exception("Couldn't insert new row.");
    }

    // Than we increase the counter for that row.
    if (!$this->increaseCounter($constraints) && $this->debug) {
      throw new Exception("Couldn't increase counter.");
    }
  }

  public function stats($from, $to, $page = null) {

  }

  abstract protected function shouldLog(array $analysis): bool;

  protected function getColumnValues(array $analysis): array {
    return [];
  }

  protected function validateColumnValues(array $columnValues) {
    foreach (array_keys(static::$columns) as $key) {
      if (!isset($columnValues[$key])) {
        false;
      }
    }
    return true;
  }

  public function log(array $analysis, $path) {
    if ($this->shouldLog($analysis)) {
      $columnValues = $this->getColumnValues($analysis);
      if (!$this->validateColumnValues($columnValues) && $this->debug) {
        throw new Exception('A value was not provided for all columns.');
      }
      $this->increase($columnValues, $path);
    }
  }
}