<?php

namespace KirbyStats\Stats;

include_once __DIR__ . '/Stats.php';

/**
 * Log browser statistics.
 */
class BrowserStats extends Stats {
  public const BROWSERS = [
    'Other' => 0,
    'Opera' => 1,
    'Edge' => 2,
    'Internet Explorer' => 3,
    'Firefox' => 4,
    'Safari' => 5,
    'Chrome' => 6
  ];

  public static $columns = [
    'BrowserId' => ['type' => 'int'],
    'MajorVersion' => ['type' => 'int']
  ];

  protected function getBrowserId(string $name): int {
    return static::BROWSERS[$name] ?? static::BROWSERS['Other'];
  }

  protected function shouldLog(array $analysis): bool {
    return $analysis['visit'];
  }

  protected function getColumnValues(array $analysis): array {
    $browser = $analysis['browser'];
    return [
      'BrowserId' => $this->getBrowserId($browser['name']),
      'MajorVersion' => $browser['majorVersion']      
    ];   
  }
}