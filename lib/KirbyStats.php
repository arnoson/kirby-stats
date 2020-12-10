<?php

namespace KirbyStats;

require_once __DIR__ . '/../vendor/autoload.php';

use Kirby\Database\Database;
use KirbyStats\Analyzer\ReferrerAnalyzer;
use KirbyStats\Stats\ViewStats;
use KirbyStats\Stats\VisitStats;
use KirbyStats\Stats\BrowserStats;
use KirbyStats\Stats\SystemStats;

class KirbyStats {
  /**
   * @var \Kirby\Database\Database
   */
  protected $database;

  /**
   * @var KirbyStats\Stats\Stats[]
   */
  protected $statsInstances;

  public function __construct() {
    $database = $this->database = new Database([
      'type' => 'sqlite',
      'database' => option('arnoson.kirby-stats.sqlite')
    ]);

    $this->statsInstances = [
      new ViewStats($database, $this->getStatsOptions('ViewStats')),
      new VisitStats($database, $this->getStatsOptions('VisitStats')),
      new BrowserStats($database, $this->getStatsOptions('BrowserStats')),
      new SystemStats($database, $this->getStatsOptions('SystemStats'))
    ];
  }

  protected function getStatsOptions($className) {
    return option('arnoson.kirby-stats.' . lcfirst($className));
  }

  public function install() {
    foreach ($this->statsInstances as $instance) {
      $instance->install();
    }
  }

  public function log($path) {
    $analysis = (new ReferrerAnalyzer)->analyze();
    foreach ($this->statsInstances as $instance) {
      $instance->log($analysis, $path);
    }    
  }

  public function stats() {
    $result = [];
    foreach ($this->statsInstances as $instance) {
      $result[lcfirst($instance->tableName)] = $instance->stats();
    }
  }
}