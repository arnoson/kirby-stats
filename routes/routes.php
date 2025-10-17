<?php

use arnoson\KirbyStats\KirbyStats;
use Kirby\Toolkit\Str;

return [
  [
    'pattern' => 'kirby-stats/site',
    'method' => 'GET',
    'action' => function () {
      KirbyStats::processRequest('site://');
      return ['status' => 'ok'];
    },
  ],
  [
    'pattern' => 'kirby-stats/page/(:all?)',
    'method' => 'GET',
    'action' => function ($path) {
      KirbyStats::processRequest($path);
      return ['status' => 'ok'];
    },
  ],
];
