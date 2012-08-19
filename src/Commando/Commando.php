<?php
/**
 * @author Nate Good <me@nategood.com>
 */

namespace Commando;

class Commando implements \ArrayAccess
{
    const OPTION_TYPE_ARGUMENT  = 1; // e.g. foo
    const OPTION_TYPE_SHORT     = 2; // e.g. -u
    const OPTION_TYPE_VERBOSE   = 4; // e.g. --username
    const OPTION_TYPE_MULTI     = 8; // e.g. -fbg

    private
        $current_option             = null,
        $options                    = array(),
        $nameless_option_counter    = 0,
        $tokens                     = array(),
        $parsed                     = false;

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
        return new Commando($tokens);
    }

    /**
     * @var array Valid "option" options, mapped to their aliases
     */
    public static $methods = array(

        'option' => 'option',
        'o' => 'option',

        'boolean' => 'boolean',
        'bool' => 'boolean',
        'b' => 'boolean',

        'require' => 'require',
        'r' => 'require',

        'alias' => 'alias',
        'aka' => 'alias',
        'a' => 'alias',

        'describe' => 'describe',
        'd' => 'describe',
        'help' => 'describe',
        'h' => 'describe',
        'description' => 'describe',
        'describedAs' => 'describe',

        'map' => 'map',
        'mapTo' => 'map',
        'cast' => 'map',
        'castWith' => 'map',

        'must' => 'must',

        // Special cases of map
        // 'castToInt' => 'map',
        // 'castToFloat' => 'map',

        // Special cases of must
        // 'mustMatch' => 'must',
        // 'mustBeAnInt' => 'must',
    );


    /**
     * This is the meat of Commando.  Any time we are operating on
     * an individual option for commando (e.g. $cmd->option()->require()...)
     * it relies on this magic method.  It allows us to handle some logic
     * that is applicable across the board and also allows easy aliasing of
     * methods (e.g. "o" for "option")... since it is a CLI library, such
     * minified aliases would only be fitting :-).
     *
     * @param string $name
     * @param array $arguments
     * @return Commando
     */
    public function __call($name, $arguments)
    {
        if (empty(self::$methods[$name])) {
            throw new \Exception(sprintf('Unknown function, %s, called', $name));
        }

        // use the fully quantified name, e.g. "option" when "o"
        $name = self::$methods[$name];

        // set the option we'll be acting on
        if (empty($this->current_option) && $name !== 'option') {
            throw new \Exception(sprintf('Invalid Option Chain: Attempting to call %s before an "option" declaration', $name));
        }

        // call method
        array_unshift($arguments, $this->current_option);
        $option = call_user_func_array(array($this, "_$name"), $arguments);

        return $this;
    }

    /**
     * @param Option|null $option
     * @return Option
     */
    public function _option($option, $name = null)
    {
        // Is this a previously declared option?
        if (!empty($name) && !empty($this->options[$name])) {
            $this->current_option = $this->getOption($name);
        } else {
            if (empty($name)) {
                $name = $this->nameless_option_counter++;
            }
            $this->current_option = $this->options[$name] = new Option($name);
        }

        return $this->current_option;
    }

    // OPTION OPERATIONS

    /**
     * @param Option $option
     * @return Option
     */
    public function _boolean(Option $option, $boolean = true)
    {
        return $option->setBoolean($boolean);
    }

    /**
     * @param Option $option
     * @return Option
     */
    public function _require(Option $option, $require = true)
    {
        return $option->setRequired($require);
    }

    /**
     * @param Option $option
     * @param string $alias
     * @return Option
     */
    public function _alias(Option $option, $alias)
    {
        $this->options[$alias] = $this->current_option;
        return $option->addAlias($alias);
    }

    /**
     * @param Option $option
     * @param string $description
     * @return Option
     */
    public function _describe(Option $option, $description)
    {
        return $option->setDescription($description);
    }

    /**
     * @param Option $option
     * @param \Closure $callback (string $value) -> boolean
     * @return Option
     */
    public function _must(Option $option, \Closure $callback)
    {
        return $option->setRule($callback);
    }

    /**
     * @param Option $option
     * @param \Closure $callback
     * @return Option
     */
    public function _map(Option $option, \Closure $callback)
    {
        return $option->setMap($callback);
    }

    // END OPTION OPERATIONS

    /**
     * Rare that you would need to use this other than for testing,
     * allows defining the cli tokens, instead of using $argv
     * @param array $cli_tokens
     */
    public function setTokens(array $cli_tokens)
    {
        $this->tokens = $cli_tokens;
    }

    /**
     * @throws \Exception
     */
    public function parse()
    {
        $tokens = $this->tokens;
        $filename = array_shift($tokens);

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
                // no combo support yet (e.g. -abc !== -a -b -c)
                $option = $this->getOption($name);
                if ($option->isBoolean()) {
                    // todo implicitly set each boolean option to false to start with
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
                throw new Exception(sprintf('Required option, %s, must be specified', $option->getName()));
            }
        }

        $this->parsed = true;
    }

    /**
     * Has this Commando instance parsed its arguments?
     * @return bool
     */
    public function isParsed()
    {
        return $this->parsed;
    }

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
        // var_dump(array_keys($this->options));
        if (!$this->hasOption($option)) {
            throw new \Exception(sprintf('Unknown option, %s, specified', $option));
        }

        return $this->options[$option];
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
    public function _toString()
    {
        // todo return values of set options as map of option name => value
    }

    /**
     * @return string help docs
     */
    public function helpText()
    {
        // todo
        return '';
    }

    // ARRAYACCESS METHODS

    /**
     * @param string $offset
     */
    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param string $offset
     */
    public function offsetGet($offset)
    {
        // Support implicit/lazy parsing
        if (!$this->isParsed()) {
            $this->parse();
        }
        if (!isset($this->options[$offset])) {
            return null; // bc it is PHP like... might want to throw an Exception?
        }
        return $this->options[$offset]->getValue();
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        // todo maybe support?
        throw new Exception('Setting an option value via array syntax is not permitted');
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->options[$offset]->setValue(null);
    }

    // END ARRAYACCESS METHODS

}