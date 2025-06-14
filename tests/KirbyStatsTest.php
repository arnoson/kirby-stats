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

it('provides data', function () {
  $now = new DateTimeImmutable();

  $time1 = $now->modify('8 AM');
  $timestamp1 = Interval::HOUR->startOf($time1)->getTimestamp();

  $time2 = $now->modify('10 AM');
  $timestamp2 = Interval::HOUR->startOf($time2)->getTimestamp();

  $from = $time1->modify('yesterday');
  $to = $time1->modify('tomorrow');

  // 2 visits and 3 additional views at 8 AM
  toggleVisit(true);
  KirbyStats::processRequest('page://test', $time1);
  KirbyStats::processRequest('page://test', $time1);
  toggleVisit(false);
  KirbyStats::processRequest('page://test', $time1);
  KirbyStats::processRequest('page://test', $time1);

  // 1 visit and 1 additional view at 10 AM
  toggleVisit(true);
  KirbyStats::processRequest('page://test', $time2);
  toggleVisit(false);
  KirbyStats::processRequest('page://test', $time2);

  $data = KirbyStats::data($from, $to);
  $page = $data['page://test'];

  expect($page['traffic'][$timestamp1])->toMatchArray([
    'views' => 4,
    'visits' => 2,
    'label' => '08:00',
  ]);

  expect($page['traffic'][$timestamp2])->toMatchArray([
    'views' => 2,
    'visits' => 1,
    'label' => '10:00',
  ]);

  expect($page['meta'])->toMatchArray([
    'browser' => ['Firefox' => 3],
    'os' => ['Windows' => 3],
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
  KirbyStats::processRequest('page://test', $time);
  // ... and a day with 24 visits.
  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  for ($i = 0; $i < 24; $i++) {
    KirbyStats::processRequest('page://test', $time);
  }
  // When retrieving the data hourly, the day should be split up in 24 hour
  // intervals with one visit each. So all in all the hour should now have 2
  // visits.
  $data = KirbyStats::data($from, $to);
  expect($data['page://test']['traffic'][$timestamp]['visits'])->toBe(2);
});

it('handles data stored in a smaller interval than the current', function () {
  $now = new DateTimeImmutable();
  $from = $now->modify('last week');
  $to = $now->modify('next week');

  $time = $now->modify('8 AM');
  $timestamp = Interval::WEEK->startOf($time)->getTimestamp();

  // A week with 1 visit...
  KirbyStats::mockOptions(['interval' => ['traffic' => 'week']]);
  KirbyStats::processRequest('page://test', $time);
  // ... and a day with 1 visit.
  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  KirbyStats::processRequest('page://test', $time);
  // When retrieving the data weekly, the day should be added to the week.
  $data = KirbyStats::data($from, $to, Interval::WEEK);
  expect($data['page://test']['traffic'][$timestamp]['visits'])->toBe(2);
});

it('handles missing data', function () {
  $today = new DateTimeImmutable('today midnight');
  $from = $today->modify('-3 day');
  $to = $today->modify('+2 day');

  KirbyStats::mockOptions(['interval' => ['traffic' => 'day']]);
  KirbyStats::processRequest('page://test', $today->modify('-2 day'));
  KirbyStats::processRequest('page://test', $today);

  $data = KirbyStats::data($from, $to, Interval::DAY);
  $traffic = $data['page://test']['traffic'];

  // We expect one visit today and one visit two days ago. The day in between
  // should be empty.
  $timestamp = $today->modify('-2 days')->getTimestamp();
  expect($traffic[$timestamp]['visits'])->toBe(1);

  $timestamp = $today->modify('-1 days')->getTimestamp();
  expect($traffic[$timestamp]['visits'])->toBe(0);

  $timestamp = $today->getTimestamp();
  expect($traffic[$timestamp]['visits'])->toBe(1);

  // // Data ends tomorrow and because the date is in the future we expect it also
  // // to be missing.
  // $timestamp = $today->modify('+1 day')->getTimestamp();
  // expect($traffic[$timestamp]['missing'])->toBeTrue();
});

it('returns the time of the first data', function () {
  $now = new DateTimeImmutable();
  $oneWeekAgo = $now->modify('-1 week');
  KirbyStats::processRequest('page://test', $oneWeekAgo);
  KirbyStats::processRequest('page://test', $now);
  expect(KirbyStats::getFirstTime())->toEqual(
    Interval::HOUR->startOf($oneWeekAgo)
  );
});
