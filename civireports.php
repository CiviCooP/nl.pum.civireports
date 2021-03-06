<?php

require_once 'civireports.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function civireports_civicrm_config(&$config) {
  _civireports_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function civireports_civicrm_xmlMenu(&$files) {
  _civireports_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function civireports_civicrm_install() {
  return _civireports_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function civireports_civicrm_uninstall() {
  return _civireports_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function civireports_civicrm_enable() {
  return _civireports_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function civireports_civicrm_disable() {
  return _civireports_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function civireports_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _civireports_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function civireports_civicrm_managed(&$entities) {
  return _civireports_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function civireports_civicrm_caseTypes(&$caseTypes) {
  _civireports_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function civireports_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _civireports_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
/**
 * Implementation of hook civicrm_alterContent
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 18 Aug 2014
 */
function civireports_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  /*
   * remove collapsed for custom field sets for report FindExpert
   */
  if ($tplName === 'CRM/Civireports/Form/Report/FindExpert.tpl') {
    $content = str_replace('div class="crm-accordion-wrapper crm-accordion collapsed','div class="crm-accordion-wrapper crm-accordion',$content);
  }
}

/**
 * Implementation of hook civicrm_export
 * Specific for issue 3023 - always add name/id of representative and name/id of sponsor if case export
 *
 * @param $exportTempTable
 * @param $headerRows
 * @param $sqlColumns
 * @param $exportMode
 */
function civireports_civicrm_export( $exportTempTable, &$headerRows, &$sqlColumns, $exportMode ) {
  if ($exportMode == 6) {
    // only if case_id is included in the export
    if (isset($sqlColumns['case_id'])) {

      $addColumns = array();
      $addColumns[] = "ADD COLUMN representative_id INT(11)";
      $addColumns[] = "ADD COLUMN representative_name VARCHAR(128)";
      $addColumns[] = "ADD COLUMN fa_donor_id INT(11)";
      $addColumns[] = "ADD COLUMN fa_donor_name VARCHAR(128)";
      $sql = "ALTER TABLE " . $exportTempTable . " " . implode(", ", $addColumns);
      CRM_Core_DAO::singleValueQuery($sql);

      $headerRows[] = "Representative ID";
      $headerRows[] = "Representative Name";
      $headerRows[] = "Sponsor ID";
      $headerRows[] = "Sponsor Name";

      $sqlColumns['representative_id'] = 'representative_id INT(11)';
      $sqlColumns['representative_name'] = 'representative_name VARCHAR(128)';
      $sqlColumns['fa_donor_id'] = 'fa_donor_id INT(11)';
      $sqlColumns['fa_donor_name'] = 'fa_donor_name VARCHAR(128)';

      // update temp table
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM ".$exportTempTable);
      while ($dao->fetch()) {
        $count = 1;
        $updateFields = array();
        $updateParams = array();

        if (method_exists("CRM_Threepeas_BAO_PumCaseRelation", "getCaseRepresentative")) {
          $representativeId = CRM_Threepeas_BAO_PumCaseRelation::getCaseRepresentative($dao->case_id);
          if ($representativeId) {
            $updateFields[] = "representative_id = %".$count;
            $updateParams[$count] = array($representativeId, "Integer");
            $count++;
            $updateFields[] = "representative_name = %".$count;
            $updateParams[$count] = array(CRM_Threepeas_Utils::getContactName($representativeId), "String");
            $count++;
          }
        }
        if (method_exists("CRM_Threepeas_BAO_PumDonorLink", "getCaseFADonor")) {
          $faDonorId = CRM_Threepeas_BAO_PumDonorLink::getCaseFADonor($dao->case_id);
          if ($faDonorId) {
            $updateFields[] = "fa_donor_id = %".$count;
            $updateParams[$count] = array($faDonorId, "Integer");
            $count++;
            $updateFields[] = "fa_donor_name = %".$count;
            $updateParams[$count] = array(CRM_Threepeas_Utils::getContactName($faDonorId), "String");
            $count++;
          }
        }
        if (!empty($updateFields)) {
          $update = "UPDATE ".$exportTempTable." SET ".implode(", ", $updateFields)." WHERE case_id = %".$count;
          $updateParams[$count] = array($dao->case_id, "Integer");
          CRM_Core_DAO::executeQuery($update, $updateParams);
        }
      }
    }
  }
}