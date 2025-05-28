<?php

namespace arnoson\KirbyStats;

use Kirby\Toolkit\Str;

class Helpers {
  static function pathTitle(?string $path = null) {
    if (!$path) {
      return null;
    }
    $path = Str::replace($path, '+', '/');

    if ($page = page($path)) {
      $parts = [$page->title()->value()];
      while ($page = $page->parent()) {
        $parts[] = $page->title()->value();
      }
      return implode(' / ', array_reverse($parts));
    }

    if ($path == '/home') {
      return page(option('home', 'home'))->title()->value();
    }

    return $path;
  }
}
