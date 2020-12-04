<?php

require_once __DIR__ . '/lib/KirbyStats.php';

use Kirby\Cms\App;

App::plugin('arnoson/kirby-stats', []);

KirbyStats::init();