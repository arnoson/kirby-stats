<?php

namespace arnoson\KirbyStats;
use DateTimeImmutable;
use Kirby\Toolkit\Str;

class StatsView {
  public static function createIntervalView(
    Interval $interval,
    DateTimeImmutable $date,
    ?string $pageId = null
  ) {
    $now = new DateTimeImmutable();
    $current = $interval->startOf($date);
    $last = $interval->startOfLast($date);
    $next = $interval->startOfNext($date);

    $hasLast = $date > KirbyStats::getFirstTime();
    $hasNext = $next < $now;
    $format = $interval === 'month' ? 'Y-m' : 'Y-m-d';

    $from = $current;
    $to = $interval->endOf($date);

    // Url params
    $pageParam = $pageId ? "/page/$pageId" : '';
    $intervalParam = $interval->name();
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
      'date' => $isToday
        ? t('arnoson.kirby-stats.today')
        : $interval->label($current),
    ];

    // Stats
    $dataInterval = match ($interval) {
      Interval::DAY => Interval::HOUR,
      Interval::YEAR => Interval::MONTH,
      default => Interval::DAY,
    };
    $uuid = $pageId ? page($pageId)->uuid()->toString() : 'site://';
    $stats = KirbyStats::data($from, $to, $dataInterval, $uuid);

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
    [$modifier, $name, $interval] = match ($range) {
      'today' => ['-1 day', 'today', Interval::HOUR],
      '7-days' => ['-7 days', '7-days', Interval::DAY],
      '30-days' => ['-30 days', '30-days', Interval::DAY],
      '12-months' => ['-12 months', '12-months', Interval::MONTH],
    };
    $label = t("arnoson.kirby-stats.$name");
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

    $uuid = $pageId ? page($pageId)->uuid()->toString() : 'site://';
    $stats = KirbyStats::data($from, $to, $interval, $uuid);

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
   * The base urls needed for every view.
   */
  protected static function urls(?string $pageId = null) {
    $now = new DateTimeImmutable();
    $pageParam = $pageId ? static::encodePageParam($pageId) : '';

    $intervals = match (KirbyStats::interval()) {
      Interval::HOUR => [
        Interval::DAY,
        Interval::WEEK,
        Interval::MONTH,
        Interval::YEAR,
      ],
      Interval::DAY => [Interval::WEEK, Interval::MONTH, Interval::YEAR],
      Interval::WEEK => [Interval::MONTH, Interval::YEAR],
      Interval::MONTH => [Interval::YEAR],
    };

    $intervalUrls = [];
    foreach ($intervals as $interval) {
      $name = $interval->name();
      $param = $interval->slug($now);
      $intervalUrls[$name] = "stats/$name/$param";
      if ($pageParam) {
        $intervalUrls[$name] .= "/page/$pageParam";
      }
    }

    $ranges = match (KirbyStats::option('interval')) {
      'hour' => ['today', '7-days', '30-days', '12-months'],
      'day' => ['7-days', '30-days', '12-months'],
      'week' => ['30-days', '12-months'],
      'month' => ['12-months'],
    };
    $rangeUrls = [];
    foreach ($ranges as $name) {
      $rangeUrls[$name] = "stats/$name";
      if ($pageParam) {
        $rangeUrls[$name] = "/page/$pageParam";
      }
    }

    return ['interval' => $intervalUrls, 'range' => $rangeUrls];
  }

  /**
   * Return the page id from the page url param.
   */
  public static function decodePageParam(string $path) {
    return Str::replace($path, '+', '/');
  }

  /**
   * Return the url param from a page's id.
   */
  public static function encodePageParam(string $path) {
    return Str::replace($path, '/', '+');
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
      'action' => fn() => StatsView::createLatestView(
        match (KirbyStats::option('interval')) {
          'hour' => 'today',
          'day' => '7-days',
          'week' => '30-days',
          'month' => '12-months',
        }
      ),
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
