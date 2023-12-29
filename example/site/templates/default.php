<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <?= js('assets/stats.js', ['async', 'defer']) ?>
</head>
<body>
  <h1><?= $page->title() ?></h1>
  <ul>
    <li><a href="/">Home</a></li>
    <li><a href="/test">Test</a></li>
  </ul>
</body>
</html>