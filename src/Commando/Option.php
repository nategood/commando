<?php

namespace Commando;
use \Commando\Util\Terminal;

/**
 * Here are all the methods available through __call.  For accurate method documentation, see the actual method.
 *
 * This is merely for intellisense purposes!
 *
 * @method Option option (mixed $name = null)
 * @method Option o (mixed $name = null)
 * @method Option flag (string $name)
 * @method Option argument (mixed $option = null)
 * @method Option alias (string $alias)
 * @method Option a (string $alias)
 * @method Option aka (string $alias)
 * @method Option description (string $description)
 * @method Option d (string $description)
 * @method Option describe (string $description)
 * @method Option describedAs (string $description)
 * @method Option require (bool $require = true)
 * @method Option r (bool $require = true)
 * @method Option required (bool $require = true)
 * @method Option needs (mixed $options)
 * @method Option must (\Closure $rule)
 * @method Option cast (\Closure $map)
 * @method Option castTo (\Closure $map)
 * @method Option referToAs (string $name)
 * @method Option title (string $name)
 * @method Option referredToAs (string $name)
 * @method Option boolean ()
 * @method Option default (mixed $defaultValue)
 * @method Option defaultsTo (mixed $defaultValue)
 * @method Option file ()
 * @method Option expectsFile ()
 *
 */

class Option
{
    private
        $name, /* string optional name of argument */
        $title, /* a formal way to reference this argument */
        $aliases = array(), /* aliases for this argument */
        $value = null, /* mixed */
        $description, /* string */
        $required = false, /* bool */
        $needs = array(), /* set of other required options for this option */
        $conflicts = array(), /* set of options this option conflicts with */
        $boolean = false, /* bool */
        $type = 0, /* int see constants */
        $rule, /* closure */
        $map, /* closure */
        $increment = false, /* bool */
        $max_value = 0, /* int max value for increment */
        $default, /* mixed default value for this option when no value is specified */
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
     * @throws \Exception
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
        // if we didn't define a default already, set false as the default value...
        if($this->default === null) {
            $this->setDefault(false);
        }
        $this->boolean = $bool;
        return $this;
    }

    /**
     * @param int $max
     * @return Option
     */
    public function setIncrement($max = 0)
    {
        if($this->default === null) {
            $this->setDefault(0);
        }
        $this->increment = true;
        $this->max_value = $max;
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
    public function setFileRequirements($require_exists = true, $allow_globbing = true)
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
     * Set an option as required
     *
     * @param string $option Option name
     * @return Option
     */
    public function setNeeds($option)
    {
        if (!is_array($option)) {
            $option = array($option);
        }
        foreach ($option as $opt) {
            $this->needs[] = $opt;
        }
        return $this;
    }

    /**
     * Set an option as conflicting
     *
     * @param string $option Option name
     * @return Option
     */
    public function setConflicts($option)
    {
        if (!is_array($option)) {
            $option = array($option);
        }
        foreach ($option as $opt) {
            $this->conflicts[] = $opt;
        }
        return $this;
    }

    /**
     * @param mixed $value default value
     * @return Option
     */
    public function setDefault($value)
    {
        $this->default = $value;
        $this->setValue($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param \Closure|string $rule regex, closure
     * @return Option
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @param \Closure
     * @return Option
     */
    public function setMap(\Closure $map)
    {
        $this->map = $map;
        return $this;
    }

    /**
     * @param \Closure|string $value regex, closure
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
     * @param mixed $value
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
     * @param string $file_path
     * @return string|array full file path or an array of file paths in the
     *     case where "globbing" is supported
     */
    public function parseFilePath($file_path)
    {
        $path = realpath($file_path);
        if ($this->file_allow_globbing) {
            $files = glob($file_path);
            if (empty($files)) {
                return $files;
            }
            return array_map(function($file) {
                return realpath($file);
            }, $files);
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
     * Get the current set of this option's requirements
     * @return array List of required options
     */
    public function getNeeds()
    {
        return $this->needs;
    }

    /**
     * Get the current set of this option's conflicts
     * @return array List of conflicting options
     */
    public function getConflicts()
    {
        return $this->conflicts;
    }

    /**
     * @return bool is this option a boolean
     */
    public function isBoolean()
    {
        // $this->value = false; // ?
        return $this->boolean;
    }

    /**
     * @return bool is this option an incremental option
     */
    public function isIncrement()
    {
        return $this->increment;
    }

    /**
     * @return bool is this option a boolean
     */
    public function isFile()
    {
        return $this->file;
    }

    /**
     * @return bool is this option required?
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Check to see if requirements list for option are met
     *
     * @param array $optionsList Set of current options defined
     * @return boolean|array True if requirements met, array if not found
     */
    public function hasNeeds($optionsList)
    {
        $needs = $this->getNeeds();

        $definedOptions = array_keys($optionsList);
        $notFound = array();
        foreach ($needs as $need) {
            if (!in_array($need, $definedOptions)) {
                // The needed option has not been defined as a valid flag.
                $notFound[] = $need;
            } elseif (!$optionsList[$need]->getValue()) {
                // The needed option has been defined as a valid flag, but was
                // not passed in by the user.
                $notFound[] = $need;
            }
        }
        return (empty($notFound)) ? true : $notFound;
    }

    /**
     * Check to see if any conflicting options are set
     *
     * @param array $optionsList Set of current options defined
     * @return boolean|array Array of conflicts, false if none
     */
    public function hasConflicts($optionsList)
    {
        if (!$this->getValue()) {
            // If this option isn't set, it can't conflict with anything.
            return false;
        }

        $conflicts = $this->getConflicts();
        $found = array();

        foreach ($conflicts as $conflict) {
            if (isset($optionsList[$conflict]) && $optionsList[$conflict]->getValue()) {
                // A conflicting option is found.
                $found[] = $conflict;
            }
        }

        if (empty($found)) {
            // No conflicts found.
            return false;
        }
        return $found;
    }

    /**
     * @param mixed $value for this option (set on the command line)
     * @throws \Exception
     */
    public function setValue($value)
    {
        if ($this->isBoolean() && !is_bool($value)) {
            throw new \Exception(sprintf('Boolean option expected for option %s, received %s value instead', $this->name, $value));
        }
        if (!$this->validate($value)) {
            throw new \Exception(sprintf('Invalid value, %s, for option %s', $value, $this->name));
        }
        if ($this->isIncrement()) {
            if (!is_int($value)) {
                throw new \Exception(sprintf('Integer expected as value for %s, received %s instead', $this->name, $value));
            }
            if ($value > $this->max_value && $this->max_value > 0) {
                $value = $this->max_value;
            }
        }
        if ($this->isFile()) {
            $file_path = $this->parseFilePath($value);
            if (empty($file_path)) {
                if ($this->file_require_exists) {
                    throw new \Exception(sprintf('Expected %s to be a valid file', $value, $this->name));
                }
            } else {
                $value = $file_path;
            }
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

        $isNamed = ($this->type & self::TYPE_NAMED);

        if ($isNamed) {
            $help .=  PHP_EOL . (mb_strlen($this->name, 'UTF-8') === 1 ?
                '-' : '--') . $this->name;
            if (!empty($this->aliases)) {
                foreach($this->aliases as $alias) {
                    $help .= (mb_strlen($alias, 'UTF-8') === 1 ?
                        '/-' : '/--') . $alias;
                }
            }
            if (!$this->isBoolean()) {
                $help .= ' ' . $color->underline('<argument>');
            }
            $help .= PHP_EOL;
        } else {
            $help .= (empty($this->title) ? "arg {$this->name}" : $this->title) . PHP_EOL;
        }

        // bold what has been displayed so far
        $help = $color->bold($help);

        $titleLine = '';
        if($isNamed && $this->title) {
            $titleLine .= $this->title . '.';
            if ($this->isRequired()) {
                $titleLine .= ' ';
            }
        }

        if ($this->isRequired()) {
            $titleLine .= $color->red('Required.');
        }

        if($titleLine){
            $titleLine .= ' ';
        }
        $description = $titleLine . $this->description;
        if (!empty($description)) {
            $descriptionArray = explode(PHP_EOL, trim($description));
            foreach($descriptionArray as $descriptionLine){
                $help .= Terminal::wrap($descriptionLine, 5, 1) . PHP_EOL;
            }

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
