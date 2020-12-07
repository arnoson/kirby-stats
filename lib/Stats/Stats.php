<?php

namespace KirbyStats\Stats;

use \DateTime;

abstract class Stats {
  const INTERVAL_HOURLY = 0;
  const INTERVAL_DAILY = 1;
  const INTERVAL_WEEKLY = 2;
  const INTERVAL_MONTHLY = 3;
  const INTERVAL_YEARLY = 4;

  protected static $secondsInInterval = [
    self::INTERVAL_HOURLY => 3600,
    self::INTERVAL_DAILY => 86400,
    self::INTERVAL_WEEKLY => 604800,
    self::INTERVAL_MONTHLY => 2629800,
    self::INTERVAL_YEARLY => 31557600
  ];

  public static $interval = self::INTERVAL_DAILY;

  protected static function getCurrentIntervalTime() {
    $time = (new DateTime())->getTimestamp();
    return $time - ($time % self::$secondsInInterval[self::$interval]);
  }

  abstract static public function setup();

  abstract static public function increase($payload);

  // abstract static public function stats($from, $to);
}