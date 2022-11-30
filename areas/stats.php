<?php

namespace arnoson\KirbyStats;
use DateTimeImmutable;
use Kirby\Toolkit\A;

function urls() {
  $now = new DateTimeImmutable();
  $urls = [];
  foreach (['day', 'week', 'month', 'year'] as $name) {
    $slug = Interval::slug(Interval::fromName($name), $now);
    $urls[$name . 'Interval'] = "stats/$name/$slug";
  }
  return $urls;
}

function statsViewLatest(string $range) {
  $to = (new DateTimeImmutable())->modify('tomorrow');

  [$modifier, $label, $dataInterval] = match ($range) {
    'today' => ['-1 day', 'Today', Interval::HOUR],
    '7-days' => ['-7 days', 'Last 7 days', Interval::DAY],
    '30-days' => ['-30 days', 'Last 30 days', Interval::DAY],
  };

  $from = $to->modify($modifier);

  return [
    'component' => 'k-stats-main-view',
    'props' => [
      'stats' => (new KirbyStats())->data($from, $to, $dataInterval),
      'dateLabel' => $label,
      'urls' => urls(),
    ],
  ];
}

function statsViewInterval(int $interval, DateTimeImmutable $date) {
  $now = new DateTimeImmutable();
  $current = Interval::startOf($interval, $date);
  $last = Interval::startOfLast($interval, $date);
  $next = Interval::startOfNext($interval, $date);
  $hasNext = $next < $now;
  $format = $interval === 'month' ? 'Y-m' : 'Y-m-d';

  $from = Interval::startOf($interval, $date);
  $to = Interval::endOf($interval, $date);
  $intervalName = Interval::name($interval);
  $dataInterval = match ($interval) {
    Interval::DAY => Interval::HOUR,
    Interval::YEAR => Interval::MONTH,
    default => Interval::DAY,
  };

  return [
    'component' => 'k-stats-main-view',
    'props' => [
      'urls' => A::merge(urls(), [
        'lastInterval' => "stats/$intervalName/{$last->format($format)}",
        'nextInterval' => $hasNext
          ? "stats/$intervalName/{$next->format($format)}"
          : null,
      ]),
      'stats' => (new KirbyStats())->data($from, $to, $dataInterval),
      'dateLabel' => Interval::label($interval, $current),
    ],
  ];
}

return fn() => [
  'label' => 'Stats',
  'icon' => 'chart',
  'menu' => true,
  'link' => 'stats',
  'views' => [
    [
      'pattern' => 'stats',
      'action' => fn() => statsViewLatest('today'),
    ],
    [
      'pattern' => 'stats/(:any)',
      'action' => fn($range) => statsViewLatest($range),
    ],
    [
      'pattern' => 'stats/(:any)/(:any)',
      'action' => fn($interval, $date) => statsViewInterval(
        Interval::fromName($interval),
        new DateTimeImmutable($date)
      ),
    ],
  ],
];