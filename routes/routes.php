<?php

use arnoson\KirbyStats\KirbyStats;

return [
  [
    'pattern' => 'kirby-stats/site',
    'method' => 'GET',
    'action' => function () {
      KirbyStats::processRequest();
      return ['status' => 'ok'];
    },
  ],
  [
    'pattern' => 'kirby-stats/page/(:all?)',
    'method' => 'GET',
    'action' => function ($path = null) {
      KirbyStats::processRequest($path ? "/$path" : '/');
      return ['status' => 'ok'];
    },
  ],
];
