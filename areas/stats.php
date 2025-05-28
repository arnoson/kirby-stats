<?php

namespace arnoson\KirbyStats;
use DateTimeImmutable;
use Kirby\Toolkit\Str;

class StatsView {
  public static function createIntervalView(
    int $interval,
    DateTimeImmutable $date,
    ?string $pageId = null
  ) {
    $now = new DateTimeImmutable();
    $current = Interval::startOf($interval, $date);
    $last = Interval::startOfLast($interval, $date);
    $next = Interval::startOfNext($interval, $date);

    $hasLast = $date > KirbyStats::getFirstTime();
    $hasNext = $next < $now;
    $format = $interval === 'month' ? 'Y-m' : 'Y-m-d';
    $path = $pageId ? ($pageId === 'home' ? '/' : "/$pageId") : null;

    $from = $current;
    $to = Interval::endOf($interval, $date);

    // Url params
    $pageParam = $pageId ? "/page/$pageId" : '';
    $intervalParam = Interval::name($interval);
    $currentParam = $current->format($format);
    $lastParam = $last->format($format);
    $nextParam = $next->format($format);

    // Urls
    $urls = [
      ...static::urls($pageId),
      'withPage' => "stats/$intervalParam/$currentParam/page/{{slug}}",
      'withoutPage' => "stats/$intervalParam/$currentParam",
    ];
    if ($hasLast) {
      $urls['last'] = "stats/$intervalParam/{$lastParam}$pageParam";
    }
    if ($hasNext) {
      $urls['next'] = "stats/$intervalParam/$nextParam/$pageParam";
    }

    // Labels
    $isToday =
      $interval === Interval::DAY &&
      $current == (new DateTimeImmutable())->setTime(0, 0);
    $labels = [
      'date' => $isToday ? 'Today' : Interval::label($interval, $current),
    ];

    // Stats
    $dataInterval = match ($interval) {
      Interval::DAY => Interval::HOUR,
      Interval::YEAR => Interval::MONTH,
      default => Interval::DAY,
    };
    $path = page($pageId)->uuid()->toString();
    $stats = KirbyStats::data($dataInterval, $from, $to, $path);

    return [
      'component' => 'kirby-stats-main-view',
      'props' => [
        'stats' => $stats,
        'labels' => $labels,
        'urls' => $urls,
        'page' => $pageId,
      ],
    ];
  }

  public static function createLatestView(
    string $range,
    ?string $pageId = null
  ) {
    $to = (new DateTimeImmutable())->modify('tomorrow');
    [$modifier, $label, $interval] = match ($range) {
      'today' => ['-1 day', 'Today', Interval::HOUR],
      '7-days' => ['-7 days', 'Last 7 days', Interval::DAY],
      '30-days' => ['-30 days', 'Last 30 days', Interval::DAY],
    };
    $from = $to->modify($modifier);

    // For a single day view, users can navigate using prev/next buttons.
    // However, for ranges like "last 7 days" or "last 30 days", navigation
    // isn't possible since these are rolling windows rather than fixed intervals
    if ($range === 'today') {
      return static::createIntervalView(Interval::DAY, $from, $pageId);
    }

    $urls = [
      ...static::urls($pageId),
      'withPage' => "stats/$range/page/{{slug}}",
      'withoutPage' => "stats/$range",
    ];

    $path = $pageId ? page($pageId)->uuid()->toString() : null;
    $stats = KirbyStats::data($interval, $from, $to, $path);

    if ($pageId) {
      $page = [
        'id' => $pageId,
        'title' => Helpers::pathTitle($pageId),
        'uuid' => page($pageId)?->uuid()->toString(),
      ];
    }

    return [
      'component' => 'kirby-stats-main-view',
      'props' => [
        'stats' => $stats,
        'labels' => ['date' => $label],
        'urls' => $urls,
        'page' => $page ?? null,
      ],
    ];
  }

  /**
   * The base urls need for every view.
   */
  protected static function urls(?string $page = null) {
    $urls = [];
    $now = new DateTimeImmutable();

    foreach (['day', 'week', 'month', 'year'] as $name) {
      $slug = Interval::slug(Interval::fromName($name), $now);
      $urls[$name] = "stats/$name/$slug" . ($page ? "/page/$page" : '');
    }

    foreach (['today', '7-days', '30-days'] as $name) {
      $urls[$name] = "stats/$name" . ($page ? "/page/$page" : '');
    }

    return $urls;
  }

  /**
   * Return the page id from the page url param.
   */
  public static function decodePageParam(string $path) {
    return str_replace('+', '/', $path);
  }

  /**
   * Return the url param from a page's id.
   */
  public static function encodePageParam(string $path) {
    return str_replace('+', '/', $path);
  }
}

return fn() => [
  'label' => 'Stats',
  'icon' => 'chart',
  'menu' => true,
  'link' => 'stats',
  'views' => [
    [
      'pattern' => 'stats',
      'action' => fn() => StatsView::createLatestView('today'),
    ],
    [
      'pattern' => 'stats/(:any)/page/(:any)',
      'action' => fn($range, $page) => StatsView::createLatestView(
        $range,
        StatsView::decodePageParam($page)
      ),
    ],
    [
      'pattern' => 'stats/(:any)',
      'action' => fn($range) => StatsView::createLatestView($range),
    ],
    [
      'pattern' => 'stats/(:any)/(:any)/page/(:any)',
      'action' => fn($interval, $date, $page) => StatsView::createIntervalView(
        Interval::fromName($interval),
        new DateTimeImmutable($date),
        StatsView::decodePageParam($page)
      ),
    ],
    [
      'pattern' => 'stats/(:any)/(:any)',
      'action' => fn($interval, $date) => StatsView::createIntervalView(
        Interval::fromName($interval),
        new DateTimeImmutable($date)
      ),
    ],
  ],
];
