<?php

namespace arnoson\KirbyStats;

class Helpers {
  static function pathTitle(string $path = null) {
    if ($page = page($path)) {
      $parts = [$page->title()->value()];
      while ($page = $page->parent()) {
        $parts[] = $page->title()->value();
      }
      return implode(' / ', array_reverse($parts));
    } elseif ($path == '/home') {
      return page(option('home', 'home'))
        ->title()
        ->value();
    } else {
      return $path;
    }
  }
}
