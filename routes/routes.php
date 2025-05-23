<?php

use arnoson\KirbyStats\KirbyStats;

return [
  [
    'pattern' => 'kirby-stats/site',
    'method' => 'GET',
    'action' => function () {
      (new KirbyStats())->processRequest();
      return ['status' => 'ok'];
    },
  ],
  [
    'pattern' => 'kirby-stats/page/(:all?)',
    'method' => 'GET',
    'action' => function ($path = null) {
      (new KirbyStats())->processRequest($path ? "/$path" : '/');
      return ['status' => 'ok'];
    },
  ],
];
