<?php

use Kirby\Cms\App;

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'enabled' => true,
    'sqlite' => kirby()->root('storage')
      ? kirby()->root('storage') . '/stats.sqlite'
      : kirby()->root('site') . '/storage/stats.sqlite',
    'interval' => 'day',
  ],
  'routes' => include __DIR__ . '/routes/routes.php',
  'areas' => include __DIR__ . '/areas/areas.php',
]);
