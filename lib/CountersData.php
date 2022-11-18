<?php

namespace arnoson\KirbyStats;

use DateTime;
use Kirby\Toolkit\A;

class CountersData {
  protected array $rows;
  protected array $data;
  protected array $counters;

  public function __construct(array $rows, array $counters) {
    $this->rows = $rows;
    $this->counters = $counters;
  }

  protected function divideCounters(array $row, int $divisor): array {
    foreach ($this->counters as $counter) {
      $row[$counter] = round(intval($row[$counter]) / $divisor);
    }
    return $row;
  }

  protected function addCounters(array $rowA, array $rowB) {
    $result = A::merge([], $rowA);

    foreach ($this->counters as $counter) {
      $result[$counter] = intval($rowA[$counter]) + intval($rowB[$counter]);
    }
    return $result;
  }

  protected function addIntervalEntry($time, &$entry) {
    $existingEntry = $this->data[$time] ?? null;
    $this->data[$time] = $existingEntry
      ? $this->addCounters($existingEntry, $entry)
      : $entry;

    $this->data[$time]['Label'] = $this->makeLabel($time);
  }

  protected function makeLabel($time) {
    return (new DateTime())->setTimestamp($time)->format('D, j M');
  }

  public function groupByInterval(
    int $interval,
    DateTime $from,
    DateTime $to
  ): array {
    $this->data = [];
    $dataInterval = $interval;
    $dataIntervalDuration = Counters::SECONDS_IN_INTERVAL[$dataInterval];

    foreach ($this->rows as $row) {
      $interval = intval($row['Interval']);
      $intervalDuration = Counters::SECONDS_IN_INTERVAL[$interval];

      if ($intervalDuration === $dataIntervalDuration) {
        // Desired interval and entry's interval are matching so we can simply
        // add it.
        $this->addIntervalEntry($row['Time'], $row);
      } elseif ($intervalDuration > $dataIntervalDuration) {
        // Entry's interval is greater, so we break up the entry into multiple
        // smaller entries.
        $divisor = $intervalDuration / $dataIntervalDuration;
        // Each smaller entry has only a fraction of the counter values.
        $row = $this->divideCounters($row, $divisor);
        for ($i = 0; $i < $divisor; $i++) {
          $time = $row['Time'] + $i * $dataIntervalDuration;
          if ($time >= $to->getTimestamp()) {
            break;
          }
          $copy = A::merge($row, ['Time' => $time]);
          $this->addIntervalEntry($time, $copy);
        }
      } elseif ($intervalDuration < $dataIntervalDuration) {
        // Entry's interval is smaller, so we add it's counter values to the
        // interval the entry belongs to.
        $time = Counters::getIntervalTime($row['Time'], $dataInterval);
        $this->addIntervalEntry($time, $row);
      }
    }

    for (
      $time = $from->getTimestamp();
      $time < $to->getTimestamp();
      $time += $dataIntervalDuration
    ) {
      $this->data[$time] ??= [
        'Label' => $this->makeLabel($time),
        'Missing' => true,
      ];
    }

    return $this->data;
  }
}