<?php
// Clearer definition of Arguments vs. Flags

require dirname(__DIR__) . '/vendor/autoload.php';

// v0.2.0 started to add a clearer definition between "flag" type options
// and "argument" type options for those that may prefer it.
// In Commando, flags are options that require a name when they are being
// specified on the command line. Arguments are options that are not named in
// this way. In the example below, '-f' and '--long' are described as "flags"
// type options in Commando terms with the values 'value1' and 'value2'
// respectively, whereas value3, value4, and value5 are described as "argument"
// type options.
// php argumentsVsFlags.php -f value1 --long value2 value3 value4 value5

$cmd = new Commando\Command();
$cmd
    ->flag('f')
    ->flag('l')
        ->aka('long')
    ->argument()
    ->argument()
    ->argument();

var_dump($cmd->getArgumentValues());
var_dump($cmd->getFlagValues());

// This is equivalent to...

// $cmd = new Commando\Command();
// $cmd
//     ->option('f')
//     ->option('l')
//         ->aka('long')
//     ->option()
//     ->option()
//     ->option();
