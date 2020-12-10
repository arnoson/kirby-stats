<?php

namespace KirbyStats;

require_once __DIR__ . '/../lib/helpers.php';

return [
  'route:after' => function ($route, $path, $method, $result) {
    if ($result && pathIsPage($path)) {
      $ks = new KirbyStats;
      $ks->install();
      $ks->log('/' . $path);
    }
  }
];