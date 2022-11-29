<?php

require_once __DIR__ . '/../vendor/autoload.php';

use arnoson\KirbyStats\Interval;
use arnoson\KirbyStats\KirbyStats;
use PHPUnit\Framework\TestCase;

class KirbyStatsTest extends TestCase {
  public function setUp(): void {
    $_SERVER = [
      'HTTP_USER_AGENT' =>
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
      'HTTP_HOST' => 'localhost:8888',
    ];
  }

  public function testData() {
    $now = new DateTimeImmutable();
    $from = $now->modify('yesterday');
    $to = $now->modify('tomorrow');

    $stats = new KirbyStats();
    $stats->handle('/test', $now);
    $stats->handle('/test', $now);
    $stats->handle('/test', $now);

    $data = $stats->data($from, $to, Interval::HOUR);
    $time = Interval::startOf(Interval::HOUR, $now)->getTimestamp();
    $this->assertEquals($data[$time]['paths']['/test']['visits'], 3);

    $stats->remove();
  }

  public function testBiggerInterval() {
    $now = new DateTimeImmutable();
    $from = $now->modify('yesterday');
    $to = $now->modify('tomorrow');

    $stats = new KirbyStats(Interval::HOUR);
    $stats->handle('/test', $now);

    $stats = new KirbyStats(Interval::DAY);
    for ($i = 0; $i < 24; $i++) {
      $stats->handle('/test', $now);
    }

    $data = $stats->data($from, $to, Interval::HOUR);
    $time = Interval::startOf(Interval::HOUR, $now)->getTimestamp();
    // An hour with 1 visit, and a day with 24 visits. When retrieving the data
    // hourly, the day should be split up in 24 hour intervals with one visit
    // each. So all in all the hour should now have 2 visits.
    $this->assertEquals($data[$time]['paths']['/test']['visits'], 2);

    $stats->remove();
  }

  public function testSmallerInterval() {
    $now = new DateTimeImmutable();
    $from = $now->modify('yesterday');
    $to = $now->modify('tomorrow');

    $stats = new KirbyStats(Interval::WEEK);
    $stats->handle('/test', $now);

    $stats = new KirbyStats(Interval::DAY);
    $stats->handle('/test', $now);

    $data = $stats->data($from, $to, Interval::WEEK);
    $time = Interval::startOf(Interval::WEEK, $now)->getTimestamp();
    // A week and a day with 1 visit each. When retrieving the data weekly, the
    // should be added to the week.
    $this->assertEquals($data[$time]['paths']['/test']['visits'], 2);

    $stats->remove();
  }

  public function testMissingData() {
    $now = new DateTimeImmutable();
    $from = $now->modify('today');
    $to = $now->modify('tomorrow');

    $stats = new KirbyStats();
    $data = $stats->data($from, $to, Interval::HOUR);
    $time = Interval::startOf(Interval::HOUR, $now)->getTimestamp();
    // Expect 24 missing entries when retrieving data for one day.
    $this->assertEquals($data[$time]['missing'], true);
    $this->assertEquals(count($data), 24);

    $stats->remove();
  }
}