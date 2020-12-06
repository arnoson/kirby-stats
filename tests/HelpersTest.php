<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {
  public function testStartsWith() {
    $this->assertTrue(startsWith('TestCase', 'Test'));
    $this->assertFalse(startsWith('TestCase', 'Fest'));
  }

  /**
   * @dataProvider providePathIsPageData
   */
  public function testPathIsPage($path, $result) {
    $this->assertEquals($result, pathIsPage($path));
  }

  public function providePathIsPageData() {
    return [
      ['panel', false],
      ['panel/something', false],
      ['panelNotReally', true],
      ['api', false],
      ['api/something', false],
      ['apiNotReally', true],
      ['media', false],
      ['media/something', false],
      ['mediaNotReally', true],
      ['assets', false],
      ['assets/something', false],
      ['assetsNotReally', true]      
    ];
  }
}