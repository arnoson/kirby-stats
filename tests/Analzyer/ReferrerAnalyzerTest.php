<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use KirbyStats\Analyzer\ReferrerAnalyzer;

class ReferrerAnalyzerTest extends TestCase {
  public function testConstruct() {
    $analyzer = new ReferrerAnalyzer();
    $this->assertInstanceOf(ReferrerAnalyzer::class, $analyzer);
  }

  /**
   * @dataProvider provideAnalyzeData
   */
  public function testAnalyze($server, $result) {
    $serverBackup = $_SERVER;
    $_SERVER = $server;
    $analyzer = new ReferrerAnalyzer();
    $this->assertEquals($result, $analyzer->analyze());
    $_SERVER = $serverBackup;
  }

  public function provideAnalyzeData() {
    return [
      // No reload and no referrer, so the page is opened manually in
      // the browser.
      [
        'server' => [
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
          'HTTP_HOST' => 'localhost:8888'
        ],
        'result' => [
          'visit' => true,
          'view' => true,
          'referrer' => null,
          'browser' => [
            'name' => 'Firefox',
            'version' => 83.0,
            'majorVersion' => 83
          ]
        ]
      ],
      // No reload and a external referrer, so the page is opened via a link
      // from an external website.
      [
        'server' => [
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
          'HTTP_HOST' => 'localhost:8888',
          'HTTP_REFERER' => 'https://getkirby.com/'
        ],
        'result' => [
          'visit' => true,
          'view' => true,
          'referrer' => 'getkirby.com',
          'browser' => [
            'name' => 'Firefox',
            'version' => 83.0,
            'majorVersion' => 83
          ]
        ]        
      ],
      // Page is reloaded.    
      [
        'server' => [
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
          'HTTP_HOST' => 'localhost:8888',
          'HTTP_CACHE_CONTROL' => 'max-age=0'
        ],
        'result' => [
          'visit' => false,
          'view' => false,
          'referrer' => null,
          'browser' => [
            'name' => 'Firefox',
            'version' => 83.0,
            'majorVersion' => 83
          ]
        ]        
      ],
      // Page is reloaded.
      [
        'server' => [
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
          'HTTP_HOST' => 'localhost:8888',
          'HTTP_CACHE_CONTROL' => 'no-cache'
        ],
        'result' => [
          'visit' => false,
          'view' => false,
          'referrer' => null,
          'browser' => [
            'name' => 'Firefox',
            'version' => 83.0,
            'majorVersion' => 83
          ]
        ]        
      ],
      // No reload but an internal referrer, so the page is visited from within
      // the site.
      [
        'server' => [
          'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
          'HTTP_HOST' => 'localhost:8888',
          'HTTP_REFERER' => 'http://localhost:8888/'
        ],
        'result' => [
          'visit' => false,
          'view' => true,
          'referrer' => null,
          'browser' => [
            'name' => 'Firefox',
            'version' => 83.0,
            'majorVersion' => 83
          ]
        ]        
      ]                  
    ];
  }
}