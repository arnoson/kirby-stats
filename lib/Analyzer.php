<?php

namespace arnoson\KirbyStats;

use DeviceDetector\DeviceDetector;
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
    $device = new DeviceDetector($this->userAgent());
    $device->discardBotInformation();
    $device->parse();

    $isBot =
      $device->isBot() || (new CrawlerDetect())->isCrawler($this->userAgent());

    $os = $device->getOs('name');
    $os = $os === 'GNU/Linux' ? 'Linux' : $os;

    return [
      'bot' => $isBot,
      'visit' => $this->isVisit(),
      'view' => $this->isView(),
      'referrer' =>
        $this->host() !== $this->referrerHost() ? $this->referrerHost() : null,
      // Remove all spaces so the browser name is a valid sql column name (eg
      // `Internet Explorer` or `Microsoft Edge`).
      'browser' => str_replace(' ', '', $device->getClient('name')),
      'os' => $os,
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
