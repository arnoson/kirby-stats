<?php

namespace arnoson\KirbyStats;

use DateInterval;
use DateTimeImmutable;

enum Interval: int {
  case HOUR = 0;
  case DAY = 1;
  case WEEK = 2;
  case MONTH = 3;
  case YEAR = 4;

  private static function parseDate(
    DateTimeImmutable|int $date
  ): DateTimeImmutable {
    return is_a($date, 'DateTimeImmutable')
      ? $date
      : (new DateTimeImmutable())->setTimestamp($date);
  }

  public static function fromName(string $name): self {
    return match ($name) {
      'hour' => self::HOUR,
      'day' => self::DAY,
      'week' => self::WEEK,
      'month' => self::MONTH,
      'year' => self::YEAR,
    };
  }

  public function name(): string {
    return match ($this) {
      self::HOUR => 'hour',
      self::DAY => 'day',
      self::WEEK => 'week',
      self::MONTH => 'month',
      self::YEAR => 'year',
    };
  }

  public function interval(): DateInterval {
    return match ($this) {
      self::HOUR => new DateInterval('PT1H'),
      self::DAY => new DateInterval('P1D'),
      self::WEEK => new DateInterval('P7D'),
      self::MONTH => new DateInterval('P1M'),
      self::YEAR => new DateInterval('P1Y'),
    };
  }

  public function label(DateTimeImmutable|int $date): string {
    $format = match ($this) {
      self::HOUR => 'H:i',
      self::DAY => 'd M',
      self::WEEK => 'd M',
      self::MONTH => 'M Y',
      self::YEAR => 'Y',
    };
    return self::parseDate($date)->format($format);
  }

  public function slug(DateTimeImmutable|int $date): string {
    $format = match ($this) {
      self::HOUR => 'H-i',
      self::DAY => 'Y-m-d',
      self::WEEK => 'Y-m-d',
      self::MONTH => 'Y-m',
      self::YEAR => 'Y',
    };
    return self::parseDate($date)->format($format);
  }

  public function endOf(DateTimeImmutable|int $date): DateTimeImmutable {
    $start = $this->startOf($date);
    return $start->add($this->interval());
  }

  public function startOf(DateTimeImmutable|int $date): DateTimeImmutable {
    $date = self::parseDate($date);
    return match ($this) {
      self::HOUR => $date->setTime($date->format('H'), 0),
      self::DAY => $date->modify('today'),
      self::WEEK => $date->modify('monday this week'),
      self::MONTH => $date->modify('midnight first day of this month'),
      self::YEAR => $date->modify('midnight first day of january this year'),
    };
  }

  public function startOfNext(DateTimeImmutable|int $date): DateTimeImmutable {
    $date = self::parseDate($date);
    return match ($this) {
      self::HOUR => $date->modify('next hour')->setTime($date->format('H'), 0),
      self::DAY => $date->modify('tomorrow'),
      self::WEEK => $date->modify('monday next week'),
      self::MONTH => $date->modify('midnight first day of next month'),
      self::YEAR => $date->modify('midnight first day of january next year'),
    };
  }

  public function startOfLast(DateTimeImmutable|int $date): DateTimeImmutable {
    $date = self::parseDate($date);
    return match ($this) {
      self::HOUR => $date->modify('last hour')->setTime($date->format('H'), 0),
      self::DAY => $date->modify('yesterday'),
      self::WEEK => $date->modify('monday last week'),
      self::MONTH => $date->modify('midnight first day of last month'),
      self::YEAR => $date->modify('midnight first day of january last year'),
    };
  }
}
