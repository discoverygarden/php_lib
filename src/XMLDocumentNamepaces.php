<?php
namespace Drupal\php_lib;

/**
 *
 */
class XMLDocumentNamepaces {
  /**
   * Constants.
   */
  const xmlns = 'http://www.w3.org/2000/xmlns/';

  /**
   * The default namespace, it can be NULL.
   *
   * @var string
   */
  protected $default;

  /**
   * An array of namespaces declarations where the key is the prefix and the value is the uri.
   *
   * For example: array('prefix' => 'uri');
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Creates an XMLDocumentNamepaces Instance.
   *
   * @param string $default
   *   The default namespace for the document.
   * @param array $namespaces
   *   An array of namespaces declarations where the key is the prefix and the value is the uri.
   */
  public function __construct($default = NULL, array $namespaces = NULL) {
    $this->default = $default;
    $this->namespaces = isset($namespaces) ? $namespaces : array();
  }

  /**
   * Gets the namespace URI associated with the given namespace prefix if defined.
   *
   * @param string $prefix
   *   The namespace prefix.
   *
   * @return string
   *   The namespace URI if defined FALSE otherwise.
   */
  public function getURI($prefix) {
    return isset($this->namespaces[$prefix]) ? $this->namespaces[$prefix] : FALSE;
  }

  /**
   * Gets the namespace prefix associated with the given namespace URI if defined.
   *
   * @param string $uri
   *   The namespace URI.
   *
   * @return string
   *   The namespace prefix if defined FALSE otherwise.
   */
  public function getPrefix($uri) {
    $prefix = array_search($uri, $this->namespaces);
    return ($prefix !== FALSE) ? $prefix : FALSE;
  }

  /**
   * Gets the default namespace URI if defined.
   *
   * @return string
   *   The default namespace URI, if defined FALSE othewise.
   */
  public function getDefaultURI() {
    return isset($this->default) ? $this->default : FALSE;
  }

  /**
   *
   * @param DOMXPath $xpath
   */
  public function registerNamespaces(DOMXPath $xpath) {
    if ($this->default) {
      $xpath->registerNamespace('default', $this->default);
    }
    foreach ($this->namespaces as $prefix => $uri) {
      $xpath->registerNamespace($prefix, $uri);
    }
  }

}
