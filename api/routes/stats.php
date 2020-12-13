<?php

use KirbyStats\KirbyStats;
use Kirby\Toolkit\V;

return [
  'pattern' => 'stats/(:any)/(:any)',
  'action' => function($from, $to) {
    if (!V::date($from)) {
      throw new Exception("'$from' is not a valid date.");
    }

    if (!V::date($to)) {
      throw new Exception("'$to' is not a valid date.");
    }

    return [
      'status' => 'ok',
      'data' => (new KirbyStats)->stats(new DateTime($from), new DateTime($to))
    ];
  }
];