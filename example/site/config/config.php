<?php

use arnoson\KirbyStats\KirbyStats;

return [
  'debug' => true,
  'arnoson.kirby-stats' => [
    'sessionDuration' => 60,
  ],
  'routes' => [
    [
      'pattern' => 'tracking-test',
      'action' => function () {
        KirbyStats::handleVisitTracking();
        return [
          'session-started' => kirby()->response()->header('Last-Modified'),
        ];
      },
    ],
  ],
];
