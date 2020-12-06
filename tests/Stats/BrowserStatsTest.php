<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/StatsTestCase.php';

use Kirby\Database\Db;
use KirbyStats\Stats\BrowserStats;
use KirbyStats\KirbyStats;

class BrowserStatsTest extends StatsTestCase {
  public function testIncrease() {
    KirbyStats::connect();
    BrowserStats::setup();
    BrowserStats::increase([
      'name' => 'Firefox',
      'majorVersion' => 83
    ]);

    $row = Db::table('BrowserStats')
      ->select(['BrowserId', 'MajorVersion', 'Count'])
      ->first();

    $this->assertEquals(3, $row->BrowserId());
    $this->assertEquals(1, $row->Count());
  }
}