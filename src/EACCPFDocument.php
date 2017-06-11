<?php
namespace Drupal\php_lib;

/**
 *
 */
class EACCPFDocument extends _XMLDocument {

  /**
   * Creates an EACCPFDocument from a valid template.
   */
  public static function fromTemplate($id, EACCPFType $type, $agency, EACCPFAgentType $agent_type, $agent) {
    if ($type == EACCPFType::SINGLE_IDENTITY()) {
      $filename = drupal_get_path('module', 'islandora_metadata') . '/templates/eac-cpf.xml';
    }
    else {
      throw new InvalidArgumentException('Only EACCPFType::SINGLE_IDENTITY is supported for now.');
    }
    $doc = new EACCPFDocument();
    $doc->load($filename);
    $doc->setRecordID($id);
    $doc->setMaintenanceAgency($agency);
    $doc->setMaintenanceStatus(EACCPFMaintenceStatusType::created());
    $doc->addMaintenanceEvent(EACCPFMaintenceEventType::created(), $agent_type, $agent, 'Created EAC-CPF Record.');
    return $doc;
  }

  /**
   * Creates an EACCPF instance.
   */
  public function __construct() {
    $validator = new XMLDocumentValidator(XMLSchemaFormat::XSD(), drupal_get_path('module', 'islandora_metadata') . '/xsd/cpf.xsd');
    $namespaces = new XMLDocumentNamepaces('urn:isbn:1-931666-33-4', array(
      'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
      'xlink' => 'http://www.w3.org/1999/xlink',
    )
    );
    parent::__construct($namespaces, $validator);
  }

  /**
   * Sets the ID for this record.
   *
   * @param string $id
   *   The record ID.
   *
   * @return boolean
   *   TRUE if successful, FALSE otherwise.
   */
  public function setRecordID($id) {
    $results = $this->xpath->query('/default:eac-cpf/default:control/default:recordId');
    if ($results->length == 1) {
      $element = $results->item(0);
      $element->nodeValue = (string) $id;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set the Maintenance Status of this document.
   *
   * @param EACCPFMaintenceStatusType $status
   *   The maintenance status.
   *
   * @return boolean
   *   TRUE on success FALSE otherwise.
   */
  public function setMaintenanceStatus(EACCPFMaintenceStatusType $status) {
    $results = $this->xpath->query('/default:eac-cpf/default:control/default:maintenceStatus');
    if ($results->length == 1) {
      $element = $results->item(0);
      $element->nodeValue = (string) $status;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set the Maintenance Status of this document.
   *
   * @param EACCPFMaintenceStatusType $status
   *   The maintenance status.
   *
   * @return boolean
   *   TRUE on success FALSE otherwise.
   */
  public function setMaintenanceAgency($agency) {
    $results = $this->xpath->query('/default:eac-cpf/default:control/default:maintenanceAgency/default:agencyName');
    if ($results->length == 1) {
      $element = $results->item(0);
      $element->nodeValue = (string) $agency;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds a Maintence Event.
   *
   * @param EACCPFMaintenceEventType $event_type
   *   The event type.
   * @param EACCPFAgentType $agent_type
   *   The agent type.
   * @param string $agent
   *   The current user performing the maintence event.
   * @param string $description
   *   A description for the event.
   *
   * @return boolean
   *   TRUE on success FALSE otherwise.
   */
  public function addMaintenanceEvent(EACCPFMaintenceEventType $event_type, EACCPFAgentType $agent_type, $agent, $description, $date = NULL) {
    $date = isset($data) ? $date : date("Y-m-d");
    $standard_date_time = new DateTime($date);
    $standard_date_time = $standard_date_time->format("Y-m-d");
    $results = $this->xpath->query('/default:eac-cpf/default:control/default:maintenanceHistory');
    if ($results->length == 1) {
      $history = $results->item(0);
      $default_uri = $this->namespaces->getDefaultURI();
      $event = $this->createElementNS($default_uri, 'maintenanceEvent');
      $history->appendChild($event);
      $event->appendChild($this->createElementNS($default_uri, 'eventType', (string) $event_type));
      $date = $this->createElementNS($default_uri, 'eventDateTime', $date);
      $date->setAttribute('standardDateTime', $standard_date_time);
      $event->appendChild($date);
      $event->appendChild($this->createElementNS($default_uri, 'agentType', (string) $agent_type));
      $event->appendChild($this->createElementNS($default_uri, 'agent', $agent));
      $event->appendChild($this->createElementNS($default_uri, 'eventDescription', $description));
      return TRUE;
    }
    return FALSE;
  }

  /**
   *
   */
  public function cpfDescription() {

  }

}
