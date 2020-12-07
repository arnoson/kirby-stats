<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use KirbyStats\KirbyStats;

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'sqlite' => kirby()->root() . '/site/plugins/kirby-stats/stats.sqlite'
  ],
  'hooks' => require_once __DIR__ . '/hooks/hooks.php'
]);