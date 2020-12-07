<?php

namespace KirbyStats\Analyzer;

require_once __DIR__ . '/Analyzer.php';

/**
 * The ReferrerAnalyzer analyzes the current request based on the referrer.
 */
class ReferrerAnalyzer extends Analyzer {
  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   * 
   * @return bool
   */
  protected function isVisit(): bool {
    return (
      !$this->refreshed() &&
      $this->host() != $this->referrerHost()
    );
  }

  /**
   * Check if the current request counts as a view. For now all request that
   * aren't reloads do. In the future we could filter bots here.
   * 
   * @return  bool
   */
  protected function isView(): bool {
    return !$this->refreshed();
  }
}