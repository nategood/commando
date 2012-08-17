<?php

namespace Commando;

class Option
{
    private
        $aliases = array(), /* aliases for this argument */
        $name, /* string optional name of argument */
        // $index, /* int */
        $description, /* string */
        $boolean = false, /* bool */
        $type, /* int see constants */
        $rule, /* closure|regex|int */
        $map; /* closure */

    const TYPE_NAMED = 1;
    const TYPE_ANONYMOUS = 2;

    /**
     * @param string|int $name single char name or int index for this option
     * @return Option
     */
    public function __construct($name)
    {
        if (!is_int($name) && empty($name)) {
            throw new \Exception(sprintf('Invalid option name %s: Must be identified by a single character or an integer', $name));
        } else if (!is_int($name) && mb_strlen($name) > 1) {
            throw new \Exception(sprintf('Invalid option name %s: Must be exactly one character', $name));
        }

        $this->type = is_int($name) ? self::TYPE_NAMED : self::TYPE_ANONYMOUS;

        $this->name = $name;

        // the name is an alias too...
        $this->addAlias($name);
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
     * @param closure|string $rule regex, closure
     * @return Option
     */
    public function setBoolean($bool = true)
    {
        $this->boolean = $bool;
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


    public function validate($value)
    {
        if (!is_callable($this->rule))
            return true;

        // todo add int, float and regex special case

        // todo double check syntax
        return call_user_func($this->rule, $value);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function isBoolean()
    {
        return $this->boolean;
    }

    public function setValue($value)
    {
        // boolean check
        if ($this->isBoolean() && !is_bool($value)) {
            throw new \Exception(sprintf('Boolean option expected, received %s value', $value));
        }

        if (!$this->validate($value)){
            throw new \Exception(sprintf('Invalid value, %s, for %s', $this->name, $this->value));
        }

        $this->value = $this->map($value);
    }
}