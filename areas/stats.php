<?php

use arnoson\KirbyStats\KirbyStats;

return function ($kirby) {
  return [
    'label' => 'Stats',
    'icon' => 'chart',
    'menu' => true,
    'link' => 'stats',
    'views' => [
      [
        'pattern' => 'stats',
        'action' => fn($path = null) => [
          'component' => 'k-stats-main-view',
          'props' => [
            'path' => $path,
            'stats' => (new KirbyStats())->period('7d'),
          ],
        ],
      ],
      [
        'pattern' => 'stats/period/(:any)/(:any?)',
        'action' => fn($period, $path = null) => [
          'component' => 'k-stats-page-view',
          'props' => [
            'path' => $path,
            'period' => $period,
            'stats' => (new KirbyStats())->period($period),
          ],
        ],
      ],
    ],
  ];
};