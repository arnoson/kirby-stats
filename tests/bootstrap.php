<?php

use Kirby\Filesystem\F;

require_once __DIR__ . '/../kirby/bootstrap.php';

$app = new Kirby\Cms\App([
  'roots' => [
    'index' => __DIR__ . '/../example',
    'config' => __DIR__ . '/kirby/config',
    'content' => __DIR__ . '/kirby/content',
  ],
]);

F::remove(option('arnoson.kirby-stats.sqlite'));
