<?php

namespace KirbyStats;

require_once __DIR__ . '/../lib/helpers.php';

return [
  'route:after' => function ($route, $path, $method, $result) {
    if ($result && pathIsPage($path)) {
      KirbyStats::setup();
      KirbyStats::analyze('/' . $path);
    }
  }
];