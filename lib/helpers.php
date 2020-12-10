<?php

/**
 * Test if a string starts with another string.
 */
function startsWith ($string, $startString, $caseSensitive = true) { 
  if (!$caseSensitive) {
    $string = strtolower($string);
    $startString = strtolower($startString);
  }
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