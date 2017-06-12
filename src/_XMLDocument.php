<?php
namespace Drupal\php_lib;

/**
 * Handles generic XML concepts and data.
 *
 * Underscore prevents collision with the defined class in XML forms, one day they should be the same class.
 */
class _XMLDocument extends DOMDocument {

  /**
   *
   * @var XMLValidator
   */
  protected $validator;
  protected $namepaces;

  /**
   *
   * @var DOMXPath
   */
  protected $xpath;

  /**
   *
   * @param DOMDocument $document
   * @param XMLValidator $validator
   */
  public function __construct(XMLDocumentNamepaces $namespaces, XMLDocumentValidator $validator = NULL) {
    parent::__construct();
    $this->validator = $validator;
    $this->xpath = new DOMXPath($this);
    $this->namespaces = $namespaces;
    $this->namespaces->registerNamespaces($this->xpath);
  }

  /**
   * Loads an XML document from a file.
   *
   * @param string $filename
   *   The path to the XML document.
   * @param int $options
   *   Bitwise OR of the libxml option constants.
   */
  public function load($filename, $options = NULL) {
    $ret = parent::load($filename, $options);
    if ($ret) {
      $this->xpath = new DOMXPath($this);
      $this->namespaces->registerNamespaces($this->xpath);
    }
    return $ret;
  }

  /**
   * Loads an XML document from a string.
   *
   * @param string $source
   *   The string containing the XML.
   * @param int $options
   *   Bitwise OR of the libxml option constants.
   *
   * @return
   * Returns TRUE on success or FALSE on failure. If called statically, returns a DOMDocument or FALSE on failure.
   */
  public function loadXML($source, $options = NULL) {
    $ret = parent::loadXML($source, $options);
    if ($ret) {
      $this->xpath = new DOMXPath($this);
      $this->namespaces->registerNamespaces($this->xpath);
    }
    return $ret;
  }

  /**
   * Removed function.
   */
  public function loadHTMLFile($filename) {
    throw new Exception(__FUNCTION__ . ' is not supported.');
  }

  /**
   * Removed function.
   */
  public function saveHTML() {
    throw new Exception(__FUNCTION__ . ' is not supported.');
  }

  /**
   * Checks if this XML document is valid.
   *
   * If no method of validation is provided its always assumed to be valid.
   *
   * @return boolean
   *   TRUE if valid, FALSE otherwise.
   */
  public function isValid() {
    return isset($this->validator) ? $this->validator->isValid($this) : TRUE;
  }

}
