<?php

require_once __DIR__ . '/lib/KirbyStats.php';
require_once __DIR__ . '/lib/helpers.php';

use Kirby\Cms\App;

App::plugin('arnoson/kirby-stats', [
  'hooks' => [
    'route:before' => function ($route, $path) {
      if (pathIsPage($path)) {
        KirbyStats::analyze($path);
      }
    }
  ]  
]);

KirbyStats::setup();