<?php

namespace KirbyStats\Stats;

abstract class Stats {
  abstract static public function setup();

  abstract static public function increase($payload);
}