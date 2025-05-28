<?php

use arnoson\KirbyStats\KirbyStats;

class KirbyStatsSeed extends KirbyStats {
  public static function seed() {
    $pages = kirby()->site()->index();
    $from = (new DateTimeImmutable())->modify('-1 day');
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
        $browser = self::BROWSERS[array_rand(self::BROWSERS)];
        $os = self::OS[array_rand(self::OS)];
        static::stats()->increase(
          site()->uuid()->toString(),
          ['views', 'visits', $browser, $os],
          $date
        );

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
          $path = $page->uuid()->toString();

          // First view of this page by this visitor
          if (!in_array($path, $visitedPages)) {
            static::stats()->increase(
              $path,
              ['views', 'visits', $browser, $os],
              $date
            );
            $visitedPages[] = $path;

            // 50% chance to view the page more times
            $additionalViews = rand(0, 3);
            for ($v = 0; $v < $additionalViews; $v++) {
              static::stats()->increase($path, ['views'], $date);
            }
          }
        }
      }
    }
  }
}

return [
  'description' => 'Create dummy data for testing',
  'args' => [],
  'command' => static function ($cli): void {
    $cli->out('Creating seed (this might take a while)...');
    KirbyStatsSeed::seed();
    $cli->success('Seed created!');
  },
];
