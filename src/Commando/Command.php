<?php
/**
 * @author Nate Good <me@nategood.com>
 */

namespace Commando;

class Command implements \ArrayAccess, \Iterator
{
    const OPTION_TYPE_ARGUMENT  = 1; // e.g. foo
    const OPTION_TYPE_SHORT     = 2; // e.g. -u
    const OPTION_TYPE_VERBOSE   = 4; // e.g. --username

    private
        $current_option             = null,
        $name                       = null,
        $options                    = array(),
        $arguments                  = array(),
        $flags                      = array(),
        $nameless_option_counter    = 0,
        $tokens                     = array(),
        $help                       = null,
        $parsed                     = false,
        $use_default_help           = true,
        $trap_errors                = true,
        $beep_on_error              = true,
        $position                   = 0,
        $sorted_keys                = array();

    /**
     * @var array Valid "option" options, mapped to their aliases
     */
    public static $methods = array(

        'option' => 'option',
        'o' => 'option',

        'flag' => 'flag',
        'argument' => 'argument',

        'boolean' => 'boolean',
        'bool' => 'boolean',
        'b' => 'boolean',

        'require' => 'require',
        'required' => 'require',
        'r' => 'require',

        'alias' => 'alias',
        'aka' => 'alias',
        'a' => 'alias',

        'title' => 'title',
        'referToAs' => 'title',
        'referredToAs' => 'title',

        'describe' => 'describe',
        'd' => 'describe',
        'describeAs' => 'describe',
        'description' => 'describe',
        'describedAs' => 'describe',

        'map' => 'map',
        'mapTo' => 'map',
        'cast' => 'map',
        'castWith' => 'map',

        'must' => 'must',
    );

    public function __construct($tokens = null)
    {
        if (empty($tokens)) {
            $tokens = $_SERVER['argv'];
        }

        $this->setTokens($tokens);
    }

    /**
     * Factory style reads a little nicer
     * @param array $tokens defaults to $argv
     * @return Commando
     */
    public static function define($tokens = null)
    {
        return new Command($tokens);
    }

    /**
     * This is the meat of Command.  Any time we are operating on
     * an individual option for command (e.g. $cmd->option()->require()...)
     * it relies on this magic method.  It allows us to handle some logic
     * that is applicable across the board and also allows easy aliasing of
     * methods (e.g. "o" for "option")... since it is a CLI library, such
     * minified aliases would only be fitting :-).
     *
     * @param string $name
     * @param array $arguments
     * @return Command
     */
    public function __call($name, $arguments)
    {
        if (empty(self::$methods[$name])) {
            throw new \Exception(sprintf('Unknown function, %s, called', $name));
        }

        // use the fully quantified name, e.g. "option" when "o"
        $name = self::$methods[$name];

        // set the option we'll be acting on
        if (empty($this->current_option) && $name !== 'option' &&
                $name !== 'flag' && $name !== 'argument') {
            throw new \Exception(sprintf('Invalid Option Chain: Attempting to call %s before an "option" declaration', $name));
        }

        array_unshift($arguments, $this->current_option);
        $option = call_user_func_array(array($this, "_$name"), $arguments);

        return $this;
    }

    /**
     * @param Option|null $option
     * @param string|int name
     * @return Option
     */
    private function _option($option, $name = null)
    {
        // Is this a previously declared option?
        if (isset($name) && !empty($this->options[$name])) {
            $this->current_option = $this->getOption($name);
        } else {
            if (!isset($name)) {
                $name = $this->nameless_option_counter++;
            }
            $this->current_option = $this->options[$name] = new Option($name);
        }

        return $this->current_option;
    }

    /**
     * @param Option|null $option
     * @param string name
     * @return Option
     *
     * Like _option but only for named flags
     */
    private function _flag($option, $name)
    {
        if (isset($name) && is_numeric($name))
            throw new \Exception('Attempted to reference flag with a numeric index');
        return $this->_option($option, $name);
    }

    /**
     * @param Option|null $option
     * @param int $index [optional] only used when referencing an existing option
     * @return Option
     *
     * Like _option but only for annonymous arguments
     */
    private function _argument($option, $index = null)
    {
        if (isset($index) && !is_numeric($index))
            throw new \Exception('Attempted to reference argument with a string name');
        return $this->_option($option, $index);
    }

    /**
     * @param Option $option
     * @return Option
     */
    private function _boolean(Option $option, $boolean = true)
    {
        return $option->setBoolean($boolean);
    }

    /**
     * @param Option $option
     * @return Option
     */
    private function _require(Option $option, $require = true)
    {
        return $option->setRequired($require);
    }

    /**
     * @param Option $option
     * @param string $alias
     * @return Option
     */
    private function _alias(Option $option, $alias)
    {
        $this->options[$alias] = $this->current_option;
        return $option->addAlias($alias);
    }

    /**
     * @param Option $option
     * @param string $description
     * @return Option
     */
    private function _describe(Option $option, $description)
    {
        return $option->setDescription($description);
    }

    /**
     * @param Option $option
     * @param string $title
     * @return Option
     */
    private function _title(Option $option, $title)
    {
        return $option->setTitle($title);
    }

    /**
     * @param Option $option
     * @param \Closure $callback (string $value) -> boolean
     * @return Option
     */
    private function _must(Option $option, \Closure $callback)
    {
        return $option->setRule($callback);
    }

    /**
     * @param Option $option
     * @param \Closure $callback
     * @return Option
     */
    private function _map(Option $option, \Closure $callback)
    {
        return $option->setMap($callback);
    }


    public function useDefaultHelp($help = true)
    {
        $this->use_default_help = $help;
    }

    /**
     * Rare that you would need to use this other than for testing,
     * allows defining the cli tokens, instead of using $argv
     * @param array $cli_tokens
     * @return Command
     */
    public function setTokens(array $cli_tokens)
    {
        // todo also slice on "=" or other delimiters
        $this->tokens = $cli_tokens;
        return $this;
    }

    /**
     * @throws \Exception
     */
    private function parseIfNotParsed()
    {
        if ($this->isParsed()) {
            return;
        }
        $this->parse();
    }

    /**
     * @throws \Exception
     */
    public function parse()
    {
        try {
            $tokens = $this->tokens;
            // the executed filename
            $this->name = array_shift($tokens);

            $keyvals = array();
            $count = 0; // standalone argument count

            while (!empty($tokens)) {
                $token = array_shift($tokens);

                list($name, $type) = $this->_parseOption($token);

                if ($type === self::OPTION_TYPE_ARGUMENT) {
                    // its an argument, use an int as the index
                    $keyvals[$count] = $name;

                    // We allow for "dynamic" annonymous arguments, so we
                    // add an option for any annonymous arguments that
                    // weren't predefined
                    if (!$this->hasOption($count)) {
                        $this->options[$count] = new Option($count);
                    }

                    $count++;
                } else {
                    // Short circuit if the help flag was set and we're using default help
                    if ($this->use_default_help === true && $name === 'help') {
                        $this->printHelp();
                        exit;
                    }

                    $option = $this->getOption($name);
                    if ($option->isBoolean()) {
                        $keyvals[$name] = true;
                    } else {
                        // the next token MUST be an "argument" and not another flag/option
                        list($val, $type) = $this->_parseOption(array_shift($tokens));
                        if ($type !== self::OPTION_TYPE_ARGUMENT)
                            throw new \Exception(sprintf('Unable to parse option %s: Expected an argument', $token));
                        $keyvals[$name] = $val;
                    }
                }
            }

            // Set values (validates and performs map when applicable)
            foreach ($keyvals as $key => $value) {
                $this->getOption($key)->setValue($value);
            }

            // todo protect against duplicates caused by aliases
            foreach ($this->options as $option) {
                if (is_null($option->getValue()) && $option->isRequired()) {
                    throw new \Exception(sprintf('Required %s %s must be specified',
                        $option->getType() & Option::TYPE_NAMED ?
                            'option' : 'argument', $option->getName()));
                }
            }

            // keep track of our argument vs. flag keys
            // done here to allow for flags/arguments added
            // at run time.  okay because option values are
            // not mutable after parsing.
            foreach($this->options as $k => $v) {
                if (is_numeric($k)) {
                    $this->arguments[$k] = $v;
                } else {
                    $this->flags[$k] = $v;
                }
            }

            // Used in the \Iterator implementation
            $this->sorted_keys = array_keys($this->options);
            sort($this->sorted_keys, SORT_NATURAL);

            $this->parsed = true;

        } catch(\Exception $e) {
            $this->error($e);
        }
    }

    public function error(\Exception $e)
    {
        if ($this->beep_on_error === true) {
            \Commando\Util\Terminal::beep();
        }

        if ($this->trap_errors !== true) {
            throw $e;
        }

        $color = new \Colors\Color();
        $error = sprintf('ERROR: %s ', $e->getMessage());
        echo $color($error)->bg('red')->bold()->white() . PHP_EOL;
        exit(1);
    }

    /**
     * Has this Command instance parsed its arguments?
     * @return bool
     */
    public function isParsed()
    {
        return $this->parsed;
    }

    /**
     * @param string $token
     * @return array [option name/value, OPTION_TYPE_*]
     */
    private function _parseOption($token)
    {
        $matches = array();

        if (!preg_match('/(?P<hyphen>\-{1,2})?(?P<name>[a-z][a-z0-9_]*)/i', $token, $matches)) {
            throw new \Exception(sprintf('Unable to parse option %s: Invalid syntax', $token));
        }

        $type = self::OPTION_TYPE_ARGUMENT;
        if (!empty($matches['hyphen'])) {
            $type = (strlen($matches['hyphen']) === 1) ?
                self::OPTION_TYPE_SHORT:
                self::OPTION_TYPE_VERBOSE;
        }

        return array($matches['name'], $type);
    }


    /**
     * @param string $option
     * @return Option
     * @throws \Exception if $option does not exist
     */
    public function getOption($option)
    {
        if (!$this->hasOption($option)) {
            throw new \Exception(sprintf('Unknown option, %s, specified', $option));
        }

        return $this->options[$option];
    }

    /**
     * @return array of arguments only
     *
     * If your command was `php filename -f flagvalue argument1 argument2`
     * `getArguments` would return array("argument1", "argument2");
     */
    public function getArguments()
    {
        $this->parseIfNotParsed();
        return $this->arguments;
    }

    /**
     * @return array of flags only
     *
     * If your command was `php filename -f flagvalue argument1 argument2`
     * `getFlags` would return array("-f" => "flagvalue");
     */
    public function getFlags()
    {
        $this->parseIfNotParsed();
        return $this->flags;
    }

    /**
     * @param string $option name (named option) or index (annonymous option)
     * @return boolean
     */
    public function hasOption($option)
    {
        return !empty($this->options[$option]);
    }

    /**
     * @return string dump values
     */
    public function __toString()
    {
        // todo return values of set options as map of option name => value
        return $this->getHelp();
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return count($this->options);
    }

    /**
     * @param string $help
     * @return Command
     */
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @param bool $trap when true, exceptions will be caught by Commando and
     *    printed cleanly to standard error.
     * @return Command
     */
    public function trapErrors($trap = true)
    {
        $this->trap_errors = $trap;
        return $this;
    }

    /**
     * @return Command
     */
    public function doNotTrapErrors()
    {
        return $this->trapErrors(false);
    }

    /**
     * Terminal beep on error
     * @param bool $beep
     * @return Command
     */
    public function beepOnError($beep = true)
    {
        $this->beep_on_error = $beep;
        return $this;
    }

    /**
     * @return string help docs
     */
    public function getHelp()
    {
        $this->attachHelp();

        if (empty($this->name) && isset($this->tokens[0])) {
            $this->name = $this->tokens[0];
        }

        $color = new \Colors\Color();

        $help = '';

        $help .= $color(\Commando\Util\Terminal::header(' ' . $this->name))
            ->white()->bg('green')->bold() . PHP_EOL;

        if (!empty($this->help)) {
            $help .= PHP_EOL . \Commando\Util\Terminal::wrap($this->help)
                . PHP_EOL;
        }

        $help .= PHP_EOL;

        $seen = array();
        $keys = array_keys($this->options);
        sort($keys, SORT_NATURAL);
        foreach ($keys as $key) {
            $option = $this->getOption($key);
            if (in_array($option, $seen)) {
                continue;
            }
            $help .= $option->getHelp() . PHP_EOL;
            $seen[] = $option;
        }

        return $help;
    }

    public function printHelp()
    {
        echo $this->getHelp();
    }

    private function attachHelp()
    {
        // Add in a default help method
        $this->option('help')
            ->describe('Show the help page for this command.')
            ->boolean();
    }

    /**
     * @param string $offset
     * @see \ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param string $offset
     * @see \ArrayAccess
     */
    public function offsetGet($offset)
    {
        // Support implicit/lazy parsing
        $this->parseIfNotParsed();
        if (!isset($this->options[$offset])) {
            return null; // follows normal php convention
        }
        return $this->options[$offset]->getValue();
    }

    /**
     * @param string $offset
     * @param string $value
     * @throws \Exception
     * @see \ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('Setting an option value via array syntax is not permitted');
    }

    /**
     * @param string $offset
     * @see \ArrayAccess
     */
    public function offsetUnset($offset)
    {
        $this->options[$offset]->setValue(null);
    }

    /**
     * @see \Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return mixed value of current option
     * @see \Iterator
     */
    public function current()
    {
        return $this->options[$this->sorted_keys[$this->position]]->getValue();
    }

    /**
     * @return int
     * @see \Iterator
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @see \Iterator
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     * @see \Iterator
     */
    public function valid()
    {
        return isset($this->sorted_keys[$this->position]);
    }
}