<?php
namespace Commando;


/**
 * Class SubCommand
 * Simply overwrites certain features to allow multiple command definitions, while still keeping with the main Command API
 * @package Commando
 */
class SubCommand extends Command {
    /**
     * @var The subcommands name
     */
    protected $_name;

    /**
     * @param $tokens
     * @param $command
     */
    public function __construct($tokens, $command)
    {
        parent::__construct($tokens);
        $this->_name = $command;
    }

    /**
     * // the main parser should handle this, lets just clean up our tokens first ..
     * @return null
     */
    public function parse()
    {
        // verify we are supposed to be running now..
        if($this->isParsed() === false) {
            $tokens = $this->getTokens();
            // are we the correct sub?
            if(isset($tokens[1]) === true
                && $this->name() === $tokens[1]) {
                // ... remove our subcommand from list of parsees.
                unset($tokens[1]);
                $this->setTokens(array_values($tokens));
                return parent::parse();
            }
        }
        return null;
    }

    /**
     * @return string Subcommand name
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     *
     */
    public function __destruct() {} // nada ta do
} 