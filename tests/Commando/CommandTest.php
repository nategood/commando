<?php

namespace Commando\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Commando\Option;
use Commando\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{

    public function testCommandoAnon()
    {
        $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        $cmd = new Command($tokens);
        $this->assertEquals($tokens[1], $cmd[0]);
    }

    public function testCommandoFlag()
    {
        // Single flag
        $tokens = array('filename', '-f', 'val');
        $cmd = new Command($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[2], $cmd['f']);

        // Single alias
        $tokens = array('filename', '--foo', 'val');
        $cmd = new Command($tokens);
        $cmd->option('f')->alias('foo');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[2], $cmd['foo']);

        // Multiple flags
	$tokens = array('filename', '-f', 'val', '-g', 'val2','--path=testpath');
        $cmd = new Command($tokens);
        $cmd->option('f')->option('g')->option('path');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[4], $cmd['g']);
	$this->assertEquals("testpath", $cmd['path']);

        // Single flag with anonnymous argument
        $tokens = array('filename', '-f', 'val', 'arg1');
        $cmd = new Command($tokens);
        $cmd->option('f')->option();
        $this->assertEquals($tokens[3], $cmd[0]);

        // Single flag with anonnymous argument
        $tokens = array('filename', '-f', 'val', 'arg1');
        $cmd = new Command($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[3], $cmd[0]);

        // Define flag with `flag` and a named argument
        $tokens = array('filename', '-f', 'val', 'arg1');
        $cmd = new Command($tokens);
        $cmd
            ->flag('f')
            ->argument();
        $this->assertEquals($tokens[3], $cmd[0]);
        $this->assertEquals($tokens[2], $cmd['f']);

	// Verbose equals named argument
        $tokens = array('filename','--filename=testfilename');
        $cmd = new Command($tokens);
        $cmd->option('filename');
        $this->assertEquals("testfilename", $cmd['filename']);

    }

    public function testImplicitAndExplicitParse()
    {
        // Implicit
        $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        $cmd = new Command($tokens);
        $this->assertFalse($cmd->isParsed());
        $val = $cmd[0];
        $this->assertTrue($cmd->isParsed());

        // Explicit
        $cmd = new Command($tokens);
        $this->assertFalse($cmd->isParsed());
        $cmd->parse();
        $this->assertTrue($cmd->isParsed());
    }

    // Test retrieving a previously defined option via option($name)
    public function testRetrievingOptionNamed()
    {
        // Short flag
        $tokens = array('filename', '-f', 'val');
        $cmd = new Command($tokens);
        $option = $cmd->option('f')->require();

        $this->assertTrue($cmd->getOption('f')->isRequired());
        $cmd->option('f')->require(false);
        $this->assertFalse($cmd->getOption('f')->isRequired());

        // Make sure there is still only one option
        $this->assertEquals(1, $cmd->getSize());
    }

    // Test retrieving a previously defined option via option($name)
    public function testRetrievingOptionAnon()
    {
        // Annonymous
        $tokens = array('filename', 'arg1', 'arg2', 'arg3');
        $cmd = new Command($tokens);
        $option = $cmd->option()->require();

        $this->assertTrue($cmd->getOption(0)->isRequired());
        $cmd->option(0)->require(false);
        $this->assertFalse($cmd->getOption(0)->isRequired());

        $this->assertEquals(1, $cmd->getSize());
    }

    public function testBooleanOption()
    {
        // with bool flag
        $tokens = array('filename', 'arg1', '-b', 'arg2');
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->boolean();
        $this->assertTrue($cmd['b']);
        // without
        $tokens = array('filename', 'arg1', 'arg2');
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->boolean();
        $this->assertFalse($cmd['b']);

        // try inverse bool default operations...
        // with bool flag
        $tokens = array('filename', 'arg1', '-b', 'arg2');
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->default(true)
            ->boolean();
        $this->assertFalse($cmd['b']);
        // without
        $tokens = array('filename', 'arg1', 'arg2');
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->default(true)
            ->boolean();
        $this->assertTrue($cmd['b']);
    }

    public function testGetValues()
    {
        $tokens = array('filename', '-a', 'v1', '-b', 'v2', 'v3', 'v4', 'v5');
        $cmd = new Command($tokens);
        $cmd
            ->flag('a')
            ->flag('b')->aka('boo');

        $this->assertEquals(array('v3', 'v4', 'v5'), $cmd->getArgumentValues());
        $this->assertEquals(array('a' => 'v1', 'b' => 'v2'), $cmd->getFlagValues());
    }

    /**
     * Ensure that requirements are resolved correctly
     */
    public function testRequirementsOnOptionsValid()
    {
        $tokens = array('filename', '-a', 'v1', '-b', 'v2');
        $cmd = new Command($tokens);

        $cmd->option('b');
        $cmd->option('a')
            ->needs('b');

        $this->assertEquals($cmd['a'], 'v1');
    }

    
    /**
     * Ensure that requirements are resolved correctly when 0 is an argument
     */
    public function testRequirementsOnOptionsValidZero()
    {
        $tokens = array('filename', '-a', '0', '-b', '0');
        $cmd = new Command($tokens);

        $cmd->option('b');
        $cmd->option('a')
            ->needs('b');

        $this->assertEquals($cmd['a'], '0');
    }
    
    /**
     * Test that an exception is thrown when an option isn't set
     * @expectedException \InvalidArgumentException
     */
    public function testRequirementsOnOptionsMissing()
    {
        $tokens = array('filename', '-a', 'v1',);
        
        $cmd = new Command($tokens);
        $cmd->trapErrors(false)
           ->beepOnError(false);        
        $cmd->option('a')
        ->needs('b');
                
    }

/**
     * Test that an exception is thrown when an option isn't set
     * @expectedException \InvalidArgumentException
     */
    public function testRequirementsOnOptionsUndefined()
    {
        $tokens = array('filename', '-a', 'v1');
        
        $cmd = new Command($tokens);
        $cmd->trapErrors(false)
           ->beepOnError(false);        
        $cmd->option('a')
        ->needs('b');
                
    }
    
}
