<?php

namespace Commando;

class Commando
{

    const OPTION_TYPE_ARGUMENT  = 1; // e.g. foo
    const OPTION_TYPE_SHORT     = 2; // e.g. -u
    const OPTION_TYPE_VERBOSE   = 4; // e.g. --username
    const OPTION_TYPE_MULTI     = 8; // e.g. -fbg
    const OPTION_TYPE_OPTION    = 2 | 4; // e.g. -u or --username

    private
        $current_option     = null,
        $options            = array(),
        $nameless_option_counter = 0;

    public function __contstructor($tokens = null)
    {
        if (empty($tokens)) {
            $tokens = $argv;
        }
        $this->_parse($tokens);
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

        'required' => 'required',
        'r' => 'required',

        'alias' => 'alias',
        'a' => 'alias',

        'describe' => 'describe',
        'd' => 'describe',
        'help' => 'describe',
        'h' => 'describe',

        'map' => 'map',
        'cast' => 'map',

        'must' => 'must',

        // Special cases of map
        // 'castToInt' => 'map',
        // 'castToFloat' => 'map',

        // Special cases of must
        // 'mustMatch' => 'must',
        // 'mustBeAnInt' => 'must',
    );


    /**
     * @param string $name
     * @param array $arguments
     * @return Commando
     */
    public function __call($name, $arguments)
    {
        if (empty($methods[$name])) {
            throw new \Exception('Unknown function called');
        }

        $name = $methods[$name]; // use the fully quantified name, e.g. "option" when "o"

        if ($name === 'option') {
            // We've reached a new "option", wrap up the previous
            // option in the chain
            if (!empty($this->current_option)) {
                // Add in alias references
                foreach ($option->getAliases() => $key) {
                    $this->options[$key] = $option;
                }
            }

            // Is this a previously declared option?
            if (!empty($this->options[$arguments[0]])) {
                $this->current_option = $this->getOption($arguments[0]);
            } else {
                $this->current_option = new Option;
            }
        }

        // set the option we'll be acting on
        if (empty($this->current_option)) {
            throw new \Exception(sprintf('Invalid Option Chain: Attempting to call %s before an "option" declaration', $name));
        }

        // TODO SPECIAL CASE FOR "GLOBAL" methods (or maybe just define the global methods instead of using magic)???

        // call method
        array_unshift($arguments, $this->current_option);
        $option = call_user_func_array(array($this, "_$name"), $arguments);

        return $this;
    }

    /**
     * @param Option $option
     * @param string $name if null, it is presumed to be a nameless argument and is ID'd by an int
     * @return Option
     */
    public function _option(Option $option, $name = null)
    {
        if (empty($name)) {
            $name = $this->nameless_option_counter++;
        }
        return $option->setName($name);
    }

    /**
     * @param Option $option
     * @return Option
     */
    public function _boolean(Option $option)
    {
        return $option->setBoolean();
    }

    /**
     * @param Option $option
     * @param string $alias
     * @return Option
     */
    public function _alias(Option $option, $alias)
    {
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
     * @param array $tokens command line tokens
     * @throws \Exception
     */
    public function _parse($tokens)
    {

        $filename = array_shift($tokens);

        $keyvals = array();
        $count = 0; // standalone argument count

        while (!empty($tokens)) {
            $token = array_shift($tokens);

            list($name, $type) = $this->_parseOption($token);

            if ($type === self::OPTION_TYPE_ARGUMENT) {
                // its an argument, use an int as the index
                $keyvals[$count++] = $name;
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

        // todo, have the Options do their thing (check constraints, perform mapping, perform casting, etc.)

        // todo implement required
        // foreach required option, make sure a value has been set
    }

    private function _parseOption($token) {
        $matches = array();

        if (!preg_match('/(?P<hyphen>-{1,2})?(?P<name>[a-z][a-z0-9_]+)/', $token, $matches)) {
            throw new \Exception(sprintf('Unable to parse option %s: Invalid syntax', $token));
        }

        $type = self::OPTION_TYPE_ARGUMENT;
        if (!empty($matches['hyphen'])) {
            $type = (strlen($matches['hyphen']) === 1) ?
                self::OPTION_TYPE_SINGLE:
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
     * @param string $option name (named option) or index (annonymous option)
     * @return boolean
     */
    public function hasOption($option)
    {
        return !empty($this->options[$option]);
    }

    /**
     * @return string help page
     */
    public function _toString()
    {
        // todo pretty help doc
    }

}

// class Rule
// {
//     const INT = 1;
//     const FLOAT = 2;

//     // function anInt($val)
//     // {
//     //     return is_numeric($val);
//     // }
// }