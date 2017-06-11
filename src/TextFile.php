<?php
namespace Drupal\php_lib;

/**
 * Extends the File class to recognize line endings.
 */
class TextFile extends File {
  /**
   * Supported line ending formats.
   *
   * End of line (EOL) sequences
   *   Unix: "\n"   0x0a
   *   DOS:  "\r\n" 0x0d0a
   *   MAC:  "\r"   0x0d.
   */
  const UNIX = 1;
  const DOS = 2;
  const MAC = 3;

  /**
   * Endings.
   *
   * @array
   */
  static protected $formats = array(self::UNIX, self::DOS, self::MAC);

  /**
   * Endings.
   *
   * @array
   */
  static protected $endings = array(self::UNIX => "\n", self::DOS => "\r\n", self::MAC => "\r");

  /**
   * The expected line ending format.
   *
   * @var int
   */
  protected $format;

  /**
   * The expected line ending value.
   *
   * @var string
   */
  protected $ending;

  /**
   * Creates a TextFile instance.
   *
   * @param resource $handle
   *   A valid file handle as returned by fopen().
   * @param int $format
   *   A valid line ending format for this file.
   *
   * @throws InvalidArgumentException
   *   If the $handle or $format provided is not vaild.
   */
  public function __construct($handle, $format = NULL) {
    parent::__construct($handle);
    $format = isset($format) ? $format : $this->detectFormat();
    if (array_search($format, self::$formats) === FALSE) {
      throw new InvalidArgumentException(t('Invalid format given for ' . __CLASS__));
    }
    $this->format = $format;
    $this->ending = self::$endings[$format];
  }

  /**
   * Reads the file and attempts to guess the format.
   *
   * @return string
   */
  public function detectFormat() {
    /**
     * @todo implment
     */
    return self::UNIX;
  }

  /**
   * Checks if the file pointer is on EOL character.
   *
   * End of line (EOL) sequences
   *  Windows end of line sequence:  "\r\n"
   *  Unix end of line sequence: "\n"
   *  Mac end of line sequence: "\r"
   *
   * @param int $format
   *   Used to return the format discovered.
   *
   * @return boolean
   *   TRUE if the file pointer is on a EOL character FALSE otherwise.
   */
  public function EOL() {
    switch ($this->format) {
      case self::UNIX:
        return strcmp($this->peekc(), "\n") == 0;

      case self::DOS:
        return (strcmp($this->peek(0, 2), "\r\n") == 0 || strcmp($this->peek(-1, 2), "\r\n") == 0);

      case self::MAC:
        return strcmp($this->peekc(), "\r") == 0;
    }
    return FALSE;
  }

  /**
   * If the file pointer is on an EOL character(s) move it to the last EOL character(s) in the EOL.
   *
   * Really only needed for multibyte line endings such as DOS.
   *
   * @return int
   *   The current position.
   */
  public function seekLastEOL() {
    if ($this->format == self::DOS && $this->EOL()) {
      if (strcmp($this->peekc(), "\r") == 0) {
        $this->seek(1, SEEK_CUR);
      }
    }
  }

  /**
   * If the file pointer is on an EOL character(s) move it infront of the EOL character(s).
   *
   * @return int
   *   The current position.
   */
  public function seekBeforeEOL() {
    if ($this->EOL()) {
      $c = $this->peekc();
      $move = ($this->format & self::DOS) && strcasecmp($c, "\n") ? 2 : 1;
      // DOS is the only two character EOL.
      $this->seek(-$move, SEEK_CUR);
    }
    return $this->pos;
  }

  /**
   * If the file pointer is on an EOL character(s) move it past the EOL character(s).
   *
   * Only runs once in that if you have multiple lines with only EOL characters on them this
   * will only move forward one line.
   *
   * @return int
   *   The current position.
   */
  public function seekAfterEOL() {
    if ($this->EOL()) {
      $c = $this->peekc();
      $move = ($this->format & self::DOS) && strcasecmp($c, "\r") ? 2 : 1;
      // DOS is the only two character EOL.
      $this->seek($move, SEEK_CUR, FALSE);
      // Don't allow this function to go passed the EOF.
    }
    return $this->pos;
  }

  /**
   * Moves the pointer the start of the line in which it currently is on.
   *
   * If we are to think of the file as a single stream of characters, going left to right.
   * The start of the line is defined as the leftmost character including the current position
   * that is not the previous lines EOL character if there is no previous line then its position 0.
   *
   * @return int
   *   The current position.
   */
  public function seekLineStart() {
    if ($this->EOF()) {
      $this->seek(0, SEEK_END);
      // Make sure the pointer isn't passed the EOF.
    }
    $this->seekBeforeEOL();
    // If we are on the EOL character for our line move infront of it.
    /**
     * Now on a non-EOL character of this line or in the case where this line
     * is only an EOL character(s) the previous lines EOL character. Or in the case
     * where this line is the first line and is only an EOL character we are at position 0
     */
    do {
      /**
       * Note that this could be speed up by reading large chunks of the file and then
       * processing them but this is easier/safer for the moment.
       */
      if ($this->EOL()) {
        // We are on the previous line, move back to our line.
        $this->seekAfterEOL();
        break;
      }
    } while ($this->seek(-1, SEEK_CUR));
    // Keep looking for the previous line will stop at 0.
    return $this->pos;
  }

  /**
   * Moves the pointer the end of the line in which it currently is on.
   *
   * If we are to think of the file as a single stream of characters, going left to right.
   * The end of the line is defined as the rightmost character including the current position
   * that is the last EOL character in the set of EOL character that define a line ending. As defined below.
   *
   * Exceptional cases:
   *  If there is no EOL character on the current line only a EOF character this function will move to the EOF position.
   *  If the file pointer is pass the EOF character, this function will return the pointer to the EOF character.
   *
   * End of line (EOL) sequences
   *  Windows end of line sequence:  "\r\n"
   *  Unix end of line sequence: "\n"
   *  Mac end of line sequence: "\r"
   *
   * @return int
   *   The current position.
   */
  public function seekLineEnd() {
    if ($this->EOF()) {
      $this->seek(0, SEEK_END);
      // Make sure the pointer isn't passed the EOF.
    }
    do {
      /**
       * Note that this could be speed up by reading large chunks of the file and then
       * processing them but this is easier/safer for the moment.
       */
      if ($this->EOL()) {
        $this->seekLastEOL();
        break;
      }
    } while ($this->seek(1, SEEK_CUR, FALSE));
    // Keep looking for the end of this line stop at EOF.
    return $this->pos;
  }

  /**
   * Seeks to the end of previous line.
   *
   * Exceptional cases:
   *  If the file pointer is on the first line it will be moved to positon 0.
   *
   * @return int
   *   The current position.
   */
  public function seekPrevLineEnd() {
    $this->seekLineStart();
    return $this->seek(-1, SEEK_CUR);
    // Move to previous line, if position is 0 nothing happens.
  }

  /**
   * Seeks to the beginning of previous line.
   *
   * @return int
   *   The current position.
   */
  public function seekPrevLineStart() {
    $this->seekPrevLineEnd();
    return $this->seekLineStart();
  }

  /**
   * Seeks to the beginning of previous line.
   *
   * @return int
   *   The current position.
   */
  public function seekNextLineStart() {
    $this->seekLineEnd();
    if (!$this->EOF()) {
      // Don't move pass the EOF.
      $this->seek(1, SEEK_CUR);
    }
    return $this->pos;
  }

  /**
   * Seeks to the end of previous line.
   *
   * Exceptional cases:
   *  If the file pointer is on the first line it will be moved to positon 0.
   *
   * @return int
   *   The current position.
   */
  public function seekNextLineEnd() {
    $this->seekNextLineStart();
    return $this->seekLineEnd();
  }

  /**
   * Sets the position of the file pointer at the start of the line defined by offset from $whence.
   *
   * Will not move the pointer past the start/end of the file.
   *
   * @param int $offset
   *   An offset in lines to move the pointer from the specified $whence value.
   *   Can be positve or negative.
   * @param int $whence
   *   The context in which to evaluate the given $offset. The excepted values are:
   *    SEEK_SET - Set position equal to offset lines.
   *    SEEK_CUR - Set position to current location plus lines.
   *    SEEK_END - Set position to end-of-file plus lines.
   *
   * @return boolean
   *   TRUE if the seek succeeded, FALSE otherwise.
   */
  public function seekLine($offset, $whence = SEEK_SET) {
    $this->seek(0, $whence);
    $this->seekLineStart();
    $forward = $offset >= 0 ? TRUE : FALSE;
    for ($i = 0; $i < $offset; $i++) {
      $forward ? $this->seekNextLineStart() : $this->seekPrevLineStart();
    }
    return $success;
  }

  /**
   * Similar to fgets but respects the files encoding.
   *
   * Fgets is significatly faster but this is only noticible on large files with 10,000 or more lines.
   */
  public function getLine() {
    if (feof($this->handle)) {
      return FALSE;
    }
    $start = ftell($this->handle);
    $buffer = '';
    $offset = 0;
    while (!feof($this->handle)) {
      $buffer .= fread($this->handle, 128);
      if (($pos = strpos($buffer, $this->ending, $offset)) !== FALSE) {
        fseek($this->handle, $start + $pos + strlen($this->ending), SEEK_SET);
        return substr($buffer, 0, $pos);
      }
      /**
       * If it didn't match maybe the first character was at the
       * end of the line since encoding is at most 2 characters.
       */
      $offset = strlen($buffer) - 2;
    }

    return strlen($buffer) == 0 ? FALSE : $buffer;
  }

}
