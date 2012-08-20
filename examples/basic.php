<?php
// Usage
// php basic.php test

require dirname(__DIR__) . '/vendor/autoload.php';

$cmd = new Commando\Command();

echo "Argument #1: " . $cmd[0] . PHP_EOL;