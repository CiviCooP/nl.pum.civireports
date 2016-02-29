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
        if (isset($this->_params['fields'][$fieldName])) {
          $selectClauses[] = $fieldValues['dbAlias'] . " AS " . $fieldName;
        }
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
      JOIN civicrm_activity piopen ON pica.activity_id = piopen.id AND piopen.activity_type_id = {$this->openCaseActivityTypeId}
        AND piopen.is_current_revision = 1
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
    if (isset($this->_params['fields']['country_name'])) {
      $this->_columnHeaders['country_name'] = array('title' => ts("Country"), 'type' => CRM_Utils_Type::T_STRING);
    }
    if (isset($this->_params['fields']['case_subject'])) {
      $this->_columnHeaders['case_subject'] = array('title' => ts("Case Subject"), 'type' => CRM_Utils_Type::T_STRING);
    }
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
    $this->counsRelationshipTypeId = $config->getCounsellorRelationshipTypeId();
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
      $this->updateRowWithActivities($row);
      $rows[] = $row;
    }
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
   * @param array $row
   */
  private function updateRowWithActivities(&$row) {
    $validActivityTypes = array($this->approveAnamonActivityTypeId, $this->approveCcActivityTypeId,
      $this->approveScActivityTypeId, $this->assessRepActivityTypeId);
    $fetchedCaseActivities = civicrm_api3('CaseActivity', 'Get', array('case_id' => $row['case_id']));
    foreach ($fetchedCaseActivities['values'] as $caseActivityId => $caseActivity) {
      if (in_array($caseActivity['activity_type_id'], $validActivityTypes)) {
        switch ($caseActivity['activity_type_id']) {
          case $this->assessRepActivityTypeId:
            $row['ass_date'] = $caseActivity['activity_date_time'];
            $row['approve_rep'] = $this->getApproveRep($row['case_id']);
            break;
          case $this->approveAnamonActivityTypeId:
            $row['inanamon_date'] = $caseActivity['activity_date_time'];
            $row['approve_anamon'] = $caseActivity[$this->approveAnamonColumn];
            break;
          case $this->approveCcActivityTypeId:
            $row['incc_date'] = $caseActivity['activity_date_time'];
            $row['approve_cc'] = $caseActivity[$this->approveCcColumn];
            break;
          case $this->approveScActivityTypeId:
            $row['insc_date'] = $caseActivity['activity_date_time'];
            $row['approve_sc'] = $caseActivity[$this->approveScColumn];
            break;
        }
      }
    }
  }
  private function getApproveRep($caseId) {
    if (!empty($caseId)) {
      $qry = "SELECT " . $this->approveRepColumn . " FROM " . $this->approveRepTable . " WHERE entity_id = %1";
      $params = array(1 => array($caseId, 'Integer'));
      return CRM_Core_DAO::singleValueQuery($qry, $params);
    }
    return NULL;
  }
}
