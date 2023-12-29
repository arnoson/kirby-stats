<?php

use arnoson\KirbyStats\Analyzer;

it('analyzes server requests', function ($server, $referrer, $result) {
  $serverBackup = $_SERVER;
  $_SERVER = $server;
  $analyzer = new Analyzer();
  expect($analyzer->analyze($referrer))->toEqual($result);
  $_SERVER = $serverBackup;
})->with([
  // No referrer, so the page is opened manually in the browser.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'getkirby.com',
    ],
    'referrerHost' => null,
    'result' => [
      'visit' => true,
      'view' => true,
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
      'HTTP_HOST' => 'getkirby.com',
    ],
    'referrerHost' => 'duckduckgo.com',
    'result' => [
      'visit' => true,
      'view' => true,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
  // Internal referrer, so the page is visited from within the site.
  [
    'server' => [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'getkirby.com',
    ],
    'referrerHost' => 'getkirby.com',
    'result' => [
      'visit' => false,
      'view' => true,
      'browser' => 'Firefox',
      'os' => 'Windows',
      'bot' => false,
    ],
  ],
]);
