<?php
// Demo using default error reporting and "the beep"
// Run this and forget the required -r flag.
// > php errors.php

require \dirname(__DIR__) . '/vendor/autoload.php';

$cmd = new Commando\Command();
$cmd->beepOnError();
$cmd->option('-r')->require();

echo "Argument -r: " . $cmd['-r'] . PHP_EOL;

// You can also fire off your own error by trapping an exception or 
// creating your own. This will print the message of the error to std out
// in bright red and the program will exit with a status of 1. 
// $cmd->error(new Exception("You have done something wrong"));
