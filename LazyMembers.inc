<?php
// @codingStandardsIgnoreStart
/**
 * @file
 */
module_load_include('inc', 'php_lib', 'Members');

/**
 * Lazy Members.
 */
class LazyMembers extends Members {

  /**
   *
   * @var array
   */
  protected $functions;

  /**
   * Create a LazyMembers object.
   *
   * @param array $members
   * @param array $params
   */
  public function __construct(array $members, array $params = NULL) {
    parent::__construct($members, $params);
  }

  /**
   * Checks to see if a member is lazy.
   *
   * @param string $name
   *
   * @return boolean
   */
  public function isLazy($name) {
    return $this->exists($name) && ($this->values[$name] instanceof LazyMember);
  }

  /**
   * Set the member is as dirty.
   *
   * @param string $name
   */
  public function setDirty($name) {
    if ($this->isLazy($name)) {
      $this->values[$name]->dirty = TRUE;
    }
  }

  /**
   * Get a value for the given member if it exists.
   *
   * Return NULL if the member doesn't exist or isn't set.
   *
   * @param string $name
   *
   * @return mixed
   */
  public function __get($name) {
    if ($this->isLazy($name)) {
      return $this->values[$name]();
    }
    elseif ($this->exists($name)) {
      return $this->values[$name];
    }
    return NULL;
  }

  /**
   * Set the value for the given member if it exists.
   *
   * Note that this doesn't create a new element.
   *
   * @param string $name
   * @param mixed $value
   */
  public function __set($name, $value) {
    if ($this->has($name) && !$this->isLazy($name)) {
      $this->values[$name] = $value;
    }
  }

}
// @codingStandardsIgnoreEnd
