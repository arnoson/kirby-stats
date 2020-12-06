<?php

use PHPUnit\Framework\TestCase;
use Kirby\Toolkit\F;

class StatsTestCase extends TestCase {
  public function tearDown(): void {
    F::remove(option('arnoson.kirby-stats.sqlite'));
  }  
}