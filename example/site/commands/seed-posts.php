<?php

use Kirby\Toolkit\Str;

return [
  'description' => 'Create dummy blog posts for testing',
  'args' => [],
  'command' => function ($cli) {
    $blog = page('blog');
    if (!$blog) {
      $cli->error('Blog page not found!');
      return;
    }

    kirby()->impersonate('kirby');

    $titles = [
      'Building a Kirby Panel Plugin',
      'Understanding Kirby Blueprints',
      'Creating Custom Page Methods',
      'Optimizing Kirby Performance',
      'Kirby Collections and Filtering',
      'Working with Kirby Templates',
      'Mastering Kirby Hooks',
      'Kirby Multi-language Setup',
      'Advanced Kirby Query API',
      'Custom Kirby Field Methods',
    ];

    $texts = [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
      'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
      'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
      'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    ];

    $categories = ['Tutorial', 'News', 'Guide', 'Tips', 'Case Study'];

    for ($i = 1; $i <= 60; $i++) {
      try {
        $title = $titles[array_rand($titles)];
        $post = $blog
          ->createChild([
            'slug' => Str::slug($title) . '-' . $i,
            'template' => 'post',
            'content' => [
              'title' => $title,
              'date' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
              'category' => $categories[array_rand($categories)],
              'text' => $texts[array_rand($texts)],
            ],
          ])
          ->changeStatus('listed');

        $cli->success('Created post: ' . $post->url());
      } catch (Exception $e) {
        $cli->error("Failed to create post $i: " . $e->getMessage());
      }
    }

    $cli->success('Finished creating blog posts!');
  },
];
