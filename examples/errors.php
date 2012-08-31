<?php
// Demo using default error reporting and "the beep"
// Run this and forget the required -r flag.
// > php errors.php

require dirname(__DIR__) . '/vendor/autoload.php';

$cmd = new Commando\Command();
$cmd->beepOnError();
$cmd->option('-r')->require();

echo "Argument -r: " . $cmd['-r'] . PHP_EOL;