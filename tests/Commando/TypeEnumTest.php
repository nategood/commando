<?php

namespace Commando\Test;

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');

use Commando\Option\TypeEnum;

function errorHandlerFactory(&$caught) {
  return function () use (&$caught) {
    $caught = true;
    return $caught;
  };
}

class TypeEnumTest extends \PHPUnit_Framework_TestCase {

  function testExtractValueFrom() {
    $type = new TypeEnum(TypeEnum::SHORT);
    $this->assertEquals(TypeEnum::extractValueFrom($type), TypeEnum::SHORT);
  }

  function testIsValueType() {
    $type1 = new TypeEnum(TypeEnum::SHORT);
    $type2 = new TypeEnum(TypeEnum::LONG);
    $this->assertFalse(TypeEnum::isValueType(TypeEnum::SHORT, TypeEnum::LONG));
    $this->assertFalse(TypeEnum::isValueType($type1, $type2));
    $this->assertTrue(TypeEnum::isValueType($type1, TypeEnum::SHORT));
    $this->assertTrue(TypeEnum::isValueType(TypeEnum::LONG, $type2));
  }

  function test__getValue() {
    $type = new TypeEnum(TypeEnum::SHORT);
    $this->assertEquals($type->value, TypeEnum::SHORT);
  }

  function testIsType() {
    $type = new TypeEnum(TypeEnum::SHORT);
    $this->assertTrue($type->isType(TypeEnum::SHORT));
    $this->assertFalse($type->isType(new TypeEnum(2))); // TypeEnum::LONG
  }

  function testIsValueNamed() {
    $type = new TypeEnum(TypeEnum::SHORT);
    $this->assertTrue(TypeEnum::isValueNamed($type));
    $this->assertTrue(TypeEnum::isValueNamed(TypeEnum::LONG));
    $this->assertFalse(TypeEnum::isValueNamed(4)); // TypeEnum::ARGUMENT
  }

  function testIsNamed() {
    $type1 = new TypeEnum(TypeEnum::SHORT);
    $type2 = new TypeEnum(TypeEnum::LONG);
    $type3 = new TypeEnum(TypeEnum::ARGUMENT);
    $this->assertTrue($type1->isNamed());
    $this->assertTrue($type2->isNamed());
    $this->assertFalse($type3->isNamed());
  }

}