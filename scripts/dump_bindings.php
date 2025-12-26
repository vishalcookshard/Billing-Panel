<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$bindings = array_keys($app->getBindings());
sort($bindings);
foreach ($bindings as $b) {
    echo $b . PHP_EOL;
}
