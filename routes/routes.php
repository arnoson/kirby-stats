<?php

use arnoson\KirbyStats\KirbyStats;

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
    'action' => function ($path = null) {
      $page = $path ? page($path) : site()->homePage();
      KirbyStats::processRequest($page?->uuid()->toString() ?? "/{$path}");
      return ['status' => 'ok'];
    },
  ],
];
