<?php
/**
 * Generate base Enum class
 * 
 * @package nategood/commando
 * @source src/Commando/Util/Enum.php
 */

namespace Commando\Util;

/**
 * Trait EnumUtilitiesTrait
 * 
 * Prevent code duplication when defining our base Enum.
 */
trait EnumUtilitiesTrait
{
  /**
   * Get value with magic
   * 
   * @method __get
   * @param string $name
   * @return void
   */
  public function __get($name) {
    if ($name === 'value') {
      return $this->__default;
    }
  }

  /**
   * Is the instance value equal to $type?
   *
   * @param mixed $type
   * @return boolean
   */
  public function isType($type)
  {
    return static::isValueType($this, $type);
  }

  /**
   * Is the $value equal to $type?
   *
   * @param mixed $value
   * @param mixed $type
   * @return boolean
   */
  public static function isValueType($value, $type)
  {
    $realValue = (int) is_subclass_of($value, self::class) ? static::extractValueFrom($value) : $value;
    $realType = (int) is_a($type, self::class) ? static::extractValueFrom($type) : $type;

    return (bool) ($realValue & $realType);
  }

  /**
   * Get value of Enum
   *
   * @param Enum $value
   * @return int
   */
  public static function extractValueFrom(Enum $value)
  {
    return $value->value;
  }
}

if (class_exists("\\SplEnum")) {
  /**
   * Enum extending SPL_TYPES SplEnum
   */
  class Enum extends \SplEnum {
    use EnumUtilitiesTrait;
  }
} else {
  /**
   * Enum extending ducks-project/spl-types SplEnum
   */
  class Enum extends \Ducks\Component\SplTypes\SplEnum {
    use EnumUtilitiesTrait;
  }
}
