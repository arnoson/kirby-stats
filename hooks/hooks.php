<?php

use arnoson\KirbyStats\KirbyStats;
use Kirby\Toolkit\Str;

return [
  'route:after' => function ($route, $path, $method, $result) {
    if (!$result) {
      return;
    }

    $ignore = option('arnoson.kirby-stats.ignore');
    if (is_callable($ignore) && $ignore($path, $method)) {
      return;
    }

    $ignoreDirs = option('arnoson.kirby-stats.ignoreDirs', []);
    foreach ($ignoreDirs as $dir) {
      if ($path === $dir || Str::startsWith($path, "$dir/", true)) {
        return;
      }
    }

    (new KirbyStats())->handle("/$path");
  },
];