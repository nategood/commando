# Commando

## An Elegant PHP CLI Library

[![Build Status](https://secure.travis-ci.org/nategood/commando.png?branch=master)](http://travis-ci.org/nategood/commando)

Commando is a PHP command line interface library that beautifies and simplifies writing PHP scripts intended for command line use.

## Why?

PHP's `$argv` magic variable and global `$_SERVER['argv']` make me cringe, [`getopt`](http://php.net/manual/en/function.getopt.php) isn't all that much better, and most other PHP CLI libraries are far too bloated for many cases. Commando gets down to business without a ton of overhead, removes the common boilerplate stuff when it comes to handling CLI input, all while providing a clean and readable interface.

## Installation

_Commando requires that you are running PHP 8.1 or higher._

Commando is [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant and can be installed using [Composer](http://getcomposer.org/). Add `nategood/commando` to your `composer.json`

    "require": {
        "nategood/commando": "*"
    }

If you're new to Composer...

- [Download and build Composer](http://getcomposer.org/download/)
- Make it [globally accessible](http://getcomposer.org/doc/00-intro.md#globally)
- `cd` to your the directory where you'll be writing your Commando script and run `composer install`

_Currently installing via Composer is the only supported option._

## Example

Here is an example of a PHP Commando script that gives a decent tour of Commando's features. Let's say it is in a file called `hello.php`.

```php
<?php

require_once 'vendor/autoload.php';

$hello_cmd = new Commando\Command();

// Define first option
$hello_cmd->option()
    ->require()
    ->describedAs('A person\'s name');

// Define a flag "-t" a.k.a. "--title"
$hello_cmd->option('t')
    ->aka('title')
    ->describedAs('When set, use this title to address the person')
    ->must(function($title) {
        $titles = array('Mister', 'Mr', 'Misses', 'Mrs', 'Miss', 'Ms');
        return in_array($title, $titles);
    })
    ->map(function($title) {
        $titles = array('Mister' => 'Mr', 'Misses' => 'Mrs', 'Miss' => 'Ms');
        if (array_key_exists($title, $titles))
            $title = $titles[$title];
        return "$title. ";
    });

// Define a boolean flag "-c" aka "--capitalize"
$hello_cmd->option('c')
    ->aka('capitalize')
    ->aka('cap')
    ->describedAs('Always capitalize the words in a name')
    ->boolean();

// Define an incremental flag "-e" aka "--educate"
$hello_cmd->option('e')
    ->aka('educate')
    ->map(function($value) {
        $postfix = array('', 'Jr', 'esq', 'PhD');
        return $postfix[$value] === '' ? '' : " {$postfix[$value]}";
    })
    ->count(4);

$name = $hello_cmd['capitalize'] ? ucwords($hello_cmd[0]) : $hello_cmd[0];

echo "Hello {$hello_cmd['title']}$name{$hello_cmd['educate']}!", PHP_EOL;
```

Running it:

    > php hello.php Nate
    Hello, Nate!

    > php hello.php --capitalize nate
    Hello, Nate!

    > php hello.php -c -t Mr 'nate good'
    Hello, Mr. Nate Good!

    > php hello.php -ceet Mr 'nate good'
    Hello, Mr. Nate Good esq!

Things to note:

- Commando implements ArrayAccess so it acts much like an array when you want to retrieve values for it
- For "anonymous" (i.e. not a named flag) arguments, we access them based on their numeric index
- We can access option values in an array via a flags name OR its alias
- We can use closures to perform validation and map operations right as part of our option definition

## Baked in Help

Commando has automatic `--help` support built in. Calling your script with this flag will print out a pretty help page based on your option definitions and Commando settings. If you define an option with the alias of 'help', it will override this built in support.

![help screenshot](http://cl.ly/image/1y3i2m2h220u/Screen%20Shot%202012-08-19%20at%208.54.49%20PM.png)

## Error Messaging

By default, Commando will catch Exceptions that occur during the parsing process. Instead, Commando prints a formatted, user-friendly error message to standard error and exits with a code of 1. If you wish to have Commando throw Exceptions in these cases, call the `doNotTrapErrors` method on your Command instance.

![error screenshot](http://f.cl.ly/items/150H2d3x0l3O3J0s3i1G/Screen%20Shot%202012-08-19%20at%209.58.21%20PM.png)

## Command Methods

These options work on the "command" level.

### `useDefaultHelp (bool help)`

The default behavior of Commando is to provide a `--help` option that spits out a useful help page generated off of your option definitions. Disable this feature by calling `useDefaultHelp(false)`

### `setHelp (string help)`

Text to prepend to the help page. Use this to describe the command at a high level and maybe some examples usages of the command.

### `printHelp()`

Print the default help for the command. Useful if you want to output help if no arguments are passed.

### `beepOnError (bool beep=true)`

When an error occurs, print character to make the terminal "beep".

### `getOptions`

Return an array of `Options` for each options provided to the command.

### `getFlags`

Return an array of `Options` for only the flags provided to the command.

### `getArguments`

Return an array of `Options` for only the arguments provided to the command. The order of the array is the same as the order of the arguments.

### `getFlagValues`

Return associative array of values for arguments provided to the command. E.g. `array('f' => 'value1')`.

### `getArgumentValues`

Return array of values for arguments provided to the command. E.g. `array('value1', 'value2')`.

## Command Option Definition Methods

These options work on the "option" level, even though they are chained to a `Command` instance

### `option (mixed $name = null)`

Aliases: `o`

Define a new option. When `name` is set, the option will be a named "flag" option. Can be a short form option (e.g. `f` for option `-f`) or long form (e.g. `foo` for option --foo). When no `name` is defined, the option is an anonymous argument and is referenced in the future by its position.

### `flag (string $name)`

Same as `option` except that it can only be used to define "flag" type options (a.k.a. those options that must be specified with a -flag on the command line).

### `argument ()`

Same as `option` except that it can only be used to define "argument" type options (a.k.a those options that are specified WITHOUT a -flag on the command line).

### `alias (string $alias)`

Aliases: `a`, `aka`

Add an alias for a named option. This method can be called multiple times to add multiple aliases.

### `description (string $description)`

Aliases: `d`, `describe`, `describedAs`

Text to describe this option. This text will be used to build the "help" page and as such, it is end user facing.

### `require (bool $require)`

Aliases: `r`, `required`

Require that this flag is specified

### `needs (string|array $options)`

Aliases: none

Require that other $options be set for this option to be used.

### `must (Closure $rule)`

Aliases: _N/A_

Define a rule to validate input against. Takes function that accepts a string $value and returns a boolean as to whether or not $value is valid.

### `map (Closure $map)`

Aliases: `cast`, `castTo`

Perform a map operation on the value for this option. Takes function that accepts a string $value and return mixed (you can map to whatever you wish).

### `reduce (Closure $reducer [, mixed $seed])`

Aliases: `list`, `each`, `every`

Execute an accumulator/reducer function on every instance of the option in the command. Takes an accumulator function, and returns mixed (you can return any value). If you also supply a map for the option the map will execute on every value before it is passed to the accumulator function. If `$seed` value is supplied, this will be used as the default value.

Signature: `function(mixed $accumulated, mixed $value) : mixed`

- `$accumulated`: null|Option::default|mixed (the last value returned from the function, the option default value, or null.)
- `$value`: mixed (the value that comes after the option. if map is supplied, the value returned from the map function.)
- `return`: mixed (anything you want. The last value returned becomes the value of the Option after parsing.)

### `referToAs (string $name)`

Aliases: `title`, `referredToAs`

Add a name to refer to an argument option by. Makes the help docs a little cleaner for anonymous "argument" options.

### `boolean ()`

Aliases: _N/A_

Specifices that the flag is a boolean type flag.

### `increment (int $max)`

Aliases: `i`, `count`, `repeats`, `repeatable`

Specifies that the flag is a counter type flag. The value of the flag will be incremented up to the value of `$max` for each time the flag is used in the command. Options that are set to `increment` or `boolean` types can be grouped together.

### `default (mixed $defaultValue)`

Aliases: `defaultsTo`

If the value is not specified, default to `$defaultValue`.

In the case of `boolean()` type flags, when the flag is present, the value of this option the negation of `$defaultValue`. That is to say, if you have a flag -b with a default of `true`, when -b is present as a command line flag, the value of the option will be `false`.

### `file ()`

Aliases: `expectsFile`

The value specified for this option must be a valid file path. When used relative paths will be converted into fully quantify file paths and globbing is also optionally supported. See the file.php example.

### `boolean ()`

Aliases: _N/A_

Specifices that the flag is a boolean type flag.

### `default (mixed $defaultValue)`

Aliases: `defaultsTo`

If the value is not specified, default to `$defaultValue`.

In the case of `boolean()` type flags, when the flag is present, the value of this option the negation of `$defaultValue`. That is to say, if you have a flag -b with a default of `true`, when -b is present as a command line flag, the value of the option will be `false`.

### `file ()`

Aliases: `expectsFile`

The value specified for this option must be a valid file path. When used relative paths will be converted into fully quatified file paths and globbing is also optionally supported. See the file.php example.

## Contributing

Commando highly encourages sending in pull requests. When submitting a pull request please:

- All pull requests should target the `dev` branch (not `master`)
- Make sure your code follows the coding standards laid out in [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
- Make sure you add appropriate test coverage for your changes
- Run all unit tests in the test directory via `phpunit ./tests`
- Include commenting where appropriate and add a descriptive pull request message

## Inspiration

- [Commander](https://github.com/visionmedia/commander/)
- [Optimist](https://github.com/substack/node-optimist)

Released under MIT license.

## Change Log

### v1.0.1

- Add support for negative numbers not to be misinterpreted as bad options.

### v1.0.0

- Dropping support for anything before PHP 8.1

### v0.4.0

- Dropping support for 5.4 and 5.5, bumping minor version number
- [PR #93](https://github.com/nategood/commando/pull/93) FEATURE Add `reducer` option
- [PR #95](https://github.com/nategood/commando/pull/95) FIX Remove tput call on Windows OS
- [PR #101](https://github.com/nategood/commando/pull/101) FIX Only evaluate 'needs' constraints of an option if that option is actually used
- [PR #76](https://github.com/nategood/commando/pull/76/) FIX Fix non-empty getArgumentValues array when using anonymous args

### v0.3.0

- Dropped PHP 5.3

### v0.2.9

- PR #63 FEATURE incremental flags
- PR #60 MINOR getDescription method

### v0.2.8

- Bug fix for #34

### v0.2.7

- `getOptions` added (along with some better documentation)

### v0.2.6

- Adds support for "needs" to define dependencies between options (thanks @enygma) [PR #31](https://github.com/nategood/commando/pull/31)
- Fixes issue with long-argument-names [Issue #30](https://github.com/nategood/commando/issues/30)

### v0.2.5

- Fixed up default values for boolean options, automatically default boolean options to false (unlikely, but potentially breaking change) [PR #19](https://github.com/nategood/commando/pull/19)

### v0.2.4

- Added ability to define default values for options

### v0.2.3

- Improved Help Formatting [PR #12](https://github.com/nategood/commando/pull/12)

### v0.2.2

- Bug fix for printing double help [PR #10](https://github.com/nategood/commando/pull/10)

### v0.2.1

- Adds support for requiring options to be valid file paths or globs
- Returns a fully qualified file path name (e.g. converts relative paths)
- Returns an array of file paths in the case of globbing
- See the file.php example in the examples directory

### v0.2.0

The primary goal of this update was to better delineate between flag options and argument options. In Commando, flags are options that we define that require a name when they are being specified on the command line. Arguments are options that are not named in this way. In the example below, '-f' and '--long' are described as "flags" type options in Commando terms with the values 'value1' and 'value2' respectively, whereas value3, value4, and value5 are described as "argument" type options.

```
php command.php -f value1 --long value2 value3 value4 value5
```

- Added Command::getArguments() to return an array of `Option` that are of the "argument" type (see argumentsVsFlags.php example)
- Added Command::getFlags() to return an array of `Option` that are of the "flag" type (see argumentsVsFlags.php example)
- Added Command::getArgumentValues() to return an array of all the values for "arguments"
- Added Command::getFlagValues() to return an array of all values for "flags"
- Command now implements Iterator interface and will iterator over all options, starting with arguments and continuing with flags in alphabetical order
- Can now define options with Command::flag($name) and Command::argument(), in addition to Command::option($name)
- Added ability to add a "title" to refer to arguments by, making the help docs a little cleaner (run help.php example)
- Cleaned up the generated help docs
- Bug fix for additional colorized red line when an error is displayed

### v0.1.4

- Bug fix for options values with multiple words

### v0.1.3

- Beep support added to Terminal
- Commando::beepOnError() added

### v0.1.2

- Terminal updated to use tput correctly
