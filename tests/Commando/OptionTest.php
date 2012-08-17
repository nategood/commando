<?php

namespace Commano\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Commando\Option;
use Commando\Commando;

class OptionTest extends \PHPUnit_Framework_TestCase
{

    public function testNamedOption()
    {
        $name = 'f';
        $option = new Option($name);
        $this->assertEquals($name, $option->getName());
    }

    public function testAnnonymousOption()
    {
        $option = new Option(0);

        $name = 'f';
        $named_option = new Option($name);

        $anon_option = new Option(1);

        $this->assertEquals(0, $option->getName());
        $this->assertEquals($name, $named_option->getName());
        $this->assertEquals(1, $anon_option->getName());
    }

    public function testAddAlias()
    {
        $name = 'f';
        $alias = 'foo';

        $option = new Option($name);
        $option->addAlias($alias);

        $this->assertEquals(array($name, $alias), $option->getAliases());
    }

    /**
     * @dataProvider values
     */
    public function testSetValue($val)
    {
        // $val = 'abc';
        $option = new Option('f');
        $option->setValue($val);
        $this->assertEquals($val, $option->getValue());
    }

    /**
     * @dataProvider values
     */
    public function testMap($val)
    {

        $option = new Option('f');
        $option->setMap(function($value) {
            return $value . $value;
        });

        $option->setValue($val);
        $this->assertEquals($val . $val, $option->getValue());
    }

    public function testRule()
    {

        $option = new Option('f');
        $option->setRule(function($value) {
            return is_numeric($value);
        });

        $this->assertFalse($option->validate('a'));
        $this->assertFalse($option->validate('abc'));
        $this->assertTrue($option->validate('2'));
        $this->assertTrue($option->validate(2));
        $this->assertTrue($option->validate(0));

        $option->setValue(2);

        $caught = false;
        try {
            $option->setValue('abc');
        } catch (Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    // Providers

    public function values()
    {
        return array(
            array('abc'),
            array('The quick, brown fox jumps over a lazy dog.'),
            array('200'),
            array(200),
            array(0),
        );
    }
}