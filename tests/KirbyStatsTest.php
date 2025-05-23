<?php

use arnoson\KirbyStats\Interval;
use arnoson\KirbyStats\KirbyStats;

beforeEach(function () {
  $_SERVER = [
    'HTTP_USER_AGENT' =>
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
    'HTTP_HOST' => 'localhost:8888',
  ];
});

it('provides data', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('yesterday');
  $to = $now->modify('tomorrow');

  $stats = new KirbyStats(Interval::HOUR);
  $stats->processRequest('/test', null, $now);
  $stats->processRequest('/test', null, $now);
  $stats->processRequest('/test', null, $now);

  $data = $stats->data(Interval::HOUR, $from, $to);
  $time = Interval::startOf(Interval::HOUR, $now)->getTimestamp();
  expect($data[$time]['paths']['/test']['counters']['visits'])->toBe(3);

  $stats->remove();
});

it('handles data stored in a bigger interval than the current', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('yesterday');
  $to = $now->modify('tomorrow');

  $stats = new KirbyStats(Interval::HOUR);
  $stats->processRequest('/test', null, $now);

  $stats = new KirbyStats(Interval::DAY);
  for ($i = 0; $i < 24; $i++) {
    $stats->processRequest('/test', null, $now);
  }

  $data = $stats->data(Interval::HOUR, $from, $to);
  $time = Interval::startOf(Interval::HOUR, $now)->getTimestamp();
  // An hour with 1 visit, and a day with 24 visits. When retrieving the data
  // hourly, the day should be split up in 24 hour intervals with one visit
  // each. So all in all the hour should now have 2 visits.
  $this->assertEquals($data[$time]['paths']['/test']['counters']['visits'], 2);

  $stats->remove();
});

it('handles data stored in a smaller interval than the current', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('last week');
  $to = $now->modify('next week');

  $stats = new KirbyStats(Interval::WEEK);
  $stats->processRequest('/test', null, $now);

  $stats = new KirbyStats(Interval::DAY);
  $stats->processRequest('/test', null, $now);

  $data = $stats->data(Interval::WEEK, $from, $to);
  $time = Interval::startOf(Interval::WEEK, $now)->getTimestamp();
  // A week and a day with 1 visit each. When retrieving the data weekly, the
  // day should be added to the week.
  expect($data[$time]['paths']['/test']['counters']['visits'])->toBe(2);

  $stats->remove();
});

it('handles missing data', function () {
  $time = new DateTimeImmutable('today midnight');
  $from = $time->modify('-3 day');
  $to = $time->modify('+2 day');

  $stats = new KirbyStats(Interval::DAY);
  $stats->processRequest('/test', null, $time->modify('-2 day'));
  $stats->processRequest('/test', null, $time);

  $data = $stats->data(Interval::DAY, $from, $to);

  // The data starts tree days ago and because at this time
  // data recording hasn't started yet we expect the first day to be missing.
  expect($data[$from->getTimestamp()]['missing'])->toBeTrue();

  // We expect one visit today and one visit two days ago. The day in between
  // should be empty.
  $timeStamp = $time->getTimestamp();
  expect($data[$timeStamp]['paths']['/test']['counters']['visits'])->toBe(1);

  $timeStamp = $time->modify('-2 days')->getTimestamp();
  expect($data[$timeStamp]['paths']['/test']['counters']['visits'])->toBe(1);

  // Data ends tomorrow and because the date is in the future we expect it also
  // to be missing.
  expect($data[$time->modify('+1 day')->getTimestamp()]['missing'])->toBeTrue();

  $stats->remove();
});
