<?php

/**
 * Example showing several of the option definition options in use, including
 * map, must, required, alias (a.k.a. aka), description (aka describedAs), and
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
 * > php greet.php -cs Mr 'nate good'
 * Hello Mr. Nate Good!
 *
 * > php greet.php -cs Mister 'nate good'
 * Hello Mr. Nate Good!
 * 
 * php greet.php -ceet Mr 'nate good'
 * Hello Mr. Nate Good esq!
 *
 * > php greet.php
 * # Throws an Exception because the command requires at least one
 * # anonymous option
 */

require \dirname(__DIR__) . '/vendor/autoload.php';

use Commando\Command;

$hello_cmd = new Command();
$hello_cmd
  // Define first option
  ->option()
    ->require()
    ->title('name')
    ->describedAs('A person\'s name')
  // Define a flag "-s" a.k.a. "--title"
  ->option('t')
    ->aka('title')
    ->aka('long-title')
    ->describedAs('When set, use this title to address the person')
    ->must(function($title) {
        $titles = array('Mister', 'Mr', 'Misses', 'Mrs', 'Miss', 'Ms');
        return \in_array($title, $titles);
    })
    ->map(function($title) {
        $titles = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms');
        if (\array_key_exists($title, $titles))
            $title = $titles[$title];
        return "$title. ";
    })
  // Define a boolean flag "-c" aka "--capitalize"
  ->option('c')
    ->aka('capitalize')
    ->aka('cap')
    ->describedAs('Always capitalize the words in a name')
    ->boolean()
    
  ->option('e')
    ->aka('educate')
    ->map(function($value) {
        $postfix = array('', 'Jr', 'esq', 'PhD');
        return $postfix[$value] === '' ? '' : " {$postfix[$value]}";
    })
    ->count(4);

$name = $hello_cmd['capitalize'] ? \ucwords($hello_cmd[0]) : $hello_cmd[0];

echo "Hello {$hello_cmd['title']}$name{$hello_cmd['educate']}!", PHP_EOL;
