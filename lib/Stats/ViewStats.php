<?php

namespace KirbyStats\Stats;

require_once __DIR__ . '/Stats.php';

/**
 * Log page or site view statistics.
 */
class ViewStats extends Stats {
  protected function shouldLog($analysis): bool {
    return $analysis['view'];
  }
}