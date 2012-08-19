<?php

/**
 * Example showing several of the option definition options in use, including
 * map, must, required, alias (a.k.a. aka), desription (aka describedAs), and
 * boolean.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Commando\Commando;

$salutations = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms', 'Doctor' => 'Dr');

$hello_cmd = new Commando();
$hello_cmd
  // Define first option
  ->option()
    ->required()
    ->describedAs('A person\'s name')
  // Define a flag "-s" a.k.a. "--salutation"
  ->option('s')
    ->aka('salutation')
    ->describedAs('When set, use this salutation to address the person')
    ->must(function($salutation) {
        return array_key_exists($salutation, $salutations) || in_array($salutation, $salutations);
    })
    ->map(function($salutation) {
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

$name = $hello_cmd[0];

if ($hello_cmd['capitlize'])
    $name = ucwords($hello_cmd[0]);

echo "Hello {$hello_cmd['salutation']}$name";