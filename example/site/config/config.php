<?php

use arnoson\KirbyStats\KirbyStats;

return [
  'debug' => true,
  'languages' => true,
  'arnoson.kirby-stats' => [
    'sessionDuration' => 60 * 60 * 6, // 6 hours
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
