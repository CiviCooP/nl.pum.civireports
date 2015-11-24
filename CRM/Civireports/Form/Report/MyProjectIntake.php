<?php

class CRM_Civireports_Form_Report_MyProjectIntake extends CRM_Report_Form {

  protected $_addressField = FALSE;
  protected $_emailField = FALSE;
  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_customGroupGroupBy = FALSE;

  protected $_from = NULL;
  protected $_where = NULL;

  protected $userSelect = array();
  protected $approveRepTable = NULL;
  protected $approveAnamonTable = NULL;
  protected $approveCcTable = NULL;
  protected $approveScTable = NULL;
  protected $approveRepColumn = NULL;
  protected $approveAnamonColumn = NULL;
  protected $approveCcColumn = NULL;
  protected $approveScColumn = NULL;
  protected $repRelationshiptypeId = NULL;
  protected $ccRelationshipTypeId = NULL;
  protected $counsRelationshipTypeId = NULL;
  protected $scRelationshipTypeId = NULL;
  protected $poRelationshipTypeId = NULL;
  protected $openCaseActivityTypeId = NULL;
  protected $assessRepActivityTypeId = NULL;
  protected $approveAnamonActivityTypeId = NULL;
  protected $approveCcActivityTypeId = NULL;
  protected $approveScActivityTypeId = NULL;
  protected $caseStatusOptionGroupId = NULL;
  protected $projectIntakeCaseTypeId = NULL;

  function __construct() {
    $this->_exposeContactID = FALSE;
    $this->caseStatus = CRM_Case_PseudoConstant::caseStatus();
    $this->country = CRM_Case_PseudoConstant::country();
    $this->setUserSelect();
    $this->setApproveColumns();
    $this->setRelationshipTypes();
    $this->setActivityTypes();
    $this->setOptionGroupIds();

    $this->_columns = array(
      'civicrm_contact' => array(
        'alias' => 'piclient',
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'customer_display_name' => array(
            'name' => 'display_name',
            'title' => ts('Customer'),
            'required' => TRUE,
          ),
          'customer_contact_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
            'default' => TRUE
          ),
        ),
        'filters' => array(
          'user_id' => array(
            'title' => ts('Project Intake for user'),
            'default' => 0,
            'pseudofield' => 1,
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->userSelect,
          ),
        ),
        'order_bys' => array(
          'display_name' => array(
            'title' => 'Customer',
          ),
        ),
      ),
      'civicrm_country' => array(
        'alias' => 'picountry',
        'dao' => 'CRM_Core_DAO_Country',
        'fields' => array(
          'country_name' => array(
            'name' => 'name',
            'title' => 'Country',
            'default' => TRUE
          ),
        ),
        'filters' => array(
          'country_name' => array(
            'title' => ts('Country'),
            'default' => 0,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->country,
          ),
        ),
        'order_bys' => array(
          'name' => array(
            'title' => 'Country',
          ),
        ),
      ),
      'civicrm_case' => array(
        'alias' => 'pi',
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => array(
          'case_id' => array(
            'name' => 'id',
            'title' => ts('Case ID'),
            'required' => TRUE,
            'no_display' => FALSE,
          ),
          'case_status_id' => array(
            'name' => 'status_id',
            'title' => ts('Case Status'),
            'default' => TRUE,
          ),
          'case_subject' => array(
            'name' => 'subject',
            'title' => ts('Case Subject'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'status_id' => array(
            'title' => ts('Case Status'),
            'default' => 0,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->caseStatus,
          ),
          'date_submission' => array(
            'title' => ts('Date Submission'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
        'order_bys' => array(
          'open_case_date' => array(
            'title' => 'Date Submission',
         ),
        ),
      ),
    );

    $this->_groupFilter = FALSE;
    $this->_tagFilter = FALSE;
    $this->_add2groupSupported = FALSE;
    parent::__construct();
  }

  /**
   * Overridden parent method to select fields
   *
   */
  function select() {
    foreach ($this->_columns as $tableName => $tableValues) {
      foreach ($tableValues['fields'] as $fieldName => $fieldValues) {
        $selectClauses[] = $fieldValues['dbAlias']." AS ".$fieldName;
      }
    }
    $this->addSelectClauses($selectClauses);
    $this->_select = 'SELECT '.implode(', ', $selectClauses);
  }

  /**
   * Method to add the additional select clauses
   *
   * @param array &$selectClauses
   */

  private function addSelectClauses(&$selectClauses) {
    $addSelects = array(
      "ov.label AS case_status",
      "piopen.activity_date_time AS date_submission",
      "pirep.display_name AS rep_name",
      "pirep.id AS rep_id",
      "ass.activity_date_time AS ass_date",
      "incc.activity_date_time AS incc_date",
      "insc.activity_date_time AS insc_date",
      "inanamon.activity_date_time AS inanamon_date",
      "cvrep.".$this->approveRepColumn." AS approve_rep",
      "cvanamon.".$this->approveAnamonColumn." AS approve_anamon",
      "cvsc.".$this->approveScColumn." AS approve_sc",
      "cvcc.".$this->approveCcColumn." AS approve_cc"
      );
    foreach ($addSelects as $addSelect) {
      $selectClauses[] = $addSelect;
    }
  }

  /**
   * Method to set the column names for the approve custom fields
   *
   * @access private
   */
  private function setApproveColumns() {
    $approveCustomGroupNames = array(
      "ass" => "Intake",
      "cvanamon" => "Intake_Customer_by_Anamon",
      "cvcc" => "Intake_Customer_by_CC",
      "cvsc" => "Intake_Customer_by_SC");
    foreach ($approveCustomGroupNames as $approveCustomAlias => $approveCustomGroupName) {
      $customGroup = CRM_Threepeas_Utils::getCustomGroup($approveCustomGroupName);
      if (!empty($customGroup)) {
        switch ($approveCustomAlias) {
          case "ass":
            $this->approveRepTable = $customGroup['table_name'];
            $customField = CRM_Threepeas_Utils::getCustomField($customGroup['id'], "Assessment_Rep");
            if (!empty($customField)) {
              $this->approveRepColumn = $customField['column_name'];
            }
            break;
          case "cvanamon":
            $this->approveAnamonTable = $customGroup['table_name'];
            $customField = CRM_Threepeas_Utils::getCustomField($customGroup['id'], "Do_you_approve_the_project_");
            if (!empty($customField)) {
              $this->approveAnamonColumn = $customField['column_name'];
            }
            break;
          case "cvcc":
            $this->approveCcTable = $customGroup['table_name'];
            $customField = CRM_Threepeas_Utils::getCustomField($customGroup['id'], "Conclusion_Do_you_want_to_approve_this_customer_");
            if (!empty($customField)) {
              $this->approveCcColumn = $customField['column_name'];
            }
            break;
          case "cvsc":
            $this->approveScTable = $customGroup['table_name'];
            $customField = CRM_Threepeas_Utils::getCustomField($customGroup['id'], "Conclusion_Do_you_want_to_approve_this_customer_");
            if (!empty($customField)) {
              $this->approveScColumn = $customField['column_name'];
            }
            break;
        }
      }
    }
  }

  /**
   * Overridden parent method to get from clause
   */
  function from() {
    $this->_from = "
      FROM civicrm_case {$this->_aliases['civicrm_case']}
      LEFT JOIN civicrm_option_value ov ON {$this->_aliases['civicrm_case']}.status_id = ov.value
        AND ov.option_group_id = {$this->caseStatusOptionGroupId}
      LEFT JOIN civicrm_case_contact picc ON picc.case_id = {$this->_aliases['civicrm_case']}.id
      LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON picc.contact_id = {$this->_aliases['civicrm_contact']}.id
      LEFT JOIN civicrm_relationship pirel ON pirel.case_id = {$this->_aliases['civicrm_case']}.id
        AND pirel.contact_id_a = {$this->_aliases['civicrm_contact']}.id AND pirel.relationship_type_id = {$this->repRelationshiptypeId}
      LEFT JOIN civicrm_contact pirep ON pirel.contact_id_b = pirep.id
      LEFT JOIN civicrm_address piadr ON piadr.contact_id = {$this->_aliases['civicrm_contact']}.id AND is_primary = 1
      LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']} ON piadr.country_id = {$this->_aliases['civicrm_country']}.id
      LEFT JOIN civicrm_case_activity pica ON pica.case_id = {$this->_aliases['civicrm_case']}.id
      LEFT JOIN civicrm_activity piopen ON pica.activity_id = piopen.id AND piopen.activity_type_id = {$this->openCaseActivityTypeId}
        AND piopen.is_current_revision = 1
      LEFT JOIN civicrm_activity ass ON pica.activity_id = ass.id AND ass.activity_type_id = {$this->assessRepActivityTypeId}
        AND ass.is_current_revision = 1
      LEFT JOIN civicrm_activity incc ON pica.activity_id = incc.id AND incc.activity_type_id = {$this->approveCcActivityTypeId}
        AND incc.is_current_revision = 1
      LEFT JOIN civicrm_activity insc ON pica.activity_id = insc.id AND insc.activity_type_id = {$this->approveScActivityTypeId}
        AND insc.is_current_revision = 1
      LEFT JOIN civicrm_activity inanamon ON pica.activity_id = inanamon.id AND inanamon.activity_type_id = {$this->approveAnamonActivityTypeId}
        AND inanamon.is_current_revision = 1
      LEFT JOIN {$this->approveRepTable} cvrep ON cvrep.entity_id = {$this->_aliases['civicrm_case']}.id
      LEFT JOIN {$this->approveAnamonTable} cvanamon ON cvanamon.entity_id = inanamon.id
      LEFT JOIN {$this->approveCcTable} cvcc ON cvcc.entity_id = incc.id
      LEFT JOIN {$this->approveScTable} cvsc ON cvsc.entity_id = insc.id
      LEFT JOIN civicrm_relationship mypi ON {$this->_aliases['civicrm_case']}.id = mypi.case_id AND mypi.relationship_type_id IN (
      {$this->ccRelationshipTypeId}, {$this->scRelationshipTypeId}, {$this->poRelationshipTypeId}, {$this->counsRelationshipTypeId})";
  }

  function where() {
    $this->_where .= "WHERE ({$this->_aliases['civicrm_case']}.case_type_id LIKE '%".CRM_Core_DAO::VALUE_SEPARATOR.
    $this->projectIntakeCaseTypeId.CRM_Core_DAO::VALUE_SEPARATOR."%') AND {$this->_aliases['civicrm_case']}.is_deleted = 0";

    $this->_where .= $this->setCountryClause();
    $this->_where .= $this->setCaseStatusClause();
    $this->_where .= $this->setUserClause();
    $this->_where .= $this->setDateSubmissionClause();
  }

  /**
   * Method to add the user clause for where
   */
  private function setUserClause() {
    if (!isset($this->_params['user_id_value']) || empty($this->_params['user_id_value'])) {
      $session = CRM_Core_Session::singleton();
      $userId = $session->get('userID');
    } else {
      $userId = $this->_params['user_id_value'];
    }
    return " AND (mypi.contact_id_b = {$userId})";
  }

  /**
   * Method to add the country clause for where
   * @return string
   */
  private function setCountryClause() {
    if ($this->_params['country_name_op'] == "notin") {
      $operator = "NOT IN";
    } else {
      $operator = "IN ";
    }
    $countryClauses = array();
    foreach ($this->_params['country_name_value'] as $clauseId => $countryId) {
      if ($countryId != 0) {
        $countryClauses[] = $countryId;
      }
    }
    if (!empty($countryClauses)) {
      return " AND (piadr.country_id ".$operator." (".implode(", ", $countryClauses)."))";
    } else {
      return "";
    }
  }

  /**
   * Method to add the case status clause for where
   * @return string
   */
  private function setCaseStatusClause() {
    if ($this->_params['status_id_op'] == "notin") {
      $operator = "NOT IN";
    } else {
      $operator = "IN ";
    }
    $caseStatus = array();
    foreach ($this->_params['status_id_value'] as $clauseId => $statusId) {
      if ($statusId != 0) {
        $caseStatus[] = $statusId;
      }
    }
    if (!empty($caseStatus)) {
      return " AND ({$this->_aliases['civicrm_case']}.status_id ".$operator." (".implode(", ", $caseStatus)."))";
    } else {
      return "";
    }
  }

  /**
   * Method to set date submission clauses
   *
   * @return string
   * @access private
   */
  private function setDateSubmissionClause() {
    $dateSubmissionClauses = array();
    if (!empty($this->_params['date_submission_relative'])) {
      $relative = $this->_params['date_submission_relative'];
      $from = $this->_params['date_submission_from'];
      $to = $this->_params['date_submission_to'];
      list($from, $to) = $this->getFromTo($relative, $from, $to, NULL, NULL);
      $from = substr($from, 0, 8);
      $dateSubmissionClauses[] = "(piopen.activity_date_time >= $from)";
      $to = substr($to, 0, 8);
      $dateSubmissionClauses[] = "(piopen.activity_date_time <= $to)";
      $dateSubmissionClause = " AND (" . implode(" AND ", $dateSubmissionClauses)." OR piopen.activity_date_time IS NULL)";
      return $dateSubmissionClause;
    }
    return "";
  }

  function modifyColumnHeaders() {
    $this->_columnHeaders['customer_display_name'] = array('title' => ts("Client"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['country_name'] = array('title' => ts("Country"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['case_subject'] = array('title' => ts("Case Subject"), 'type' => CRM_Utils_Type::T_STRING);
    if (isset($this->_params['fields']['case_status_id']) && $this->_params['fields']['case_status_id'] == 1) {
      $this->_columnHeaders['case_status'] = array('title' => ts("Case status"), 'type' => CRM_Utils_Type::T_STRING);
    }
    $this->_columnHeaders['date_submission'] = array('title' => ts("Date Submission"), 'type' => CRM_Utils_Type::T_DATE);
    $this->_columnHeaders['rep_name'] = array('title' => ts("Representative"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['ass_date'] = array('title' => ts("Date Assessment Rep"), 'type' => CRM_Utils_Type::T_DATE);
    $this->_columnHeaders['approve_rep'] = array('title' => ts("Customer Approved by Rep"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['incc_date'] = array('title' => ts("Date Intake CC"), 'type' => CRM_Utils_Type::T_DATE);
    $this->_columnHeaders['approve_cc'] = array('title' => ts("Customer Approved by CC"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['insc_date'] = array('title' => ts("Date Intake SC"), 'type' => CRM_Utils_Type::T_DATE);
    $this->_columnHeaders['approve_sc'] = array('title' => ts("Customer Approved by SC"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['inanamon_date'] = array('title' => ts("Date Intake Anamon"), 'type' => CRM_Utils_Type::T_DATE);
    $this->_columnHeaders['approve_anamon'] = array('title' => ts("Customer Approved by Anamon"), 'type' => CRM_Utils_Type::T_STRING);
    $this->_columnHeaders['manage_case'] = array('title' => '','type' => CRM_Utils_Type::T_STRING);
  }

  function alterDisplay(&$rows) {
    foreach($rows as $index => $row) {
      if (isset($row['customer_display_name']) && isset($row['customer_display_name'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view" , "action=view&reset=1&cid=". $row['customer_id'], $this->_absoluteUrl);
        $rows[$index]['customer_display_name_link'] = $url;
        $rows[$index]['customer_display_name_hover'] = ts('View Customer');
      }
      if (isset($row['rep_name']) && isset($row['rep_name'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view" , "action=view&reset=1&cid=". $row['rep_id'], $this->_absoluteUrl);
        $rows[$index]['rep_name_link'] = $url;
        $rows[$index]['rep_name_hover'] = ts('View contact');
      }
      if (isset($row['case_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case", 'reset=1&action=view&cid=' . $row['customer_id'] . '&id=' . $row['case_id'], $this->_absoluteUrl);
        $rows[$index]['manage_case'] = ts('Manage');
        $rows[$index]['manage_case_link'] = $url;
        $rows[$index]['manage_case_hover'] = ts("Manage Case");
      }
    }
  }

  /**
   * Method to get the country coordinators, sector coordinators and project officers
   */
  protected function setUserSelect() {
    $ccContacts = CRM_Threepeas_BAO_PumCaseRelation::getAllActiveRelationContacts('country_coordinator');
    $profContacts = CRM_Threepeas_BAO_PumCaseRelation::getAllActiveRelationContacts('project_officer');
    $sectorContacts = CRM_Threepeas_BAO_PumCaseRelation::getAllSectorCoordinators();
    $threepeasConfig = CRM_Threepeas_Config::singleton();
    $projectManagers = array();
    $pmContacts = array();
    $groupContactParams = array('group_id' => $threepeasConfig->projectmanagerGroupId);
    try {
      $projectManagers = civicrm_api3('GroupContact', 'Get', $groupContactParams);
    } catch (CiviCRM_API3_Exception $ex) {
    }
    foreach ($projectManagers['values'] as $projectManager) {
      $pmContacts[$projectManager['contact_id']] = $projectManager['contact_id'];
    }
    $allContacts = $ccContacts + $profContacts + $sectorContacts + $pmContacts;
    ksort($allContacts);
    $this->userSelect[0] = 'current user';
    foreach ($allContacts as $contact) {
      $this->userSelect[$contact] = CRM_Threepeas_Utils::getContactName($contact);
    }
  }

  /**
   * Method to set the relationship type id of the representative
   */
  protected function setRelationshipTypes() {
    $config = CRM_Threepeas_CaseRelationConfig::singleton();
    $this->repRelationshiptypeId = $config->getRelationshipTypeId("representative");
    $this->ccRelationshipTypeId = $config->getRelationshipTypeId("country_coordinator");
    $this->counsRelationshipTypeId = $config->getRelationshipTypeId("counsellor");
    $this->scRelationshipTypeId = $config->getRelationshipTypeId("sector_coordinator");
    $this->poRelationshipTypeId = $config->getRelationshipTypeId("project_officer");
  }

  /**
   * Method to set the activity types that are required for the report
   */
  protected function setActivityTypes() {
    $assesRepActivityType = CRM_Threepeas_Utils::getActivityTypeWithName("Assessment Project Request by Rep");
    $this->assessRepActivityTypeId = $assesRepActivityType['value'];
    $approveCcActivityType = CRM_Threepeas_Utils::getActivityTypeWithName("Intake Customer by CC");
    $this->approveCcActivityTypeId = $approveCcActivityType['value'];
    $approveScActivityType = CRM_Threepeas_Utils::getActivityTypeWithName("Intake Customer by SC");
    $this->approveScActivityTypeId = $approveScActivityType['value'];
    $approveAnamonActivityType = CRM_Threepeas_Utils::getActivityTypeWithName("Intake Customer by Anamon");
    $this->approveAnamonActivityTypeId = $approveAnamonActivityType['value'];
    $openCaseActivityType = CRM_Threepeas_Utils::getActivityTypeWithName("Open Case");
    $this->openCaseActivityTypeId = $openCaseActivityType['value'];
  }

  /**
   * Method to set option group ids that are required for the report
   */
  protected function setOptionGroupIds() {
    try {
      $this->caseStatusOptionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_status', 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      $this->caseStatusOptionGroupId = null;
    }
    try {
      $optionGroupId = civicrm_api3('OptionGroup', 'Getvalue', array('name' => 'case_type', 'return' => 'id'));
      try {
        $optionValueParams = array(
          'option_group_id' => $optionGroupId,
          'name' => 'Projectintake',
          'return' => 'value'
        );
        $this->projectIntakeCaseTypeId = civicrm_api3('OptionValue', 'Getvalue', $optionValueParams);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->projectIntakeCaseTypeId = null;
      }
    } catch (CiviCRM_API3_Exception $ex) {
      $this->projectIntakeCaseTypeId = null;
    }
  }

  /**
   * Overridden parent method to catch to and from dates so they can be used in additional project Rows
   *
   * @param $fieldName
   * @param $relative
   * @param $from
   * @param $to
   * @param null $type
   * @param null $fromTime
   * @param null $toTime
   * @return null|string
   */
  private function openCaseClause($fieldName,
                      $relative, $from, $to, $type = NULL, $fromTime = NULL, $toTime = NULL
  ) {
    $clauses = array();
    if (in_array($relative, array_keys($this->getOperationPair(CRM_Report_Form::OP_DATE)))) {
      $sqlOP = $this->getSQLOperator($relative);
      return "( {$fieldName} {$sqlOP} )";
    }

    list($from, $to) = $this->getFromTo($relative, $from, $to, $fromTime, $toTime);
    /*
     * store from and to in class properties so they can be used in comparison of added rows
     */
    $dateField = "piopen.activity_date_time";
    if ($fieldName == $dateField) {
      $this->dateFrom = $from;
      $this->dateTo = $to;
    }

    if ($from) {
      $from = ($type == CRM_Utils_Type::T_DATE) ? substr($from, 0, 8) : $from;
      $clauses[] = "( {$fieldName} >= $from )";
    }

    if ($to) {
      $to = ($type == CRM_Utils_Type::T_DATE) ? substr($to, 0, 8) : $to;
      $clauses[] = "( {$fieldName} <= {$to} )";
    }

    if (!empty($clauses)) {
      return implode(' AND ', $clauses);
    }
    return NULL;
  }

  /**
   * Overridden parent method to build the report rows
   *
   * @param string $sql
   * @param array $rows
   * @access public
   */
  function buildRows($sql, &$rows) {
    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->modifyColumnHeaders();
    while ($dao->fetch()) {
      //only add to rows if there is no row yet for the case
      if ($this->rowExists($dao->case_id, $rows) == FALSE) {
        $row = array();
        foreach ($this->_columnHeaders as $key => $value) {
          if (property_exists($dao, $key)) {
            $row[$key] = $dao->$key;
          }
        }
        // add specific id's so they can be used to click on the customer name, case subject and rep name
        $row['case_id'] = $dao->case_id;
        $row['customer_id'] = $dao->customer_contact_id;
        $row['rep_id'] = $dao->rep_id;
        $rows[] = $row;
      } else {
        $this->updateRow($dao, $rows);
      }
    }
  }

  /**
   * Method if row already exists for caseId
   * @param int $caseId
   * @param array $rows
   * @return bool
   */
  private function rowExists($caseId, $rows) {
    foreach ($rows as $row) {
      if ($row['case_id'] == $caseId) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Overridden parent method to set the found rows on distinct case_id
   */
  function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    if ($this->_limit && ($this->_limit != '')) {
      $sql              = "SELECT COUNT(DISTINCT({$this->_aliases['civicrm_case']}.id)) ".$this->_from." ".$this->_where;
      $this->_rowsFound = CRM_Core_DAO::singleValueQuery($sql);
      $params           = array(
        'total' => $this->_rowsFound,
        'rowCount' => $rowCount,
        'status' => ts('Records') . ' %%StatusMessage%%',
        'buttonBottom' => 'PagerBottomButton',
        'buttonTop' => 'PagerTopButton',
        'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
      );
      $pager = new CRM_Utils_Pager($params);
      $this->assign_by_ref('pager', $pager);
    }
  }

  /**
   * Overridden parent method for order by's
   */
  function orderBy() {
    $this->_orderBy = "";
    $orderByArray = array();
    foreach ($this->_params['order_bys'] as $orderBy) {
      if (isset($orderBy['column'])) {
        switch ($orderBy['column']) {
          case "display_name":
            $orderByArray[] = "customer_display_name ".$orderBy['order'];
            break;
          case "name":
            $orderByArray[] = "country_name ".$orderBy['order'];
            break;
          case "open_case_date":
            $orderByArray[] = "date_submission ".$orderBy['order'];
            break;
        }
      }
    }
    if (!empty($orderByArray)) {
      $this->_orderBy = "ORDER BY " . implode(", ", $orderByArray);
    }
  }

  /**
   * Method to update row if any of the date or approve columns is set
   *
   * @param object $dao
   * @param array $rows
   */
  private function updateRow($dao, &$rows) {
    foreach ($rows as $rowNum => $row) {
      if ($row['case_id'] == $dao->case_id) {
        if (empty($row['date_submission']) && !empty($dao->date_subission)) {
          $rows[$rowNum]['date_submission'] = $dao->date_submission;
        }
        if (empty($row['ass_date']) && !empty($dao->ass_date)) {
          $rows[$rowNum]['ass_date'] = $dao->ass_date;
        }
        if (empty($row['incc_date']) && !empty($dao->incc_date)) {
          $rows[$rowNum]['incc_date'] = $dao->incc_date;
        }
        if (empty($row['insc_date']) && !empty($dao->insc_date)) {
          $rows[$rowNum]['insc_date'] = $dao->insc_date;
        }
        if (empty($row['inanamon_date']) && !empty($dao->inanamon_date)) {
          $rows[$rowNum]['inanamon_date'] = $dao->inanamon_date;
        }
        if (empty($row['approve_rep']) && !empty($dao->approve_rep)) {
          $rows[$rowNum]['approve_rep'] = $dao->approve_rep;
        }
        if (empty($row['approve_cc']) && !empty($dao->approve_cc)) {
          $rows[$rowNum]['approve_cc'] = $dao->approve_cc;
        }
        if (empty($row['approve_sc']) && !empty($dao->approve_sc)) {
          $rows[$rowNum]['approve_sc'] = $dao->approve_sc;
        }
        if (empty($row['approve_anamon']) && !empty($dao->approve_anamon)) {
          $rows[$rowNum]['approve_anamon'] = $dao->approve_anamon;
        }
      }
    }
  }
}
