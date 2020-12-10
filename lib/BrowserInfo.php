<?php

namespace KirbyStats;

require __DIR__ . '/../vendor/autoload.php';

use \Browser;

class BrowserInfo {
  /**
   * @var string
   */
  public $userAgent;

  /**
   * The browser name.
   * 
   * @var string
   */
  public $name;

  /**
   * The browser version.
   * 
   * @var float
   */
  public $version;

  /**
   * The major browser version.
   * 
   * @var int
   */
  public $majorVersion;

  /**
   * The platform the browser is running on.
   * 
   * @var string
   */
  public $system;

  /**
   * The browser object used to analyze the browser.
   * 
   * @var Browser
   */
  protected $browser;  

  public function __construct($userAgent) {
    $this->userAgent = $userAgent;
    $this->browser = new Browser($userAgent);

    $this->name = $this->browser->isRobot()
      ? 'Bot'
      : $this->browser->getBrowser();

    $this->version = $this->browser->isRobot()
      ? 0
      : $this->browser->getVersion();

    $this->majorVersion = (int) $this->version;

    $this->system = $this->browser->getPlatform();
  }

  public function toArray(): array {
    return [
      'name' => $this->name,
      'version' => $this->version,
      'majorVersion' => $this->majorVersion,
      'system' => $this->system
    ];
  }
}