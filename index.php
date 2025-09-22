<?php

use Kirby\Cms\App;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

App::plugin('arnoson/kirby-stats', [
  'options' => [
    'enabled' => true,
    'database' => kirby()->root('logs') . '/kirby-stats/stats.sqlite',
    'interval' => 'day',
    'sessionDuration' => 60 * 60 * 6, // 6 hours
  ],
  'routes' => include __DIR__ . '/routes/routes.php',
  'areas' => include __DIR__ . '/areas/areas.php',
  'translations' => A::keyBy(
    A::map(Dir::files(__DIR__ . '/translations'), function ($file) {
      $translations = [];
      foreach (Yaml::read(__DIR__ . "/translations/$file") as $key => $value) {
        $translations["arnoson.kirby-stats.$key"] = $value;
      }
      return ['lang' => F::name($file), ...$translations];
    }),
    'lang'
  ),
]);
