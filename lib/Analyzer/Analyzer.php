<?php

namespace KirbyStats\Analyzer;

require_once __DIR__ . '/../BrowserInfo.php';

use KirbyStats\BrowserInfo;

/**
 * The Analyzer base class. All inherited classes must implement the `isView()`
 * and `isVisit()` methods.
 */
abstract class Analyzer {
  protected $host;
  protected $referrerHost;
  protected $refreshed;
  protected $userAgent;
  protected $browser;

  /** 
   * Analyze the current request.
   * 
   * @return array
   */
  public function analyze(): array {
    return [
      'visit' => $this->isVisit(),
      'view' => $this->isView(),
      'referrer' => $this->host() !== $this->referrerHost()
        ? $this->referrerHost()
        : null,
      'browser' => $this->browser(),
    ];
  }

  /**
   * Determine if the request counts as a visit.
   * 
   * @return bool
   */
  abstract protected function isVisit();

  /**
   * Determine if the request counts as a view.
   * 
   * @return bool
   */  
  abstract protected function isView();

  /**
   * Get the user agent.
   * 
   * @return string
   */
  protected function userAgent(): string {
    return (
      $this->userAgent ?? 
      $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null
    ); 
  }

  /**
   * Get the browser info.
   * 
   * @return array|null
   */
  protected function browser() {
    return (
      $this->browser ??
      $this->browser = (new BrowserInfo($this->userAgent()))->toArray()
    );
  }

  /**
   * Get the host name (without port).
   * 
   * @return string
   */
  protected function host(): string {
    return $this->host ?? $this->host = strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Get the referrer's host name (seems to omit the port automatically).
   * 
   * @return string\null
   */
  protected function referrerHost() {
    if (isset($_SERVER['HTTP_REFERER'])) {
      return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    }
  }

  /**
   * Check if the page has been refreshed.
   * 
   * @return bool
   */
  protected function refreshed(): bool {
    return $this->refreshed ?? $this->refreshed = (
      isset($_SERVER['HTTP_CACHE_CONTROL']) &&
      (
        $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  
        $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
      )
    );
  }
}