<?php

use arnoson\KirbyStats\KirbyStats;
use Kirby\Toolkit\A;

function toggleVisit(bool $isVisit, $date = new DateTimeImmutable()) {
  if ($isVisit) {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = null;
  } else {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $date
      ->setTimezone(new DateTimeZone('UTC'))
      ->modify('-1 hour')
      ->format('D, d M Y H:i:s \G\M\T');
  }
}

function request(
  string $id,
  DateTimeImmutable $date,
  $isVisit = false,
  $isVisitor = false,
) {
  KirbyStats::mockTime($date);
  toggleVisit($isVisitor, $date);
  KirbyStats::processRequest('site://', $date);
  toggleVisit($isVisit, $date);
  KirbyStats::processRequest($id, $date);
  KirbyStats::resetMockTime();
}

return [
  'description' => 'Create dummy data for testing',
  'args' => [
    'time' => [
      'description' => 'The time range, e.g.: "1 week"',
      'default' => '1 Month',
    ],
  ],
  'command' => static function ($cli): void {
    $cli->out('Creating seed (this might take a while)...');

    KirbyStats::clear();

    $faker = Faker\Factory::create();
    $from = (new DateTimeImmutable())->modify('-' . $cli->arg('time'));
    $now = new DateTimeImmutable();
    $interval = new \DateInterval('PT1H'); // Hour interval
    $period = new \DatePeriod($from, $interval, $now);

    $totalIntervals = iterator_count($period);
    $progress = $cli->progress()->total($totalIntervals);

    $pages = kirby()->site()->index();

    foreach ($period as $date) {
      $progress->advance();
      $hour = (int) $date->format('G'); // 0-23

      // Skip hours with low traffic (3am-6am) most of the time
      if ($hour >= 3 && $hour <= 6 && rand(0, 10) > 2) {
        continue;
      }

      // More traffic during peak hours (9am-5pm)
      $isPeakHour = $hour >= 9 && $hour <= 17;

      // Determine number of unique visitors for this hour
      $uniqueVisitors = $isPeakHour ? rand(1, 8) : rand(0, 3);

      // For each unique visitor
      for ($visitor = 0; $visitor < $uniqueVisitors; $visitor++) {
        // Track unique visitor (site-wide)
        $isVisitor = true;
        $_SERVER['HTTP_USER_AGENT'] = $faker->userAgent();

        // Each visitor views 1-5 different pages
        $pagesToVisit = rand(1, 5);
        $visitedPages = [];

        for ($i = 0; $i < $pagesToVisit; $i++) {
          // Select random page (make home and about more likely)
          $weight = rand(1, 12);
          if ($weight <= 5) {
            $page = page('home');
          } elseif ($weight <= 10) {
            $page = page('about');
          } else {
            $page = $pages->nth(rand(0, $pages->count() - 1));
          }

          $path = $page->isHomePage() ? '' : $page->id();
          // For some reason `kirby()->languages()` isn't ready yet.
          $langs = ['', 'de/']; // en has no prefix
          $lang = $langs[array_rand($langs)];
          $path = $lang . $path;

          // First view of this page by this visitor
          if (!in_array($path, $visitedPages)) {
            request($path, $date, isVisit: true, isVisitor: $isVisitor);
            $isVisitor = false;
            $visitedPages[] = $path;

            // 50% chance to view the page more times
            $additionalViews = rand(0, 3);
            for ($v = 0; $v < $additionalViews; $v++) {
              request($path, $date);
            }
          }
        }
      }
    }

    $cli->success('Seed created!');
  },
];
