<?php

use Kirby\Filesystem\F;

require_once __DIR__ . '/../kirby/bootstrap.php';

new Kirby\Cms\App([
  'roots' => [
    'roots' => ['index' => __DIR__ . '/../example'],
    'config' => __DIR__ . '/kirby/config',
  ],
]);

F::remove(option('arnoson.kirby-stats.sqlite'));
