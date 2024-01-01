<?php

use arnoson\KirbyStats\KirbyStats;
use Kirby\Toolkit\Str;

return [
  [
    'pattern' => 'kirby-stats/hit',
    'method' => 'POST',
    'action' => function () {
      $path = get('path');
      $referrer = get('referrer');
      (new KirbyStats())->handle($path, $referrer);
      return ['status' => 'ok'];
    },
  ],
];
