<?php

namespace KirbyStats;

abstract class Stats {
  abstract static public function setup();

  abstract static public function increase($payload);
}