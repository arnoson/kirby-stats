<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use KirbyStats\BrowserInfo;

class BrowserInfoTest extends TestCase {
  static $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0';

  public function testProperties() {
    $browserInfo = new BrowserInfo(self::$userAgent);
    $this->assertEquals('Firefox', $browserInfo->name());
    $this->assertFalse($browserInfo->isBot());
    $this->assertEquals(83, $browserInfo->majorVersion());
    $this->assertEquals(83.0, $browserInfo->version());
    $this->assertFalse($browserInfo->isOther());
  }

  public function testToArray() {
    $browserInfo = new BrowserInfo(self::$userAgent);
    $this->assertEquals([
      'name' => 'Firefox',
      'version' => 83.0,
      'majorVersion' => 83
    ], $browserInfo->toArray());
  }
}