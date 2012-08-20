<?php

namespace Commando;

class Option
{
    private
        $name, /* string optional name of argument */
        $aliases = array(), /* aliases for this argument */
        $value = null, /* mixed */
        $description, /* string */
        $required = false, /* bool */
        $boolean = false, /* bool */
        $type = 0, /* int see constants */
        $rule, /* closure */
        $map; /* closure */

    const TYPE_SHORT        = 1;
    const TYPE_VERBOSE      = 2;
    const TYPE_NAMED        = 3; // 1|2
    const TYPE_ANONYMOUS    = 4;

    /**
     * @param string|int $name single char name or int index for this option
     * @return Option
     */
    public function __construct($name)
    {
        if (!is_int($name) && empty($name)) {
            throw new \Exception(sprintf('Invalid option name %s: Must be identified by a single character or an integer', $name));
        }

        if (!is_int($name)) {
            $this->type = mb_strlen($name, 'UTF-8') === 1 ?
                self::TYPE_SHORT : self::TYPE_VERBOSE;
        } else {
            $this->type = self::TYPE_ANONYMOUS;
        }

        $this->name = $name;
    }

    /**
     * @param string $alias
     * @return Option
     */
    public function addAlias($alias)
    {
        $this->aliases[] = $alias;
        return $this;
    }

    /**
     * @param closure|string $rule regex, closure
     * @return Option
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param bool $bool
     * @return Option
     */
    public function setBoolean($bool = true)
    {
        $this->boolean = $bool;
        return $this;
    }

    /**
     * @param bool $bool required?
     * @return Option
     */
    public function setRequired($bool = true)
    {
        $this->required = $bool;
        return $this;
    }

    /**
     * @param closure|string $rule regex, closure
     * @return Option
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @param closure|string $rule regex, closure
     * @return Option
     */
    public function setMap(\Closure $map)
    {
        $this->map = $map;
        return $this;
    }


    /**
     * @param closure|string $rule regex, closure
     * @return Option
     */
    public function map($value)
    {
        if (!is_callable($this->map))
            return $value;

        // todo add int, float and regex special case

        // todo double check syntax
        return call_user_func($this->map, $value);
    }


    /**
     * @return bool
     */
    public function validate($value)
    {
        if (!is_callable($this->rule))
            return true;

        // todo add int, float and regex special case

        // todo double check syntax
        return call_user_func($this->rule, $value);
    }

    /**
     * @return string|int name of the option
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return mixed value of the option
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array list of aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @return bool is this option a boolean
     */
    public function isBoolean()
    {
        $this->value = false;
        return $this->boolean;
    }

    /**
     * @return bool is this option required?
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param mixed value for this option (set on the command line)
     */
    public function setValue($value)
    {
        // boolean check
        if ($this->isBoolean() && !is_bool($value)) {
            throw new \Exception(sprintf('Boolean option expected for option -%s, received %s value instead', $this->name, $value));
        }
        if (!$this->validate($value)){
            throw new \Exception(sprintf('Invalid value, %s, for option -%s', $value, $this->name));
        }
        $this->value = $this->map($value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $color = new \Colors\Color();
        $help = '';

        if ($this->type & self::TYPE_NAMED) {
            $help .=  PHP_EOL . (mb_strlen($this->name, 'UTF-8') === 1 ?
                '-' : '--') . $this->name;
            if (!empty($this->aliases)) {
                foreach($this->aliases as $alias) {
                    $help .= (mb_strlen($alias, 'UTF-8') === 1 ?
                        '/-' : '/--') . $alias;
                }
            }
            if (!$this->isBoolean()) {
                $help .= ' ' . $color('<argument>')->underline();
            }
            $help .= PHP_EOL;
        } else {
            $help .= 'arg' . $this->name . PHP_EOL;
        }

        if (!empty($this->description)) {
            $help .= \Commando\Util\Terminal::wrap($this->description, 5, 1);
        }

        return $help;
    }
}