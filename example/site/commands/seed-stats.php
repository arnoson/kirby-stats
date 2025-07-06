<?php

use arnoson\KirbyStats\KirbyStats;

function toggleVisit(bool $isVisit) {
  if ($isVisit) {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = null;
  } else {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = (new DateTimeImmutable())
      ->setTime(0, 0, 1)
      ->format('D, d M Y H:i:s \G\M\T');
  }
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

    $faker = Faker\Factory::create();
    $pages = kirby()->site()->index();
    $from = (new DateTimeImmutable())->modify('-' . $cli->arg('time'));
    $now = new DateTimeImmutable();
    $interval = new \DateInterval('PT1H'); // Hour interval
    $period = new \DatePeriod($from, $interval, $now);

    foreach ($period as $date) {
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
        $_SERVER['HTTP_USER_AGENT'] = $faker->userAgent();
        toggleVisit(true);
        KirbyStats::processRequest('site://', $date);
        toggleVisit(false);

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
            $page = kirby()
              ->site()
              ->index()
              ->nth(rand(0, $pages->count() - 1));
          }
          $uuid = $page->uuid()->toString();

          // First view of this page by this visitor
          if (!in_array($uuid, $visitedPages)) {
            toggleVisit(true);
            KirbyStats::processRequest($uuid, $date);
            toggleVisit(false);
            KirbyStats::processRequest('site://', $date);
            $visitedPages[] = $uuid;

            // 50% chance to view the page more times
            $additionalViews = rand(0, 3);
            for ($v = 0; $v < $additionalViews; $v++) {
              toggleVisit(false);
              KirbyStats::processRequest($uuid, $date);
              KirbyStats::processRequest('site://', $date);
            }
          }
        }
      }
    }

    $cli->success('Seed created!');
  },
];
