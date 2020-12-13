<?php

return [
  'pattern' => 'stats/(:any)/(:any)/(:any)',
  'action' => function($path, $from, $to) {
    return [
      'status' => 'ok',
      'data' => []
    ];
  }
];