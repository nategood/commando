<?php
/**
 * TypeEnum Definition
 * 
 * @package nategood/commando
 * @source src/Commando/Option/TypeEnum.php
 */

namespace Commando\Option;

use Commando\Util\Enum;

/**
 * Class TypeEnum
 * 
 * Extend dynamic Enum
 */
class TypeEnum extends Enum
{

  const SHORT = 1;
  const LONG = 2;
  const ARGUMENT = 4;

  /**
   * Convenience method
   * 
   * Check if the instance value is LONG or SHORT
   *
   * @return boolean
   */
  public function isNamed()
  {
    return static::isValueNamed($this);
  }

  /**
   * Check if the $value is one of LONG or SHORT
   *
   * @param mixed $value
   * @return boolean
   */
  public static function isValueNamed($value)
  {
    return static::isValueType($value, (static::SHORT | static::LONG));
  }

}
