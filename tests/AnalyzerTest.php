<?php

use arnoson\KirbyStats\Analyzer;

it('analyzes server requests', function ($server, $result) {
  $serverBackup = $_SERVER;
  $_SERVER = $server;
  $analyzer = new Analyzer();
  expect($analyzer->analyze())->toEqual($result);
  $_SERVER = $serverBackup;
})->with([
  // No reload and no referrer, so the page is opened manually in
  // the browser.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
    ],
    'result' => [
      'visit' => true,
      'view' => true,
      'referrer' => null,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
  // No reload and a external referrer, so the page is opened via a link
  // from an external website.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
      'HTTP_REFERER' => 'https://getkirby.com/',
    ],
    'result' => [
      'visit' => true,
      'view' => true,
      'referrer' => 'getkirby.com',
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
  // Page is reloaded.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
      'HTTP_CACHE_CONTROL' => 'max-age=0',
    ],
    'result' => [
      'visit' => false,
      'view' => false,
      'referrer' => null,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
  // Page is reloaded.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
      'HTTP_CACHE_CONTROL' => 'no-cache',
    ],
    'result' => [
      'visit' => false,
      'view' => false,
      'referrer' => null,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
  // No reload but an internal referrer, so the page is visited from within
  // the site.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
      'HTTP_REFERER' => 'http://localhost:8888/',
    ],
    'result' => [
      'visit' => false,
      'view' => true,
      'referrer' => null,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
]);
