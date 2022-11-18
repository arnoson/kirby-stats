<h1><?= $page->title() ?></h1>

<?php dump((new \arnoson\KirbyStats\KirbyStats())->period('day')); ?>