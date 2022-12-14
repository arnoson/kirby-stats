<?php

use Kirby\Cms\App;

load([
  'arnoson\\KirbyStats\\Analyzer' => __DIR__ . '/lib/Analyzer.php',
  'arnoson\\KirbyStats\\Counters' => __DIR__ . '/lib/Counters.php',
  'arnoson\\KirbyStats\\Helpers' => __DIR__ . '/lib/Helpers.php',
  'arnoson\\KirbyStats\\Interval' => __DIR__ . '/lib/Interval.php',
  'arnoson\\KirbyStats\\KirbyStats' => __DIR__ . '/lib/KirbyStats.php',
]);

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'sqlite' => kirby()->root() . '/site/plugins/kirby-stats/stats.sqlite',
    'ignoreDirs' => ['panel', 'api', 'assets', 'media'],
  ],
  'hooks' => include __DIR__ . '/hooks/hooks.php',
  'api' => include __DIR__ . '/api/api.php',
  'areas' => include __DIR__ . '/areas/areas.php',
]);