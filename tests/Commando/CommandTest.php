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
        $tokens = array('filename', '-f', 'val', '-g', 'val2');
        $cmd = new Command($tokens);
        $cmd->option('f')->option('g');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[4], $cmd['g']);

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
    public function testRevtrievingOptionNamed()
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
    public function testRevtrievingOptionAnon()
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

    public function testRequireBool()
    {
        $this->setExpectedException('\Exception');

        // -a is not specified, ergo an exception should be thrown because it is required
        $tokens = array('filename');
        $cmd = new Command($tokens);
        $cmd->trapErrors(false);
        $cmd->option('a')->require(true);
        $cmd->parse();
    }

    public function testRequireCallableTrue()
    {
        $this->setExpectedException('\Exception');

        // -a is not specified, ergo b is required and an exception should be thrown
        $tokens = array('filename');
        $cmd = new Command($tokens);
        $cmd->trapErrors(false);
        $cmd->option('a');
        $cmd->option('b')->require(function() use($cmd){
            return $cmd->getOption('a')->getValue() == null;
        });
        $cmd->parse();
    }

    public function testRequireCallableFalse()
    {
        try { 
            // -a is not specified, ergo b is required and an exception should not be thrown
            $tokens = array('filename', '-b', '1');
            $cmd = new Command($tokens);
            $cmd->trapErrors(false);
            $cmd->option('a');
            $cmd->option('b')->require(function() use($cmd){
                return $cmd->getOption('a')->getValue() == null;
            });
            $cmd->parse();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->fail('An unexpected exception has been raised.');
        }
    }
}