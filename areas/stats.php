<?php

namespace arnoson\KirbyStats;
use DateTimeImmutable;
use Kirby\Toolkit\A;

function path(string $slug = null) {
  if (!$slug) {
    return;
  }
  $path = str_replace('+', '/', $slug);
  return $path === 'home' ? '/' : "/$path";
}

function statsView(array $data) {
  $now = new DateTimeImmutable();
  $page = $data['page'] ?? null;
  $labels = ['page' => Helpers::pathTitle($page)];
  $urls = [];

  foreach (['day', 'week', 'month', 'year'] as $name) {
    $slug = Interval::slug(Interval::fromName($name), $now);
    $urls[$name] = "stats/$name/$slug" . ($page ? "/page/$page" : '');
  }

  foreach (['today', '7-days', '30-days'] as $name) {
    $urls[$name] = "stats/$name" . ($page ? "/page/$page" : '');
  }

  return [
    'component' => 'k-stats-main-view',
    'props' => [
      'stats' => $data['stats'],
      'labels' => A::merge($labels, $data['labels']),
      'urls' => A::merge($urls, $data['urls']),
      'page' => $page,
    ],
  ];
}

function statsViewLatest(string $range, string $page = null) {
  $to = (new DateTimeImmutable())->modify('tomorrow');
  [$modifier, $label, $interval] = match ($range) {
    'today' => ['-1 day', 'Today', Interval::HOUR],
    '7-days' => ['-7 days', 'Last 7 days', Interval::DAY],
    '30-days' => ['-30 days', 'Last 30 days', Interval::DAY],
  };
  $from = $to->modify($modifier);

  return statsView([
    'stats' => (new KirbyStats())->data($interval, $from, $to, path($page)),
    'page' => "stats/$range/page/{{slug}}",
    'urls' => ['page' => "stats/$range/page/{{slug}}"],
    'labels' => ['date' => $label],
    'page' => $page,
  ]);
}

function statsViewInterval(
  int $interval,
  DateTimeImmutable $date,
  $page = null
) {
  $now = new DateTimeImmutable();
  $current = Interval::startOf($interval, $date);
  $last = Interval::startOfLast($interval, $date);
  $next = Interval::startOfNext($interval, $date);

  $hasNext = $next < $now;
  $format = $interval === 'month' ? 'Y-m' : 'Y-m-d';
  $path = $page ? ($page === 'home' ? '/' : "/$page") : null;

  $from = $current;
  $to = Interval::endOf($interval, $date);

  $intervalName = Interval::name($interval);
  $dataInterval = match ($interval) {
    Interval::DAY => Interval::HOUR,
    Interval::YEAR => Interval::MONTH,
    default => Interval::DAY,
  };

  return statsView([
    'stats' => (new KirbyStats())->data($dataInterval, $from, $to, $path),
    'labels' => ['date' => Interval::label($interval, $current)],
    'urls' => [
      'page' => "stats/$intervalName/{$current->format($format)}/page/{{slug}}",
      'last' => "stats/$intervalName/{$last->format($format)}",
      'next' => $hasNext
        ? "stats/$intervalName/{$next->format($format)}"
        : null,
    ],
  ]);
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
      'pattern' => 'stats/(:any)/page/(:any)',
      'action' => fn($range, $page) => statsViewLatest($range, $page),
    ],
    [
      'pattern' => 'stats/(:any)',
      'action' => fn($range) => statsViewLatest($range),
    ],
    [
      'pattern' => 'stats/(:any)/(:any)/page/(:any)',
      'action' => fn($interval, $date, $page) => statsViewInterval(
        Interval::fromName($interval),
        new DateTimeImmutable($date),
        $page
      ),
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