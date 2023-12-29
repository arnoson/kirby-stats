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
    'sqlite' => kirby()->root('storage')
      ? kirby()->root('storage') . '/stats.sqlite'
      : kirby()->root('site') . '/storage/stats.sqlite',
    'ignoreDirs' => ['panel', 'api', 'assets', 'media'],
  ],
  'routes' => include __DIR__ . '/routes/routes.php',
  'areas' => include __DIR__ . '/areas/areas.php',
]);
