<?php

namespace KirbyStats\Stats;

require_once __DIR__ . '/Stats.php';

/**
 * Log page or site visit statistics.
 */
class VisitStats extends Stats {
  public function shouldLog($analysis): bool {
    return $analysis['visit'];
  }
}