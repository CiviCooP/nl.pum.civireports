<?php

/*
 +--------------------------------------------------------------------+
 | Specific report for PUM to Match Experts                           |
 | Author : Erik Hommel (CiviCooP) <erik.hommel@civicoop.org          |
 | Date   : 16 Jul 2014                                               |
 | Copyright (C) 2014 Coöperatieve CiviCooP U.A.                      |
 | <http://www.civicoop.org>                                          |
 | Licensed to PUM <http://www.pum.nl> and CiviCRM under AGPL-3.0     |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Civireports_Form_Report_FindExpert extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_customGroupExtends = array('Individual');
  
  protected $_expertRelationshipTypeId = NULL;

  function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = FALSE;
    $this->_customGroupFilters = TRUE;
    $this->setExpertRelationshipTypeId('Expert');
    $genderOptions = $this->getGenderOptions();
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'display_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
          ),
          'middle_name' => array(
            'title' => ts('Middle Name')
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name')),
          'gender_id' =>
          array(
            'title' => ts('Gender'), 
            'type' => CRM_Utils_Type::T_INT, 
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $genderOptions
          ),
        ),
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'email' =>
          array('title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'street_address' =>
          array('default' => TRUE),
          'city' =>
          array('default' => TRUE),
          'postal_code' => NULL,
          ),
        ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'phone' => NULL,   
        ),
      ),
    );

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_country') {
              $this->_countryField = TRUE;
            }

            $alias = "{$tableName}_{$fieldName}";
            $select[] = "{$field['dbAlias']} as {$alias}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ) ";

    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
            LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1) ";
    }

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }

    if ($this->isTableSelected('civicrm_country')) {
      $this->_from .= "
            LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']}
                   ON {$this->_aliases['civicrm_address']}.country_id = {$this->_aliases['civicrm_country']}.id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ";
    }
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    /*
     * only contact type Expert
     */
    $this->_whereClauses[] .= '(contact_civireport.contact_sub_type LIKE CONCAT ("%'.
      CRM_Core_DAO::VALUE_SEPARATOR.'Expert'.CRM_Core_DAO::VALUE_SEPARATOR.'%"))';
    $sql = $this->buildQuery(TRUE);
    //CRM_Core_Error::debug('sql', $sql);
    //exit();

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('latest_main', $row) && array_key_exists('case_id', $row)) {
        $caseUrl = CRM_Utils_System::url('civicrm/contact/view/case', 'reset=1&id='.$row['case_id'].'&cid='.$row['client_id'].'&action=view', $this->_absoluteUrl);
        $rows[$rowNum]['latest_main_link'] = $caseUrl;
        $rows[$rowNum]['latest_main_hover'] = ts("Click to manage main activity");
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_contact_display_name', $row) && array_key_exists('civicrm_contact_id', $row)) {
        $contactUrl = CRM_Utils_system::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'], $this->_absoluteUrl);
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $contactUrl;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("Click to view contact");
        $entryFound = TRUE;
      }
      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
  /**
   * Function to retrieve gender options
   */
  function getGenderOptions() {
    $genderOptions = array();
    try {
      $apiGenderOptions = civicrm_api3('OptionValue', 'Get', array('option_group_id' => 3));
      foreach($apiGenderOptions['values'] as $genderOption) {
        $genderOptions[$genderOption['value']] = $genderOption['label'];
      }
    } catch (CiviCRM_API3_Exception $ex) {
      return $genderOptions;
    }
    $genderOptions[0] = '- select -';
    asort($genderOptions);
    return $genderOptions;
  }
  /**
   * Local function whereClause to make sure no where clause is ended
   * when option 0 (- select -) is selected
   */
  function whereClause(&$field, $op, $value, $min, $max) {
    $type = CRM_Utils_Type::typeToString(CRM_Utils_Array::value('type', $field));
    $clause = NULL;

    switch ($op) {
      case 'bw':
      case 'nbw':
        if (($min !== NULL && strlen($min) > 0) ||
          ($max !== NULL && strlen($max) > 0)
        ) {
          $min     = CRM_Utils_Type::escape($min, $type);
          $max     = CRM_Utils_Type::escape($max, $type);
          $clauses = array();
          if ($min) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} >= $min )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} < $min )";
            }
          }
          if ($max) {
            if ($op == 'bw') {
              $clauses[] = "( {$field['dbAlias']} <= $max )";
            }
            else {
              $clauses[] = "( {$field['dbAlias']} > $max )";
            }
          }

          if (!empty($clauses)) {
            if ($op == 'bw') {
              $clause = implode(' AND ', $clauses);
            }
            else {
              $clause = implode(' OR ', $clauses);
            }
          }
        }
        break;

      case 'has':
      case 'nhas':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            $value = "'%{$value}%'";
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP $value )";
        }
        break;

      case 'in':
      case 'notin':
        if ($value !== NULL && is_array($value) && count($value) > 0) {
          $sqlOP = $this->getSQLOperator($op);
          if (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_STRING) {
            //cycle through selections and esacape values
            foreach ($value as $key => $selection) {
              $value[$key] = CRM_Utils_Type::escape($selection, $type);
            }
            $clause = "( {$field['dbAlias']} $sqlOP ( '" . implode("' , '", $value) . "') )";
          }
          else {
            // for numerical values
            $clause = "{$field['dbAlias']} $sqlOP (" . implode(', ', $value) . ")";
          }
          if ($op == 'notin') {
            $clause = "( " . $clause . " OR {$field['dbAlias']} IS NULL )";
          }
          else {
            $clause = "( " . $clause . " )";
          }
        }
        break;

      case 'mhas':
        // mhas == multiple has
        if ($value !== NULL && count($value) > 0) {
          $sqlOP = $this->getSQLOperator($op);
          $clause = "{$field['dbAlias']} REGEXP '[[:<:]]" . implode('|', $value) . "[[:>:]]'";
        }
        break;

      case 'sw':
      case 'ew':
        if ($value !== NULL && strlen($value) > 0) {
          $value = CRM_Utils_Type::escape($value, $type);
          if (strpos($value, '%') === FALSE) {
            if ($op == 'sw') {
              $value = "'{$value}%'";
            }
            else {
              $value = "'%{$value}'";
            }
          }
          else {
            $value = "'{$value}'";
          }
          $sqlOP = $this->getSQLOperator($op);
          $clause = "( {$field['dbAlias']} $sqlOP $value )";
        }
        break;

      case 'nll':
      case 'nnll':
        $sqlOP = $this->getSQLOperator($op);
        $clause = "( {$field['dbAlias']} $sqlOP )";
        break;

      default:
        if ($value !== NULL && strlen($value) > 0) {
          if (isset($field['clause'])) {
            // FIXME: we not doing escape here. Better solution is to use two
            // different types - data-type and filter-type
            $clause = $field['clause'];
          }
          else {
            /*
             * hack : if field = gender and value is 0, no clause
             */
            if ($field['title'] === 'Gender' && $value != 0) {
              $value = CRM_Utils_Type::escape($value, $type);
              $sqlOP = $this->getSQLOperator($op);
              if ($field['type'] == CRM_Utils_Type::T_STRING) {
                $value = "'{$value}'";
              }
              $clause = "( {$field['dbAlias']} $sqlOP $value )";
            }
          }
        }
        break;
    }

    if (CRM_Utils_Array::value('group', $field) && $clause) {
      $clause = $this->whereGroupClause($field, $value, $op);
    }
    elseif (CRM_Utils_Array::value('tag', $field) && $clause) {
      // not using left join in query because if any contact
      // belongs to more than one tag, results duplicate
      // entries.
      $clause = $this->whereTagClause($field, $value, $op);
    }
    return $clause;
  }
  /**
   * Function buildRows to add the latest main activity for expert
   */
  function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }
      // section headers not selected for display need to be added to row
      foreach ($unselectedSectionColumns as $key => $values) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }
      /*
       * add latest main activity
       */
      $latestMainActivity = $this->getLatestMainActivity($row['civicrm_contact_id']);
      if (!empty($latestMainActivity)) {
        $row['case_id'] = $latestMainActivity['case_id'];
        $row['client_id'] = $latestMainActivity['client_id'];
        $row['latest_main'] = $latestMainActivity['label'];
      }
      $rows[] = $row;
    }
  }
  /**
   * Function to check if we already have row for expert and if so, add language
   */
  /**
   * local function modifyColumnHeaders to add latest main activity header
   */
  function modifyColumnHeaders() {
    $this->_columnHeaders['latest_main'] = array('type' => 2, 'title' => 'Latest Main Act.');
  }

  /**
   * Function to retrieve the latest main activity of Expert
   * - get cases where Expert is active using the relationship api
   * - get case details and create text
   */
  function getLatestMainActivity($contactId) {
    $latestMainActivity = array();
    $caseId = 0;
    if (!empty($this->_expertRelationshipTypeId)) {
      $caseIds = array();
      $params = array('relationship_type_id' => $this->_expertRelationshipTypeId, 
        'contact_id_b' => $contactId, 'options' => array('sort' => 'start_date DESC'));
      $relationships = civicrm_api3('Relationship', 'Get', $params);
      foreach($relationships['values'] as $relationship) {
        if (isset($relationship['case_id']) && !empty($relationship['case_id'])) {
          if (empty($caseId)) {
            $caseId = $relationship['case_id'];
            $latestMainActivity['case_id'] = $caseId;
          }
        }
      }
      if (isset($caseId) && !empty($caseId)) {
        $case = civicrm_api3('Case', 'Getsingle', array('case_id' => $caseId));
        $statusLabel = $this->getCaseStatusLabel($case['status_id']);
        foreach ($case['contacts'] as $caseContact) {
          if ($caseContact['role'] == 'Client') {
            $clientName = $caseContact['display_name'];
            $latestMainActivity['client_id'] = $caseContact['contact_id'];
          }
        }
        $latestMainActivity['label'] = $case['subject'].' - '.$clientName.' - '.$statusLabel;
      }
    }
    return $latestMainActivity;
  }
  /**
   * Function to get case status label
   */
  function getCaseStatusLabel($caseStatusId) {
    $statusLabel = '';
    $optionGroupParams = array('name' => 'case_status', 'return' => 'id');
    try {
      $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue', $optionGroupParams);
      $optionValueParams = array('option_group_id' => $optionGroupId, 'value' => $caseStatusId, 'return' => 'label');
      try {
        $statusLabel = civicrm_api3('OptionValue', 'Getvalue', $optionValueParams);
      } catch (CiviCRM_API3_Exception $ex) {
        return $statusLabel;
      }
    } catch (CiviCRM_API3_Exception $ex) {
      return $statusLabel;
    }
    return $statusLabel;
  }
  /**
   * Function to set the expert relationship type id for incoming name
   */
  function setExpertRelationshipTypeId($expertName) {
    $params = array('name_a_b' => trim($expertName), 'return' => 'id');
    try {
      $this->_expertRelationshipTypeId = civicrm_api3('RelationshipType', 'Getvalue', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_expertRelationshipTypeId = 0;
    }
    return;
  }
}

