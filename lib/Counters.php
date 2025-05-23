<?php

namespace arnoson\KirbyStats;

use DatePeriod;
use DateTime;
use DateTimeImmutable;
use Kirby\Database\Database;
use Kirby\Toolkit\A;

class Counters {
  private Database $database;
  private string $tableName;
  private array $counterNames;
  private int $interval;

  public function __construct(
    Database $database,
    $tableName,
    $counterNames,
    $interval
  ) {
    $this->database = $database;
    $this->tableName = $tableName;
    $this->counterNames = $counterNames;
    $this->interval = $interval;
    $this->create();
  }

  /**
   * Create a table (if it doesn't already exist) to store the counter's values
   * for each interval.
   */
  private function create(): bool {
    $columns = [
      '"path" TEXT NULL',
      '"time" INTEGER NULL',
      '"interval" INTEGER NULL',
    ];

    foreach ($this->counterNames as $name) {
      $columns[] = "\"$name\" INTEGER NOT NULL DEFAULT 0";
    }

    $columns[] = 'PRIMARY KEY ("path", "time", "interval")';

    // We can't use `sql->createTable()`, because default values won't work with
    // bindings in sqlite. So we have to create the query manually.
    $columns = join(', ', $columns);
    $tableName = $this->database->sql()->quoteIdentifier($this->tableName);
    $query = "CREATE TABLE $tableName ($columns)";

    return $this->database->execute($query);
  }

  public function remove(): bool {
    $tableName = $this->database->sql()->quoteIdentifier($this->tableName);
    $query = "DROP TABLE $tableName";
    return $this->database->execute($query);
  }

  /**
   * Add a new entry (if one doesn't already exist).
   */
  private function addEntry(string $path, int $time): bool {
    $insert = $this->database->sql()->insert([
      'table' => $this->tableName,
      'values' => [
        'path' => $path,
        'time' => $time,
        'interval' => $this->interval,
      ],
      'bindings' => [],
    ]);
    return $this->database->execute($insert['query'], $insert['bindings']);
  }

  /**
   * Increase the specified counters by one.
   */
  public function increase(
    string $path,
    array $counterNames,
    ?DateTimeImmutable $date = null
  ): bool {
    $sql = $this->database->sql();
    $date ??= new DateTimeImmutable();
    $time = Interval::startOf($this->interval, $date)->getTimestamp();

    $this->addEntry($path, $time);

    $updates = array_map(function ($counter) use ($sql) {
      $identifier = $sql->columnName($this->tableName, $counter);
      return "$identifier = $identifier + 1";
    }, $counterNames);

    $updates = join(', ', $updates);
    $table = $sql->tableName($this->tableName);
    $where = '"path" = ? AND "time" = ? AND "interval" = ?';
    $query = "UPDATE $table SET $updates WHERE $where";

    return $this->database->execute($query, [$path, $time, $this->interval]);
  }

  /**
   * Get counter values from a specific time range and optionally only for a
   * specific path.
   */
  public function data(
    int $interval,
    DateTimeImmutable $from,
    DateTimeImmutable $to,
    ?string $path = null
  ): array {
    $where = '"time" BETWEEN ? AND ?';
    $bindings = [$from->getTimestamp(), $to->getTimestamp()];
    if ($path) {
      $where .= ' AND "path" = ?';
      $bindings[] = $path;
    }

    $select = $this->database->sql()->select([
      'table' => $this->tableName,
      'columns' => '*',
      'where' => $where,
      'bindings' => $bindings,
    ]);

    $rows = $this->database
      ->query($select['query'], $select['bindings'])
      ->toArray(function ($row) {
        $array = $row->toArray();
        // Kirby doesn't automatically cast integer table columns so we have to
        // do it manually.
        foreach ($array as $key => $value) {
          if ($key !== 'path') {
            $array[$key] = intval($value);
          }
        }
        return $array;
      });

    if (!count($rows)) {
      return [];
    }

    // Group the rows by time and path.
    $data = $this->groupRows($rows, $interval);

    // Add any missing intervals.
    $firstTime = $this->getFirstTime();
    $now = new DateTime();
    $dateInterval = Interval::interval($interval);
    $period = new DatePeriod($from, $dateInterval, $to);
    foreach ($period as $time) {
      $timeStamp = $time->getTimestamp();

      // If the time is before the earliest entry or in the future we consider
      // it as missing. Otherwise it would just represent empty data.
      $missing = $time < $firstTime || $time > $now;

      $data[$timeStamp] ??= [
        'time' => $timeStamp,
        'label' => Interval::label($interval, $time),
        'paths' => [],
        'missing' => $missing,
      ];

      if ($now >= $time && $now < $time->add($dateInterval)) {
        $data[$timeStamp]['now'] = true;
      }
    }

    return $data;
  }

  private function groupRows(array $rows, int $interval) {
    $group = [];
    $groupInterval = $interval;

    foreach ($rows as $row) {
      ['time' => $time, 'path' => $path, 'interval' => $interval] = $row;
      $countersByInterval = [];

      if ($interval === $groupInterval) {
        // Intervals are matching, so we can simply add the row's counters.
        $countersByInterval[$time] = $this->getCounters($row);
      } elseif ($interval < $groupInterval) {
        // Add the rows' counters to their corresponding group interval.
        $time = Interval::startOf($groupInterval, $time)->getTimestamp();
        $countersByInterval[$time] = $this->getCounters($row);
      } elseif ($interval > $groupInterval) {
        // Break up the row's counter values into multiple smaller intervals.
        $start = Interval::startOf($interval, $time);
        $end = Interval::endOf($interval, $time);
        $periodInterval = Interval::interval($groupInterval);
        $period = new DatePeriod($start, $periodInterval, $end);
        $num = iterator_count($period);
        $row = $this->divideCounters($this->getCounters($row), $num);
        foreach ($period as $time) {
          $countersByInterval[$time->getTimestamp()] = $row;
        }
      }

      foreach ($countersByInterval as $time => $counters) {
        $group[$time] ??= [
          'time' => $time,
          'label' => Interval::label($groupInterval, $time),
          'paths' => [],
          'site' => null,
        ];

        $path = $path === '/' ? '/home' : $path;
        $existing = $group[$time]['paths'][$path] ?? null;

        $group[$time]['paths'][$path] = $existing
          ? [
            'title' => $existing['title'],
            'counters' => $this->sumCounters($existing['counters'], $counters),
          ]
          : ['title' => Helpers::pathTitle($path), 'counters' => $counters];
      }
    }

    return $group;
  }

  function getFirstTime(): DateTimeImmutable {
    $tableName = $this->database->sql()->quoteIdentifier($this->tableName);
    $query = "SELECT time FROM $tableName ORDER BY time ASC LIMIT 1";
    $rows = $this->database->query($query);
    $timeStamp = $rows->isEmpty() ? 0 : intval($rows->first()->time());
    return (new DateTimeImmutable())->setTimestamp($timeStamp);
  }

  private function getCounters(array $row): array {
    return array_intersect_key($row, array_flip($this->counterNames));
  }

  private function divideCounters(array $counters, int $divisor): array {
    foreach ($this->counterNames as $counter) {
      $counters[$counter] = round(intval($counters[$counter]) / $divisor);
    }
    return $counters;
  }

  private function sumCounters(array $a, array $b) {
    $result = A::merge([], $a);
    foreach ($this->counterNames as $counter) {
      $result[$counter] = intval($a[$counter]) + intval($b[$counter]);
    }
    return $result;
  }
}
