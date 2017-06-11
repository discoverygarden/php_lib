<?php
namespace Drupal\php_lib;

/**
 * Only supports one level of extension, all child classes should be declared final.
 *
 * Since Operator overloading is only availible via PECL extensions we need to compare the values directly or
 * via objects. So Statements like Enum::Const == new Enum(Enum::Const) won't work like they would with SplEnum.
 *
 * To compare and object and const value we can do something like Enum::Const == (new Enum(Enum::Const))->val;
 */
abstract class Enum {

  /**
   * The value for the instantiated Enum Object.
   *
   * @var mixed
   */
  protected $protected;

  /**
   * Create an Enumerated instance.
   *
   * @throws InvalidArgumentException
   * @param string or int or object... $value
   */
  public function __construct($value = NULL) {
    $this->protected = new ReadOnlyProtectedMembers(array('val' => NULL));
    $consts = $this->getConstList(TRUE);
    $use_default = $value === NULL;
    if ($use_default) {
      $has_default = isset($consts['__default']);
      if ($has_default) {
        $this->val = $consts['__default'];
      }
      else {
        $class_name = get_class($this);
        throw new InvalidArgumentException("No value provided, and no __default value defined for the class '{$class_name}'.");
      }
    }
    elseif (array_search($value, $consts) !== FALSE) {
      $this->val = $value;
    }
    else {
      $expected = implode(' or ', $this->getConstList(FALSE));
      throw new InvalidArgumentException("Invalid value '$value' provided. Expected $expected.");
    }
  }

  /**
   * Gets the list of the defined constants.
   *
   * @param bool $include_defaultInclude __default in the output?
   *   Include __default in the output?
   *
   * @return array
   */
  public function getConstList($include_default = FALSE) {
    $reflection = new ReflectionClass($this);
    $consts = $reflection->getConstants();
    if ($include_default === FALSE) {
      unset($consts['__default']);
    }
    return $consts;
  }

  public function __get($name) {
    return $this->protected->$name;
  }

  public function __set($name, $value) {
    $this->protected->$name = $value;
  }

  public function __isset($name) {
    return isset($this->protected->$name);
  }

  public function __unset($name) {
    unset($this->protected->$name);
  }

  public function __toString() {
    return $this->val;
  }

  public static function __callStatic($name, $arguments) {
    $consts = $this->getConstList(FALSE);
    if (isset($consts[$name])) {
      return $consts[$name];
    }
    else {
      $class_name = __CLASS__;
      throw new Exception("Constant '$name' is not defined in '$class_name'.");
    }
  }

}
