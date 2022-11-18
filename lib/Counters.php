<?php

namespace arnoson\KirbyStats;

use DateTime;
use Kirby\Database\Database;

class Counters {
  protected Database $database;
  protected string $tableName;
  protected array $counters;

  const INTERVALS = [
    'hourly' => 0,
    'daily' => 1,
    'weekly' => 2,
    'monthly' => 3,
    'yearly' => 4,
  ];

  const SECONDS_IN_INTERVAL = [
    self::INTERVALS['hourly'] => 3600,
    self::INTERVALS['daily'] => 86400,
    self::INTERVALS['weekly'] => 604800,
    self::INTERVALS['monthly'] => 2629800,
    self::INTERVALS['yearly'] => 31557600,
  ];

  public function __construct(
    Database $database,
    $tableName,
    $interval,
    $counters
  ) {
    $this->counters = $counters;
    $this->database = $database;
    $this->tableName = $tableName;
    $this->interval = self::INTERVALS[strtolower($interval)];

    $this->create();
  }

  public function create() {
    $columns = [
      '"Name" TEXT NULL',
      '"Time" INTEGER NULL',
      '"Interval" INTEGER NULL',
    ];

    foreach ($this->counters as $name) {
      $columns[] = "\"$name\" INTEGER NOT NULL DEFAULT 0";
    }

    $columns[] = 'PRIMARY KEY ("Name", "Time", "Interval")';

    // We can't use `sql->createTable()`, because default values won't work with
    // bindings in sqlite. So we have to create the query manually.
    $query =
      'CREATE TABLE ' .
      $this->database->sql()->quoteIdentifier($this->tableName) .
      ' (' .
      join(', ', $columns) .
      ')';

    return $this->database->execute($query);
  }

  /**
   * Add a new entry (if one doesn't already exist) for the specified name and
   * time.
   */
  protected function addEntry(string $name, int $time): bool {
    $insert = $this->database->sql()->insert([
      'table' => $this->tableName,
      'values' => [
        'Name' => $name,
        'Time' => $time,
        'Interval' => $this->interval,
      ],
      'bindings' => [],
    ]);
    return $this->database->execute($insert['query'], $insert['bindings']);
  }

  /**
   * Increase the specified counters by one for the current time interval.
   */
  public function increase($name, $counters): bool {
    $sql = $this->database->sql();
    $now = (new DateTime())->getTimestamp();
    $time = self::getIntervalTime($now, $this->interval);

    // Add a new entry (if none exists already).
    $this->addEntry($name, $time);

    $updates = array_map(function ($counter) use ($sql) {
      $identifier = $sql->columnName($this->tableName, $counter);
      return "$identifier = $identifier + 1";
    }, $counters);

    $updates = join(', ', $updates);
    $table = $sql->tableName($this->tableName);
    $where = '"Name" = ? AND "Time" = ?';
    $query = "UPDATE $table SET $updates WHERE $where";

    return $this->database->execute($query, [$name, $time]);
  }

  public function select(
    DateTime $from,
    DateTime $to,
    int $interval,
    string $name = null
  ) {
    $where = '"Time" BETWEEN ? AND ?';
    $bindings = [$from->getTimestamp(), $to->getTimestamp()];
    if ($name) {
      $where .= ' AND "Name" = ?';
      $bindings[] = $name;
    }

    $select = $this->database->sql()->select([
      'table' => $this->tableName,
      'columns' => '*',
      'where' => $where,
      'bindings' => $bindings,
    ]);

    $rows = $this->database
      ->query($select['query'], $select['bindings'])
      ->toArray(fn($el) => $el->toArray());

    $data = new CountersData($rows, $this->counters);
    return $data->groupByInterval($interval, $from, $to);
  }

  /**
   * Get the time for the interval. For example, if the interval is hourly, the
   * hour started for the time is returned.
   */
  static function getIntervalTime(int $time, int $interval) {
    return $time - ($time % self::SECONDS_IN_INTERVAL[$interval]);
  }
}