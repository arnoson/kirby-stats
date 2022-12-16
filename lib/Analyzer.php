<?php

namespace arnoson\KirbyStats;

use Browser;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Analyzer {
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
    $browser = new Browser($this->userAgent());

    return [
      'bot' => (new CrawlerDetect())->isCrawler($this->userAgent()),
      'visit' => $this->isVisit(),
      'view' => $this->isView(),
      'referrer' =>
        $this->host() !== $this->referrerHost() ? $this->referrerHost() : null,
      // Remove all spaces so the browser name is a valid sql column name (only
      // relevant for internet explorer).
      'browser' => str_replace(' ', '', $browser->getBrowser()),
      'os' => $browser->getPlatform(),
    ];
  }

  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   *
   * @return bool
   */
  protected function isVisit(): bool {
    return !$this->refreshed() && $this->host() != $this->referrerHost();
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

  /**
   * Get the user agent.
   *
   * @return string
   */
  protected function userAgent(): string {
    return $this->userAgent ??
      ($this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null);
  }

  /**
   * Get the browser info.
   *
   * @return array|null
   */
  protected function browser() {
    return $this->browser ?? ($this->browser = new Browser($this->userAgent()));
  }

  /**
   * Get the host name (without port).
   *
   * @return string
   */
  protected function host(): string {
    return $this->host ?? ($this->host = strtok($_SERVER['HTTP_HOST'], ':'));
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
    return $this->refreshed ??
      ($this->refreshed =
        isset($_SERVER['HTTP_CACHE_CONTROL']) &&
        ($_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||
          $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'));
  }
}