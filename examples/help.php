<?php

// An example just to demo what the help output looks like
// You would rarely if ever actually call "printHelp()" in
// production.  Typically the user would initiate this via
// the user specifying the --help option

require \dirname(__DIR__) . '/vendor/autoload.php';

use Commando\Command;

$tokens = array('mycmd');
$cmd = new Command($tokens);
$cmd
    ->setHelp('This is a great command it.  It can be used by calling `mycmd <argument>`.')
    ->option()->referToAs('the first arg')->describeAs('mycmd takes an optional single argument. e.g. mycmd argument0')
    ->option('a')->description("Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.")
    ->option('b')->boolean()->describeAs("A boolean option.")
    ->option('c')->aka('foo')->describeAs("Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt.")->required();

$cmd->printHelp();