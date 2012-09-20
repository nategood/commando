<?php

namespace Commando;

class Option
{
    private
        $name, /* string optional name of argument */
        $title, /* a formal way to reference this argument */
        $aliases = array(), /* aliases for this argument */
        $value = null, /* mixed */
        $description, /* string */
        $required = false, /* bool */
        $boolean = false, /* bool */
        $type = 0, /* int see constants */
        $rule, /* closure */
        $map, /* closure */
        $file = false, /* bool */
        $file_require_exists, /* bool require that the file path is valid */
        $file_allow_globbing; /* bool allow globbing for files */

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
     * @param string $description
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
     * Require that the argument is a file.  This will
     * make sure the argument is a valid file, will expand
     * the file path provided to a full path (e.g. map relative
     * paths), and in the case where $allow_globbing is set,
     * supports file globbing and returns an array of matching
     * files.
     *
     * @return string|array of full file path|paths
     * @param bool $require_exists
     * @param bool $allow_globbing
     * @throws \Exception if the file does not exists
     */
    public function setFile($require_exists = true, $allow_globbing = true)
    {
        $this->file = true;
        $this->file_require_exists = $require_exists;
        $this->file_allow_globbing = $allow_globbing;
    }

    /**
     * @param string $title
     * @return Option
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @param string $filepath
     * @return string|array full file path or an array of file paths in the
     *     case of "globbing" if supported
     */
    public function parseFile($filepath)
    {
        $pwd = dirname($_SERVER['SCRIPT_NAME']);
        $path = realpath($pwd . $path);
        if ($this->file_require_exists && $path === false) {
            throw new \Exception('Argument must be a valid file'); // todo include argument/flag name
        }
        if ($this->file_support_globbing) {
            // glob();
            // todo
        }
        return $path;
    }

    /**
     * @return string|int name of the option
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int type (see OPTION_TYPE_CONST)
     */
    public function getType()
    {
        return $this->type;
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
        if ($this->requireFile()) {
            $this->value = $this->parseFileString($value);
        }
        $this->value = $this->map($value);
    }

    /**
     * @return string
     */
    public function getHelp()
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
            $help .= (empty($this->title) ? "arg {$this->name}" : $this->title) . PHP_EOL;
        }

        $description = $this->description;
        if ($this->isRequired()) {
            $description = 'Required.  ' . $description;
        }

        if (!empty($description)) {
            $help .= \Commando\Util\Terminal::wrap(
                $this->title . $description, 5, 1);
        }

        return $help;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHelp();
    }
}
