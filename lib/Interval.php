<?php

namespace arnoson\KirbyStats;

use DateInterval;
use DateTimeImmutable;

class Interval {
  const HOUR = 0;
  const DAY = 1;
  const WEEK = 2;
  const MONTH = 3;
  const YEAR = 4;

  private static function parseDate(DateTimeImmutable|int $date) {
    return is_a($date, 'DateTimeImmutable')
      ? $date
      : (new DateTimeImmutable())->setTimestamp($date);
  }

  static function fromName(string $name): int {
    return match ($name) {
      'hour' => self::HOUR,
      'day' => self::DAY,
      'week' => self::WEEK,
      'month' => self::MONTH,
      'year' => self::YEAR,
    };
  }

  static function name(int $interval): string {
    return match ($interval) {
      self::HOUR => 'hour',
      self::DAY => 'day',
      self::WEEK => 'week',
      self::MONTH => 'month',
      self::YEAR => 'year',
    };
  }

  static function interval(int $interval): DateInterval {
    $unit = self::name($interval);
    return DateInterval::createFromDateString("1 $unit");
  }

  static function label(int $interval, DateTimeImmutable|int $date) {
    $format = match ($interval) {
      Interval::HOUR => 'H:i',
      Interval::DAY => 'd M',
      Interval::WEEK => 'd M',
      Interval::MONTH => 'M Y',
      Interval::YEAR => 'Y',
    };
    return self::parseDate($date)->format($format);
  }

  static function slug(int $interval, DateTimeImmutable|int $date) {
    $format = match ($interval) {
      Interval::HOUR => 'H-i',
      Interval::DAY => 'Y-m-d',
      Interval::WEEK => 'Y-m-d',
      Interval::MONTH => 'Y-m',
      Interval::YEAR => 'Y',
    };
    return self::parseDate($date)->format($format);
  }

  static function endOf(int $interval, DateTimeImmutable|int $date) {
    $start = self::startOf($interval, $date);
    return $start->add(self::interval($interval));
  }

  static function startOf(int $interval, DateTimeImmutable|int $date) {
    $date = self::parseDate($date);
    return match ($interval) {
      self::HOUR => $date->setTime($date->format('H'), 0),
      self::DAY => $date->modify('today'),
      self::WEEK => $date->modify('monday this week'),
      self::MONTH => $date->modify('midnight first day of this month'),
      self::YEAR => $date->modify('midnight first day of january this year'),
    };
  }

  static function startOfNext(int $interval, DateTimeImmutable|int $date) {
    $date = self::parseDate($date);
    return match ($interval) {
      self::HOUR => $date->modify('next hour')->setTime($date->format('H'), 0),
      self::DAY => $date->modify('tomorrow'),
      self::WEEK => $date->modify('monday next week'),
      self::MONTH => $date->modify('midnight first day of next month'),
      self::YEAR => $date->modify('midnight first day of january next year'),
    };
  }

  static function startOfLast(int $interval, DateTimeImmutable|int $date) {
    $date = self::parseDate($date);
    return match ($interval) {
      self::HOUR => $date->modify('last hour')->setTime($date->format('H'), 0),
      self::DAY => $date->modify('yesterday'),
      self::WEEK => $date->modify('monday last week'),
      self::MONTH => $date->modify('midnight first day of last month'),
      self::YEAR => $date->modify('midnight first day of january last year'),
    };
  }
}