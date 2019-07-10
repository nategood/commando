<?php

namespace Commando\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');

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

    public function testGetDescription()
    {
        $description = "I'm cool";
        $option = new Option("f");
        $option->setDescription($description);
        $this->assertEquals($description, $option->getDescription());
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
        $alias2 = 'foobar';

        $option = new Option($name);
        $option->addAlias($alias);
        $option->addAlias($alias2);

        $this->assertEquals(array($alias, $alias2), $option->getAliases());
    }

    /**
     * @dataProvider values
     */
    public function testSetValue($val)
    {
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
        } catch (\Exception $e) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    public function testFile()
    {
        $file = dirname(__FILE__) . '/assets/example.txt';
        $option = new Option(0);
        $option->setFileRequirements(true, false);
        $option->setValue($file);

        $this->assertTrue($option->isFile());
        $this->assertEquals($file, $option->getValue());
    }

    public function testFileGlob()
    {
        $file = dirname(__FILE__) . '/assets/*.txt';
        $option = new Option(0);
        $option->setFileRequirements(true, true);
        $option->setValue($file);

        $file1 = dirname(__FILE__) . '/assets/example.txt';
        $file2 = dirname(__FILE__) . '/assets/another.txt';

        $values = $option->getValue();
        $this->assertTrue($option->isFile());
        $this->assertCount(2, $values);
        $this->assertTrue(in_array($file1, $values));
        $this->assertTrue(in_array($file2, $values));
    }

    /**
     * @dataProvider values
     */
    public function testDefault($val)
    {
        $option = new Option('f');
        $option->setDefault($val);
        $this->assertEquals($val, $option->getValue());
    }

    /**
     * Test that requires options are set correctly
     */
    public function testSetRequired()
    {
        $option = new Option('f');
        $option->setNeeds('foo');

        $this->assertTrue(in_array('foo', $option->getNeeds()));
    }

    /**
     * Test that the reducer gets set
     */
    public function testSetReducer()
    {
        $option = new Option('f');

        $this->assertTrue(!$option->hasReducer());

        $option->setReducer(function() {});
        
        $this->assertTrue($option->hasReducer());
    }

    /**
     * Test that reducer is called
     */
    public function testReduce()
    {
        $option = new Option('f');
        $isCalled = false;
        $option->setReducer(function () use (&$isCalled) {
            $isCalled = true;
            return ($isCalled);
        });

        $this->assertTrue($option->reduce(null, null));
        $this->assertTrue($isCalled);
    }

    /**
     * Test that the needed requirements are met
     */
    public function testOptionRequirementsMet()
    {
        $option = new Option('f');
        $option->setNeeds('foo');
        $neededOption = new Option('foo');
        $neededOption->setValue(true);
        $optionSet = array(
            'foo' => $neededOption,
        );

        $this->assertTrue($option->hasNeeds($optionSet));
    }

    /**
     * Test hasNeeds when requirements are not met.
     * @test
     */
    public function testOptionRequiresNotMet()
    {
        $option = new Option('f');
        $option->setNeeds('foo');
        $optionSet = array(
            'foo' => new Option('foo'),
        );

        $expected = array(
            'foo',
        );
        $this->assertEquals($expected, $option->hasNeeds($optionSet));
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
            array(1.5),
            array(0.0),
            array(true),
            array(false),
        );
    }
}
