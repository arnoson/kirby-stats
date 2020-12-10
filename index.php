<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use KirbyStats\Stats\Stats;

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'sqlite' => kirby()->root() . '/site/plugins/kirby-stats/stats.sqlite',
    // Note: don't change these options after `KirbyStats->install()`.
    // If you want to change something, delete the sqlite database, change the 
    // stats options und call `KirbyStats->install()` again.
    'viewStats' => [
      'interval' => Stats::INTERVAL_HOURLY,
      'logPerPath' => true
    ],
    'visitStats' => [
      'interval' => Stats::INTERVAL_HOURLY,
      'logPerPath' => true
    ],
    'browserStats' => [
      'interval' => Stats::INTERVAL_WEEKLY,
      'logPerPath' => false
    ],
    'systemStats' => [
      'interval' => Stats::INTERVAL_WEEKLY,
      'logPerPath' => false
    ]
  ],
  'hooks' => include __DIR__ . '/hooks/hooks.php',
  'api' => include __DIR__ . '/api/api.php'
]);