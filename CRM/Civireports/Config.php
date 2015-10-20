<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 28 Nov 2014
 */

class CRM_Civireports_Config {
  
  protected static $_singleton;
  
  protected $nationality_custom_group = null;
  protected $language_custom_group = null;
  protected $language_level_custom_field = null;
  protected $expert_data_custom_group = null;
  protected $expert_start_date_custom_field = null;
  protected $expert_end_date_custom_field = null;


  protected function __construct() {
    $this->expert_data_custom_group = $this->getCustomGroup('expert_data');
    $this->language_custom_group = $this->getCustomGroup('Languages');
    $this->nationality_custom_group = $this->getCustomGroup('Nationality');
    $this->expert_end_date_custom_field = $this->getCustomField($this->expert_data_custom_group['id'], 
      'expert_status_end_date');
    $this->expert_start_date_custom_field = $this->getCustomField($this->expert_data_custom_group['id'], 
      'expert_status_start_date');
    $this->language_level_custom_field = $this->getCustomField($this->language_custom_group['id'], 
      'Level');
  }
  
  /**
   * @return CRM_Civireports_Config 
   */
  public static function singleton() {
    if (!self::$_singleton) {
      self::$_singleton = new CRM_Civireports_Config;
    }
    return self::$_singleton;
  }
  
  public function getExpertDataCustomGroup() {
    return $this->expert_data_custom_group;
  }
  
  public function getLanguageCustomGroup() {
    return $this->language_custom_group;
  }
  
  public function getNationalityCustomGroup() {
    return $this->nationality_custom_group;
  }
  
  public function getExpertStartDateCustomField() {
    return $this->expert_start_date_custom_field;
  }
  
  public function getExpertEndDateCustomField() {
    return $this->expert_end_date_custom_field;
  }
  
  public function getLanguageLevelCustomField() {
    return $this->language_level_custom_field;
  }
  
  protected function getCustomGroup($custom_group_name) {
    try {
      $custom_group = civicrm_api3('CustomGroup', 'Getsingle', 
        array('name' => $custom_group_name));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom group with name '.$custom_group_name
        .', error from API CustomGroup Getsingle: '.$ex->getMessage());
    }
    return $custom_group;
  }
  
  protected function getCustomField($custom_group_id, $custom_field_name) {
    $params = array(
      'custom_group_id' => $custom_group_id,
      'name' => $custom_field_name
    );
    try {
      $custom_field = civicrm_api3('CustomField', 'Getsingle', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field with name '.$custom_field_name
        .' and custom group id '.$custom_group_id.', error from API CustomField Getsingle: '
        .$ex->getMessage());
    }
    return $custom_field;
  }
}