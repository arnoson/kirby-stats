<?php

use arnoson\KirbyStats\Interval;
use arnoson\KirbyStats\KirbyStats;
use Kirby\Cms\App;

beforeEach(function () {
  KirbyStats::mockOptions([]);
  $_SERVER = [
    'HTTP_USER_AGENT' =>
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0',
    'HTTP_HOST' => 'localhost:8888',
  ];
});

afterEach(function () {
  KirbyStats::clear();
});

function toggleVisit(bool $isVisit) {
  if ($isVisit) {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = null;
  } else {
    $_SERVER['HTTP_IF_MODIFIED_SINCE'] = (new DateTimeImmutable())
      ->setTime(0, 0, 1)
      ->format('D, d M Y H:i:s \G\M\T');
  }
}

function request(
  string $uuid,
  DateTimeImmutable $date,
  $isVisit = false,
  $isVisitor = false
) {
  toggleVisit($isVisitor);
  KirbyStats::processRequest('site://', $date);
  toggleVisit($isVisit);
  KirbyStats::processRequest($uuid, $date);
}

it('provides data', function () {
  $now = new DateTimeImmutable();

  $time1 = $now->modify('8 AM');
  $timestamp1 = Interval::HOUR->startOf($time1)->getTimestamp();

  $time2 = $now->modify('10 AM');
  $timestamp2 = Interval::HOUR->startOf($time2)->getTimestamp();

  $from = $time1->modify('today');
  $to = $time1->modify('tomorrow');

  // 1 Visitor with 1 visit and two additional views to page A at 8 AM
  request('page://a', $time1, isVisitor: true, isVisit: true);
  request('page://a', $time1);
  request('page://a', $time1);

  // 1 Visitor with 1 visit and 1 additional view to page B at 8 AM
  request('page://b', $time1, isVisitor: true, isVisit: true);
  request('page://b', $time1);

  // 1 Visitor with 1 visit to page A at 10 AM
  request('page://a', $time2, isVisitor: true, isVisit: true);

  // 1 Visitor with 1 visit to page B at 10 AM
  request('page://b', $time2, isVisitor: true, isVisit: true);

  // Page A
  $data = KirbyStats::data($from, $to, Interval::HOUR, 'page://a');
  expect($data['traffic'][$timestamp1])->toMatchArray([
    'views' => 3,
    'visits' => 1,
    'label' => '08:00',
  ]);
  expect($data['traffic'][$timestamp2])->toMatchArray([
    'views' => 1,
    'visits' => 1,
    'label' => '10:00',
  ]);
  expect($data['meta'])->toMatchArray([
    'browser' => ['Firefox' => 2],
    'os' => ['Windows' => 2],
  ]);

  // Page B
  $data = KirbyStats::data($from, $to, Interval::HOUR, 'page://b');
  expect($data['traffic'][$timestamp1])->toMatchArray([
    'views' => 2,
    'visits' => 1,
    'label' => '08:00',
  ]);
  expect($data['traffic'][$timestamp2])->toMatchArray([
    'views' => 1,
    'visits' => 1,
    'label' => '10:00',
  ]);
  expect($data['meta'])->toMatchArray([
    'browser' => ['Firefox' => 2],
    'os' => ['Windows' => 2],
  ]);

  // Site
  $data = KirbyStats::data($from, $to, Interval::HOUR, 'site://');
  expect($data['traffic'][$timestamp1])->toMatchArray([
    'views' => 5,
    'visits' => 2,
    'label' => '08:00',
  ]);
  expect($data['traffic'][$timestamp2])->toMatchArray([
    'views' => 2,
    'visits' => 2,
    'label' => '10:00',
  ]);
  expect($data['meta'])->toMatchArray([
    'browser' => ['Firefox' => 4],
    'os' => ['Windows' => 4],
  ]);
  expect($data['totalTraffic'])->toMatchArray([
    ['id' => 'a', 'name' => 'Page A', 'views' => 4, 'visits' => 2],
    ['id' => 'b', 'name' => 'Page B', 'views' => 3, 'visits' => 2],
  ]);
});

it('handles data stored in a bigger interval than the current', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('yesterday');
  $to = $now->modify('tomorrow');

  $time = $now->modify('8 AM');
  $timestamp = Interval::HOUR->startOf($time)->getTimestamp();

  // An hour with 1 visit ...
  KirbyStats::mockOptions(['interval' => ['traffic' => 'hour']]);
  request('page://test', $time, isVisit: true);
  // ... and a day with 24 visits.
  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  for ($i = 0; $i < 24; $i++) {
    request('page://test', $time, isVisit: true);
  }
  // When retrieving the data hourly, the day should be split up in 24 hour
  // intervals with one visit each. So all in all the hour should now have 2
  // visits.
  $data = KirbyStats::data($from, $to, Interval::HOUR, 'page://test');
  expect($data['traffic'][$timestamp]['visits'])->toBe(2);
});

it('handles data stored in a smaller interval than the current', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('last week');
  $to = $now->modify('next week');

  $time = $now->modify('8 AM');
  $timestamp = Interval::WEEK->startOf($time)->getTimestamp();

  // A week with 1 visit...
  KirbyStats::mockOptions(['interval' => ['traffic' => 'week']]);
  request('page://test', $time, isVisit: true);
  // ... and a day with 1 visit.
  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  request('page://test', $time, isVisit: true);
  // When retrieving the data weekly, the day should be added to the week.
  $data = KirbyStats::data($from, $to, Interval::WEEK, 'page://test');
  expect($data['traffic'][$timestamp]['visits'])->toBe(2);
});

it('handles missing data', function () {
  $today = new DateTimeImmutable('today midnight');
  $from = $today->modify('-3 day');
  $to = $today->modify('+2 day');

  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  request('page://test', $today->modify('-2 day'), isVisit: true);
  request('page://test', $today, isVisit: true);

  $data = KirbyStats::data($from, $to, Interval::DAY, 'page://test');
  // We expect one visit today and one visit two days ago. The day in between
  // should be empty.
  $timestamp = $today->modify('-2 days')->getTimestamp();
  expect($data['traffic'][$timestamp]['visits'])->toBe(1);

  $timestamp = $today->modify('-1 days')->getTimestamp();
  expect($data['traffic'][$timestamp]['visits'])->toBe(0);

  $timestamp = $today->getTimestamp();
  expect($data['traffic'][$timestamp]['visits'])->toBe(1);

  // Data ends tomorrow and because the date is in the future we expect it also
  // to be missing.
  $timestamp = $today->modify('+1 day')->getTimestamp();
  expect($data['traffic'][$timestamp]['visits'])->toBeNull();
});

it('returns the time of the first data', function () {
  $now = new DateTimeImmutable();
  $oneWeekAgo = $now->modify('-1 week');
  request('page://test', $oneWeekAgo);
  request('page://test', $now);
  expect(KirbyStats::getFirstTime())->toEqual(
    Interval::HOUR->startOf($oneWeekAgo)
  );
});
