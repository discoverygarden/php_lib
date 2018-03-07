<?php

namespace Drupal\php_lib;

use DOMDocument;

/**
 * Validates DOMDocuments.
 */
class XMLDocumentValidator {

  /**
   * The type of validation.
   *
   * @var XMLValidatorType
   */
  private $type;

  /**
   * The filename of the validition document.
   *
   * @var string
   */
  private $filename;

  /**
   *
   * @param XMLValidatorType $type
   * @param string $filename
   */
  public function __construct(XMLSchemaFormat $type, $filename = NULL) {
    $this->type = $type;
    $this->filename = $filename;
    if ($this->filename === NULL && $type !== XMLSchemaFormat::DTD()) {
      throw new InvalidArgumentException('No file provided.');
    }
  }

  /**
   *
   * @param DOMDocument $document
   * @return boolean
   */
  public function isValid(DOMDocument $document) {
    switch ($this->type) {
      case XMLSchemaFormat::DTD():
        return $document->validate();

      case XMLSchemaFormat::XSD():
        return $document->schemaValidate($this->filename);

      case XMLSchemaFormat::RNG():
        return $document->relaxNGValidate($this->filename);
    }
  }

}
