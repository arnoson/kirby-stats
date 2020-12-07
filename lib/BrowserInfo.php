<?php

namespace KirbyStats;

require __DIR__ . '/../vendor/autoload.php';

use \Browser;

/**
 * Get basic information about the user's browser.
 */
class BrowserInfo {
  /**
   * @var string
   */
  protected $userAgent;

  /**
   * The browser name.
   * 
   * @var string
   */
  protected $name;

  /**
   * The browser version.
   * 
   * @var float
   */
  protected $version;

  /**
   * The browser object used to analyze the browser.
   * 
   * @var Browser
   */
  protected $browser;

  /**
   * A list of all supported browser names.
   */
  protected static $supportedBrowsers = [
    Browser::BROWSER_CHROME,    
    Browser::BROWSER_EDGE,
    Browser::BROWSER_FIREFOX,
    Browser::BROWSER_IE,
    Browser::BROWSER_OPERA,
    Browser::BROWSER_SAFARI
  ];  

  /** 
   * Create a new BrowserInfo object.
   * 
   * @param string|null $userAgent
   */
  function __construct($userAgent = null) {
    $this->userAgent = $userAgent;
  }

  /**
   * Get (and create if necessary) a browser object.
   * 
   * @return Browser
   */
  protected function browser() {
    return $this->browser ?? $this->browser = new Browser($this->userAgent());
  }

  /**
   * Get the user agent.
   * 
   * @return string
   */
  protected function userAgent(): string {
    return $this->userAgent ?? $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
  }

  /**
   * Get the browser name.
   */
  public function name() {
    if ($this->name === null) {
      $name = $this->browser()->getBrowser();
      $this->name = $this->isBot()
        ? 'Bot'
        : (
          in_array($name, self::$supportedBrowsers)
            ? $name
            : 'Other'
        );
    }

    return $this->name;
  }

  public function isBot() {
    return $this->browser()->isRobot();
  }

  public function isOther() {
    return $this->name === 'Other';
  }

  /**
   * Get the browser version.
   * 
   * @return string|null
   */
  public function version() {
    return (
      $this->version ??
      $this->version = $this->isOther() ? null : $this->browser()->getVersion()
    ); 
  }

  /**
   * Get the major browser version.
   * 
   * @return int|null
   */
  public function majorVersion() {
    return $this->version() ? (int) $this->version() : null;
  }

  /**
   * Get all information as an array.
   * 
   * @return array
   */
  public function toArray(): array {
    return [
      'name' => $this->name(),
      'version' => $this->version(),
      'majorVersion' => $this->majorVersion()
    ];
  }  
}