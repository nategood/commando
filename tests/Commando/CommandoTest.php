<?php

namespace Commano\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Commando\Option;
use Commando\Commando;

class CommandoTest extends \PHPUnit_Framework_TestCase
{

    public function testCommandoAnon()
    {
        $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        $cmd = new Commando($tokens);
        $this->assertEquals($tokens[1], $cmd[0]);
    }

    public function testCommandoFlag()
    {
        // Single flag
        $tokens = array('filename', '-f', 'val');
        $cmd = new Commando($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[2], $cmd['f']);

        // Single alias
        $tokens = array('filename', '--foo', 'val');
        $cmd = new Commando($tokens);
        $cmd->option('f')->alias('foo');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[2], $cmd['foo']);

        // Multiple flags
        $tokens = array('filename', '-f', 'val', '-g', 'val2');
        $cmd = new Commando($tokens);
        $cmd->option('f')->option('g');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[4], $cmd['g']);

        // Single flag with anonnymous argument
        $tokens = array('filename', '-f', 'val', 'arg1');
        $cmd = new Commando($tokens);
        $cmd->option('f')->option();
        $this->assertEquals($tokens[3], $cmd[0]);

        // Single flag with anonnymous argument
        $tokens = array('filename', '-f', 'val', 'arg1');
        $cmd = new Commando($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[3], $cmd[0]);
    }

    public function testImplicitAndExplicitParse()
    {
        // Implicit
        $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        $cmd = new Commando($tokens);
        $this->assertFalse($cmd->isParsed());
        $val = $cmd[0];
        $this->assertTrue($cmd->isParsed());

        // Explicit
        $cmd = new Commando($tokens);
        $this->assertFalse($cmd->isParsed());
        $cmd->parse();
        $this->assertTrue($cmd->isParsed());
    }

    // Test retrieving a previously defined option via option($name)
    public function testRevtrievingOption()
    {
        // Short flag
        $tokens = array('filename', '-f', 'val');
        $cmd = new Commando($tokens);
        $cmd->option('f')->require();
        $this->assertTrue($cmd->getOption('f')->isRequired());
        $cmd->option('f')->require(false);
        $this->assertFalse($cmd->getOption('f')->isRequired());

        // Annonymous
        // $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        // $cmd = new Commando($tokens);
        // $cmd->option()->require();
        // $this->assertTrue($cmd->option(0)->isRequired());
        // $cmd->option(0)->setRequired(false);
        // $this->assertFalse($cmd->option(0)->isRequired());
    }
}