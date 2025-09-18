<?php

require dirname(__DIR__) . '/kirby/bootstrap.php';

$kirby = new Kirby\Cms\App([
  'roots' => [
    'index' => __DIR__,
    'base' => __DIR__,
    'logs' => __DIR__ . '/site/logs',
  ],
]);

echo $kirby->render();
