<?php

namespace KirbyStats\Stats;

include_once __DIR__ . '/Stats.php';

/**
 * Log system statistics.
 */
class SystemStats extends Stats {
  public const SYSTEMS = [
    'Other' => 0,
    'Windows' => 1,
    'Apple' => 2,
    'Linux'=> 3,
    'Android' => 4,
    'iOS' => 5
  ];

  public static $columns = [
    'SystemId' => ['type' => 'int']
  ];

  protected function getSystemId(string $name): int {
    return static::SYSTEMS[$name] ?? static::SYSTEMS['Other'];
  }  

  protected function shouldLog($analysis): bool {
    return $analysis['visit'];
  }

  protected function getColumnValues(array $analysis): array {
    return [
      'SystemId' => $this->getSystemId($analysis['browser']['system'])
    ];
  }
}