<?php

/** 
 * Get the current hour in unix time.
 */
function getCurrentHour() {
  $time = (new DateTime())->getTimestamp();
  return $time - ($time % 3600);
}

/** 
 * Get the current day in unix time.
 */
function getCurrentDay() {
  $time = (new DateTime())->getTimestamp();
  return $time - ($time % 86400);
}

/**
 * Test if a string starts with another string.
 */
function startsWith ($string, $startString) { 
  return (substr($string, 0, strlen($startString)) === $startString); 
}

/**
 * Test if a path represents a normal page.
 */
function pathIsPage($path) {
  foreach (['panel', 'api', 'assets', 'media'] as $ignoreDir) {
    if ($path === $ignoreDir || startsWith($path, $ignoreDir . '/')) {
      return false;
    }
  }
  return true;
}