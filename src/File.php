<?php
namespace Drupal\php_lib;

/**
 * Object Oriented file processing.
 */
class File {

  /**
   * File pointer resource.
   *
   * @var resource
   */
  protected $handle;

  /**
   * Frequently we need to save the current pointer do some processing then return to the old pointer positon this
   * instance variable a LIFO used to achieve that.
   *
   * @var array
   */
  protected $history;

  /**
   * Creates a File instance.
   *
   * Note that the $handle transfers ownership to this class, and should not be closed outside of this class.
   *
   * @param resource $handle
   *   A valid file handle as returned by fopen().
   *
   * @throws InvalidArgumentException
   *   If the $handle provided is not vaild.
   */
  public function __construct($handle) {
    if ($handle === FALSE) {
      throw new InvalidArgumentException(__CLASS__ . ' invalid file handle given.');
    }
    $this->handle = $handle;
  }

  /**
   * Clean up the allocated file for this class.
   */
  public function __destruct() {
    if (isset($this->handle)) {
      fclose($this->handle);
    }
  }

  /**
   * Gets dynamic variables for this class.
   *
   * "pos":
   *   The position of the file pointer referenced by handle as an integer; i.e., its offset
   *   into the file stream. If an error occurs, returns FALSE.
   *
   * @param string $name
   */
  public function __get($name) {
    switch ($name) {
      case 'pos':
        return ftell($this->handle);

      case 'size':
        $stats = $this->stat();
        return $stats['size'];
    }
    if ($name == 'pos') {
      return ftell($this->handle);
    }
    throw new InvalidArgumentException("$name isn't a property of " . __CLASS__);
  }

  /**
   * Passes calls though to PHP filesystem functions using the file handle.
   *
   * @param string $name
   *   The function name.
   * @param array $arguments
   *   The arguments to the given function.
   *
   * @return mixed
   *   Depends on the function called @see the PHP file system docs http://uk.php.net/manual/en/ref.filesystem.php.
   */
  public function __call($name, $arguments) {
    $functions = array('rewind', 'fgetc', 'fgets', 'fstat');
    $aliases = array(
      'getc' => 'fgetc',
      'gets' => 'fgets',
      'read' => 'fread',
      'stat' => 'fstat',
    );
    // Alias for PHP filesystem functions, for cleaner looks.
    $exists = array_search($name, $functions) !== FALSE;
    // Function exists.
    $aliased = array_key_exists($name, $aliases);
    if ($exists || $aliased) {
      array_unshift($arguments, $this->handle);
      // Handle is always the first parameter.
      return call_user_func_array($aliased ? $aliases[$name] : $name, $arguments);
    }
    throw new InvalidArgumentException("$name isn't a method of " . __CLASS__);
  }

  /**
   * Sets the position of the file pointer.
   *
   * @param int $offset
   *   An offset in bytes to move the pointer from the specified $whence value.
   *   Can be positve or negative.
   * @param int $whence
   *   The context in which to evaluate the given $offset. The excepted values are:
   *    SEEK_SET - Set position equal to offset bytes.
   *    SEEK_CUR - Set position to current location plus offset.
   *    SEEK_END - Set position to end-of-file plus offset.
   * @param bool $eofAllow this function to seek passed the EOF.
   *   Allow this function to seek passed the EOF.
   *
   * @return boolean
   *   TRUE if the seek succeeded, FALSE otherwise.
   */
  public function seek($offset = NULL, $whence = SEEK_SET, $eof = TRUE) {
    $ret = fseek($this->handle, $offset, $whence) == 0;
    if (!$eof && $this->EOF()) {
      // Not passed EOF.
      fseek($this->handle, 0, SEEK_END);
      return FALSE;
    }
    return $ret;
  }

  /**
   * Checks to see if the file pointer is at the begining of the file.
   *
   * @return boolean
   *   TRUE if this position is at the start of the file FALSE otherwise.
   */
  public function start() {
    return $this->pos == 0;
  }

  /**
   * Checks if the file pointer is on EOF character.
   *
   * @return boolean
   *   TRUE if the file pointer is on a EOF character FALSE otherwise.
   */
  public function EOF() {
    /**
     * feof() is not always return TRUE when the file pointer is on the EOF character. It requires an attempt
     * to read the EOF character to be set, and will not be set if you simply seek to the EOF character.
     */
    return $this->peekc() === FALSE;
  }

  /**
   * Peeks at the current character.
   *
   * @return string
   *   The single character the file pointer is currently pointing at. FALSE when the character is EOF.
   */
  public function peekc() {
    $this->push();
    $c = fgetc($this->handle);
    $this->pop();
    return $c;
  }

  /**
   * Peeks at the current line.
   *
   * Reading ends when length - 1 bytes have been read, on a newline (which is included in the return value),
   * or on EOF (whichever comes first). If no length is specified, it will keep reading from the stream until
   * it reaches the end of the line.
   *
   * @param int $length
   *   The max number of bytes to read from the current line, it must be a positive value greater than 0.
   *
   * @return string
   *   The current line up to the given $length -1 or the last EOL character encounter, or FALSE if EOF.
   */
  function peeks($length = 0) {
    $this->push();
    $s = $length > 0 ? fgets($this->handle, $length) : fgets($this->handle);
    $this->pop();
    return $s;
  }

  /**
   * Peeks $length bytes from $offset from the file pointer position.
   *
   * @param int $offset
   *   The offset to move the file pointer before reading.
   * @param int $length
   *   The max number of bytes to peek.
   * @param bool $eofAllow this function to seek passed the EOF.
   *   Allow this function to seek passed the EOF.
   *
   * @return string
   *   The peeked bytes.
   */
  function peek($offset, $length, $eof = TRUE) {
    $this->push();
    $this->seek($offset, SEEK_CUR, $eof);
    $ret = $this->read($length);
    $this->pop();
    return $ret;
  }

  /**
   * Pushes the current positon onto the stack.
   */
  protected function push() {
    $this->history[] = $this->pos;
  }

  /**
   * Pops the last position of the stack.
   */
  protected function pop() {
    if (!empty($this->history)) {
      $this->seek(array_pop($this->history));
    }
  }

}
