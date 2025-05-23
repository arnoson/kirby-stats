<?php

namespace arnoson\KirbyStats;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Analyzer {
  /**
   * Analyze the current request.
   */
  public function analyze(?string $referrer = null): array {
    $host = strtok($_SERVER['HTTP_HOST'], ':');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $device = new DeviceDetector($userAgent);
    $device->discardBotInformation();
    $device->parse();

    $isBot = $device->isBot() || (new CrawlerDetect())->isCrawler($userAgent);

    $os = $device->getOs('name');
    $os = $os === 'GNU/Linux' ? 'Linux' : $os;

    $browser = Browser::getBrowserFamily($device->getClient('name'));

    return [
      'bot' => $isBot,
      'browser' => $this->toColumnName($browser),
      'os' => $this->toColumnName($os),
      // Right now, everything counts as a view since we already filter out
      // page reloads in the tracking script.
      'view' => true,
      'visit' => $host !== $referrer,
    ];
  }

  protected function toColumnName(string $text) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $text);
  }
}
