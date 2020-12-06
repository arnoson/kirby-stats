<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/StatsTestCase.php';

use Kirby\Database\Db;
use KirbyStats\Stats\PageVisitStats;
use KirbyStats\KirbyStats;

class PageVisitStatsTest extends StatsTestCase {

  public function testIncrease() {
    KirbyStats::connect();
    PageVisitStats::setup();
    PageVisitStats::increase('test/path');
    $row = Db::table('PageVisitStats')->select(['Path', 'Count'])->first();
    $this->assertEquals('test/path', $row->Path());
    $this->assertEquals(1, $row->Count());
  }
}