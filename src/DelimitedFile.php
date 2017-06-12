<?php
namespace Drupal\php_lib;

/**
 * Extends File class for processing CSV. Allows multiple delimiters and multicharacter delimiters.
 *
 * Fields are defined as the string of characters between any delimited values and/or the start/end of the file.
 *
 * Unlike lines fields do not include there delimiter.
 */
class DelimitedFile extends TextFile {

  /**
   * The delimiters that seperate fields.
   *
   * @var array
   */
  protected $delimiters;

  /**
   * A PREG pattern for matching delimiters.
   *
   * @var string
   */
  protected $pattern;

  /**
   * Creates a DelimitedFile instance.
   *
   * @param resource $handle
   *   A valid file handle as returned by fopen().
   * @param int $format
   *   A valid line ending format for this file.
   * @param mixed $delimiters
   *   A single delimiter or a collection of delimiters that can be any number of characters.
   *
   * @throws InvalidArgumentException
   *   If the $handle or $format provided is not vaild.
   */
  public function __construct($handle, $format = self::UNIX, $delimiters = ',') {
    parent::__construct($handle, $format);
    $this->delimiters = is_array($delimiters) ? $delimiters : array($delimiters);
    if (array_search($this->ending, $this->delimiters) === FALSE) {
      $this->delimiters[] = $this->ending;
    }
    $subpatterns = array();
    foreach ($this->delimiters as $delmiter) {
      $subpatterns[] = '(' . preg_quote($delmiter) . ')';
    }
    $this->pattern = '/' . implode('|', $subpatterns) . '/';
  }

  /**
   * Checks if the file pointer is on a delimiter character(s).
   *
   * @return boolean
   *   TRUE if the file pointer is on a delimiter FALSE otherwise.
   */
  public function isDelimiterSafe() {
    $ret = FALSE;
    $this->push();
    if ($this->EOF()) {
      return TRUE;
      // EOF is always a delimiter.
    }
    $c = $this->peekc();
    foreach ($this->delimiters as $delimiter) {
      /**
       * Warning this class currently doesn't support delimited values
       * that contain the same character more than once. Due to the use of strpos below.
       */
      if (($offset = strpos($delimiter, $c)) !== FALSE) {
        $this->push();
        $this->seek(-$offset, SEEK_CUR);
        // Move to the expected start of the delimiter.
        $read = $this->read(strlen($delimiter));
        $this->pop();
        if (strcmp($read, $delimiter) == 0) {
          $ret = TRUE;
          break;
        }
      }
    }
    $this->pop();
    return $ret;
  }

  /**
   * Moves the pointer after the delimiter if the pointer is currently on one.
   */
  public function seekAfterDelimiter() {
    $c = $this->peekc();
    foreach ($this->delimiters as $delimiter) {
      /**
       * Warning this class currently doesn't support delimited values
       * that contain the same character more than once. Due to the use of strpos below.
       */
      if (($offset = strpos($delimiter, $c)) !== FALSE) {
        $length = strlen($delimiter);
        $this->push();
        $this->seek(-$offset, SEEK_CUR);
        // Move to the expected start of the delimiter.
        $read = $this->read($length);
        $this->pop();
        if (strcmp($read, $delimiter) == 0) {
          $this->seek($length - $offset, SEEK_CUR);
          break;
        }
      }
    }
    return $this->pos;
  }

  /**
   * Moves the file pointer to the start of a field.
   *
   * Exceptional cases:
   *  If the file pointer is on the first field it will be moved to positon 0.
   *
   * @return int
   *   The current file pointer position.
   */
  public function seekFieldStart() {
    if ($this->isDelimiter()) {
      return FALSE;
    }
  }

  /**
   * Gets the current delimited field from the point the file pointer is on.
   *
   * Fails if the file pointer is on a delimiter.
   *
   * @return string
   *   The current field if successful FALSE otherwise.
   */
  public function getField() {
    if ($this->EOF()) {

      // No fields remain.
      return FALSE;
    }
    $ret = '';
    while (!$this->isDelimiter()) {
      // Get non delimited characters.
      $ret .= $this->getc();
    }
    $this->seekAfterDelimiter();
    // Move to start of next field.
    return $ret;
  }

  /**
   * Gets a number of fields up to max $count if they exist.
   *
   * @param int $count
   *
   * @return array
   *   The requested fields up to a max of $count.
   */
  public function getFieldsSafe($count) {
    $fields = array();
    while ($count != 0 && ($field = $this->getField()) !== FALSE) {
      $fields[] = $field;
      $count--;
    }
    return empty($fields) ? FALSE : $fields;
  }

  /**
   * Unlike other functions in these classes this one has be optimized for speed.
   *
   * Fgetcsv() is about twice as fast as this function but this supports multiple delimiters, allows for EOL characters
   * to not be considered delimters useful when dealing with mixed line endings.
   *
   * The speed difference is only noticible on large files with 10,000 or more lines.
   *
   * @param int $count
   *   The number of fields to get.
   *
   * @return array
   *   The fields if found, FALSE otherwise.
   */
  public function getFields() {
    $line = $this->getLine();
    return $line === FALSE ? FALSE : preg_split($this->pattern, $line);
  }

  /**
   * Map Matches from getFields().
   *
   * @param array $item
   * @param string or int or object... $key
   */
  private function mapMatches(array &$item, $key) {
    $item = $item[0];
  }

}
