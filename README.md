# Commando
## An Elegant PHP CLI Library

Commando is a PHP command line interface library that beautifies and simplifies writing PHP scripts intended for command line use.

## Why?

PHP's `$argv` magic variable and global `$_SERVER['argv']` make me cringe, [`getopt`](http://php.net/manual/en/function.getopt.php) all that much better, and most other PHP CLI libraries are far too OOP bloated.  Commando gets down to business without a ton of overhead, removes the common boilerplate stuff when it comes to handling cli input, all while providing a clean and readable interface.

# Example

Here is a trivial example of a PHP Commando script packed with some of the cool stuff Commando supports.  Let's say it is in a file called `hello.php`.

``` php
<?php

$salutations = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms', 'Doctor' => 'Dr');

$hello_cmd = new Commando\Command();
$hello_cmd
  // Define first option
  ->option()
    ->required()
    ->describedAs('A person\'s name')
  // Define a flag "-s" a.k.a. "--salutation"
  ->option('s')
    ->alias('salutation')
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
    ->alias('capitalize')
    ->describedAs('Always capitalize the words in a name')
    ->boolean();

$name = $hello_cmd[0];

if ($hello_cmd['capitlize'])
    $name = ucwords($hello_cmd[0]);

echo "Hello, {$hello_cmd['salutation']}{$name}!";
```

Running it:

    > php hello.php Nate
    Hello, Nate!

    > php hello.php --capitalize nate
    Hello, Nate!

    > php hello.php -c -s Mr 'nate good'
    Hello, Mr. Nate Good!

Things to note:

 - Commando implements ArrayAccess so it acts much like an array when you want to retrieve values for it
 - For "annonymous" (i.e. not a named flag) arguments, we access them based on their numeric index
 - We can access option values in an array via a flags name OR it's alias
 - We can use closures to perform validation and map operations right as part of our option definition

## Command Definition Options

These options work on the "command" level

## Option Definition Options

These options work on the "option" level, even though they are chained to a `Command` instance

### `option` (mixed name = null)

Aliases: o

Define a new option.  When `name` is set, the option will be a named "flag" option and must only be a single char (e.g. `f` for option `-f`).  When no `name` is defined, the option is an annonymous argument and is referenced in the future by it's position.

### `alias` (string alias)

Aliases: a, aka

Add a long form (e.g. --example) alias for a named option.  This method can be called multiple times to add multiple aliases.

### `description` (string description)

Aliases: d, describe, describedAs

Text to describe this option.  This text will be used to build the "help" page and as such, it is end user facing.

### `require`

Aliases: r, required

Require that this flag is specified

### `must` (Closure rule)

Aliases: _N/A_

Define a rule to validate input against.  Takes function that accepts a string $value and returns a boolean as to whether or not $value is valid.

### `map` (Closure map)

Aliases: cast, castTo

Perform a map operation on the value for this option.  Takes function that accepts a string $value and return mixed (you can map to whatever you wish).

## Installation

Httpful is [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant and can be installed using [Composer](http://getcomposer.org/).  Add `nategood/commando` to your `composer.json`

    "require": {
        "nategood/commando": "*"
    }

If you're new to Composer...

 - [Download and build Composer](http://getcomposer.org/download/)
 - Make it [globally accessible](http://getcomposer.org/doc/00-intro.md#globally)
 - `cd` to your the directory where you'll be writing your Commando script and run `composer install`

*Currently installing via Composer is the only option (phar build coming soon).*

## Baked in Help

Commando has automatic `--help` support built in.  Calling your script with this flag will print out a pretty help page based on your option definitions and Commando settings.  If you define an option with the alias of 'help', it will override this built in support.

## Trainwreck

If you, [like Martin](http://www.amazon.com/gp/product/0132350882), are of the _train_ of thought that the chaining pattern is a "trainwreck", Commando also works fine without chaining.  Commando reads much nicer with the chaining, however, chaining may require a bit more work when debugging and additional indentation diligence.

``` php
<?php
// Commando without using chaining
$cmd = new Commando();
$option = $cmd->option('f');
$option->alias('foo');
$option2 = $cmd->option('g');
```

# Contributing

Commando highly encourages sending in pull requests.  When submitting a pull request please:

 - All pull requests should target the `dev` branch (not `master`)
 - Make sure your code follows the coding standards laid out in [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
 - Make sure you add appropriate test coverage for your changes
 - Run all unit tests in the test directory via `phpunit ./tests`
 - Include commenting where appropriate and add a descriptive pull request message

## Inspiration

 - [Commander](https://github.com/visionmedia/commander/)
 - [Optimist](https://github.com/substack/node-optimist)

Released under MIT license.