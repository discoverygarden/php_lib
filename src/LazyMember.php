<?php
namespace Drupal\php_lib;

/**
 * Lazy Member.
 */
class LazyMember {

  /**
   * A valid argument for call_user_func.
   *
   * @var mixed
   */
  protected $function;
  /**
   *
   *
   * @var mixed
   */
  protected $parameters;
  /**
   * The result from the function.
   *
   * @var mixed
   */
  protected $value;
  /**
   * Has the value.
   *
   * @var boolean
   */
  public $dirty;

  /**
   *
   * @param mixed $function
   * @param object $owner
   */
  public function __construct($function, $parameters = NULL) {
    $this->function = $function;
    $this->parameters = $parameters;
    $this->value = NULL;
    $this->dirty = TRUE;
  }

  /**
   *
   */
  public function __invoke() {
    if ($this->dirty) {
      $this->value = call_user_func($this->function, $this->parameters);
      $this->dirty = FALSE;
    }
    return $this->value;
  }

}
