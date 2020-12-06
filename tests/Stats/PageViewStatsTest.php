<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/StatsTestCase.php';

use Kirby\Database\Db;
use KirbyStats\Stats\PageViewStats;
use KirbyStats\KirbyStats;

class PageViewStatsTest extends StatsTestCase {

  public function testIncrease() {
    KirbyStats::connect();
    PageViewStats::setup();
    PageViewStats::increase('test/path');
    $row = Db::table('PageViewStats')->select(['Path', 'Count'])->first();
    $this->assertEquals('test/path', $row->Path());
    $this->assertEquals(1, $row->Count());
  }
}