<?php

/**
 * Example showing several of the option definition options in use, including
 * map, must, required, alias (a.k.a. aka), desription (aka describedAs), and
 * boolean.
 *
 * Usage
 * > php greet.php nate
 * Hello nate!
 *
 * > php green.php --capitalize nate
 * Hello Nate!
 *
 * > php green.php -c 'nate good'
 * Hello Nate Good!
 *
 * > php greet.php -c -s Mr 'nate good'
 * Hello Mr. Nate Good!
 *
 * > php greet.php -c -s Mister 'nate good'
 * Hello Mr. Nate Good!
 *
 * > php greet.php
 * # Throws an Exception because the command requires at least one
 * # annonymous option
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Commando\Command;

$hello_cmd = new Command();
$hello_cmd
  // Define first option
  ->option()
    ->require()
    ->describedAs('A person\'s name')
  // Define a flag "-s" a.k.a. "--salutation"
  ->option('s')
    ->aka('salutation')
    ->describedAs('When set, use this salutation to address the person')
    ->must(function($salutation) {
        $salutations = array('Mister', 'Mr', 'Misses', 'Mrs', 'Miss', 'Ms');
        return in_array($salutation, $salutations);
    })
    ->map(function($salutation) {
        $salutations = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms');
        if (array_key_exists($salutation, $salutations))
            $salutation = $salutations[$salutation];
        return "$salutation. ";
    })
  // Define a boolean flag "-c" aka "--capitalize"
  ->option('c')
    ->aka('capitalize')
    ->aka('cap')
    ->describedAs('Always capitalize the words in a name')
    ->boolean();

$name = $hello_cmd['capitalize'] ? ucwords($hello_cmd[0]) : $hello_cmd[0];

echo "Hello {$hello_cmd['salutation']}$name!", PHP_EOL;