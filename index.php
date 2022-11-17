<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'sqlite' => kirby()->root() . '/site/plugins/kirby-stats/stats.sqlite',
    'ignoreDirs' => ['panel', 'api', 'assets', 'media'],
  ],
  'hooks' => include __DIR__ . '/hooks/hooks.php',
  'api' => include __DIR__ . '/api/api.php',
]);