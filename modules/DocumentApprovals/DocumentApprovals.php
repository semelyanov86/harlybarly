<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class DocumentApprovals extends Vtiger_CRMEntity {
	var $table_name = 'vtiger_document_approvals';
	var $table_index= 'document_approvalsid';
	var $related_tables = Array ('vtiger_document_approvalscf' => Array ( 'document_approvalsid', 'vtiger_document_approvals', 'document_approvalsid' ),);


	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_document_approvalscf', 'document_approvalsid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_document_approvals', 'vtiger_document_approvalscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_document_approvals' => 'document_approvalsid',
		'vtiger_document_approvalscf'=>'document_approvalsid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('document_approvals', 'name'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = 'name';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Name' => Array('document_approvals', 'name'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Name' => 'name',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('name');

	// For Alphabetical search
	var $def_basicsearch_col = 'name';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'name';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('name','assigned_user_id');

	var $default_order_by = 'name';
	var $default_sort_order='ASC';

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
        require 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';
        $emm = new VTEntityMethodManager($adb);
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
			$this->init($moduleName);
			$this->createHandle($moduleName);
			$this->createRelationship($moduleName);
			$this->addRelatedList($moduleName, 'Contacts', 'DocumentApprovals', array('add'));
            self::createScheduler();
		} else if($eventType == 'module.disabled') {
			$this->removeHandle($moduleName);
            self::deactiveScheduler();
		} else if($eventType == 'module.enabled') {
			$this->createHandle($moduleName);
            self::createScheduler();
//            $emm->addEntityMethod("DocumentApprovals", "Update Platform Status","modules/DocumentApprovals/workflow/updatePlatformStatus.php", "UpdatePlatformStatus");
		} else if($eventType == 'module.preuninstall') {
			$this->removeHandle($moduleName);
            self::deleteScheduler();
//			$emm->removeEntityMethod('DocumentApprovals', 'Update Platform Status');
		} else if($eventType == 'module.preupdate') {
			$this->createHandle($moduleName);
		} else if($eventType == 'module.postupdate') {
            self::deleteScheduler();
            self::createScheduler();
		}
 	}

    function get_emails($id, $cur_tab_id, $rel_tab_id, $actions=false) {
        global $currentModule;
        $related_module = vtlib_getModuleNameById($rel_tab_id);
        require_once("modules/$related_module/$related_module.php");
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);

        $returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;

        $button = '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

        $userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
        $query = "SELECT CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
                vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime,
                vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start,
                vtiger_seactivityrel.crmid as parent_id FROM vtiger_activity, vtiger_seactivityrel, vtiger_document_approvals, vtiger_users,
                vtiger_crmentity LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE 
                vtiger_seactivityrel.activityid = vtiger_activity.activityid AND 
                vtiger_document_approvals.document_approvalsid = vtiger_seactivityrel.crmid AND vtiger_users.id = vtiger_crmentity.smownerid
                AND vtiger_crmentity.crmid = vtiger_activity.activityid  AND vtiger_document_approvals.document_approvalsid = $id AND
                vtiger_activity.activitytype = 'Emails' AND vtiger_crmentity.deleted = 0";

        $return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);

        if($return_value == null) $return_value = Array();
        $return_value['CUSTOM_BUTTON'] = $button;

        return $return_value;
    }
	/**
	 * When install module
	 * @param $moduleName
	 */
	public function init($moduleName) {
		$module = Vtiger_Module::getInstance($moduleName);

		// Enable Activities
		$activityFieldTypeId = 34;
		$this->addModuleRelatedToForEvents($module->name, $activityFieldTypeId);

		// Enable ModTracker
		require_once 'modules/ModTracker/ModTracker.php';
		ModTracker::enableTrackingForModule($module->id);

		// Enable Comments
		$commentInstance = Vtiger_Module::getInstance('ModComments');
		$commentRelatedToFieldInstance = Vtiger_Field::getInstance('related_to', $commentInstance);
		$commentRelatedToFieldInstance->setRelatedModules(array($module->name));

		// Customize Record Numbering
		$prefix = 'NO';
		if (strlen($module->name) >= 2) {
			$prefix = substr($module->name, 0, 2);
			$prefix = strtoupper($prefix);
		}
		$this->customizeRecordNumbering($module->name, $prefix, 1);

	}

	/**
	 * @param string $moduleName
	 * @param int $fieldTypeId
	 */
	public function addModuleRelatedToForEvents($moduleName, $fieldTypeId)
	{
		global $adb;

		$sqlCheckProject = "SELECT * FROM `vtiger_ws_referencetype` WHERE fieldtypeid = ? AND type = ?";
		$rsCheckProject = $adb->pquery($sqlCheckProject, array($fieldTypeId, $moduleName));
		if ($adb->num_rows($rsCheckProject) < 1) {
			$adb->pquery("INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)",
				array($fieldTypeId, $moduleName));
		}
	}

	/**
	 * @param string $sourceModule
	 * @param string $prefix
	 * @param int $sequenceNumber
	 * @return array
	 */
	public function customizeRecordNumbering($sourceModule, $prefix = 'NO', $sequenceNumber = 1)
	{
		$moduleModel = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($sourceModule);
		$moduleModel->set('prefix', $prefix);
		$moduleModel->set('sequenceNumber', $sequenceNumber);

		$result = $moduleModel->setModuleSequence();

		return $result;
	}

	private function createHandle($moduleName)
	{
		include_once 'include/events/VTEventsManager.inc';
		global $adb;
		$em = new VTEventsManager($adb);
		$em->setModuleForHandler($moduleName, "{$moduleName}Handler.php");
		$em->registerHandler("vtiger.entity.aftersave", "modules/{$moduleName}/{$moduleName}Handler.php", "{$moduleName}Handler");
	}

	/**
	 * @param string $moduleName
	 */
	private function removeHandle($moduleName)
	{
		include_once 'include/events/VTEventsManager.inc';
		global $adb;
		$em = new VTEventsManager($adb);
		$em->unregisterHandler("{$moduleName}Handler");
	}

    /**
     * Save the related module record information. Triggered from CRMEntity->saveentity method or updateRelations.php
     * @param String This module name
     * @param Integer This module record number
     * @param String Related module name
     * @param mixed Integer or Array of related module record number
     */
    function save_related_module($module, $crmid, $with_module, $with_crmids) {
        $adb = PearDatabase::getInstance();
        if(!is_array($with_crmids)) $with_crmids = Array($with_crmids);
        foreach($with_crmids as $with_crmid) {
            if($with_module == 'Calendar') {
                $checkpresence = $adb->pquery("SELECT crmid FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ?", Array($crmid, $with_crmids));
                // Relation already exists? No need to add again
                if ($checkpresence && $adb->num_rows($checkpresence))
                    continue;
                $adb->pquery("INSERT INTO vtiger_seactivityrel(crmid, activityid) VALUES(?,?)", array($crmid, $with_crmids));
            }else {
                parent::save_related_module($module, $crmid, $with_module, $with_crmid);
            }
        }
    }

    public function createRelationship($module1)
    {
    	include_once "vtlib/Vtiger/Module.php";        
        $module2 = 'Contacts';
        $block = 'LBL_CUSTOM_INFORMATION';
        $fieldLabel = 'Contact';
        $module = Vtiger_Module::getInstance($module1);
        $module1Class = Vtiger_Module::getClassInstance($module1);
        $block1 = Vtiger_Block::getInstance($block, $module);
        $columnName = "";
        if ($actionRelatedList == true) {
            $columnName = "cf_nrl_" . strtolower($module2) . rand(1, 1000) . "_id";
        } else {
            $columnName = "cf_" . strtolower($module2) . "_id";
        }
        $fieldName = $columnName;
        $field1 = new Vtiger_Field();
        $field1->label = $fieldLabel;
        $field1->name = $fieldName;
        $field1->table = $module1Class->table_name;
        $field1->column = $fieldName;
        $field1->generatedtype = 2;
        $field1->columntype = "INT(10)";
        $field1->uitype = 10;
        $field1->typeofdata = "I~O";
        $field1->quickcreate = 2;
        $field1->sequence = 7;
        $block1->addField($field1);
        $field1->setRelatedModules(array($module2));
        $block1->save($module);
        return $field1->id;
    }

    /**
     * @param $module1
     * @param $module2
     * @param $relListLabel
     */
    public function addRelatedList($module1, $module2, $relListLabel, $actions)
    {
        include_once "vtlib/Vtiger/Module.php";
        $module = Vtiger_Module::getInstance($module2);
        $module->setRelatedList(Vtiger_Module::getInstance($module1), $relListLabel, $actions, "get_dependents_list");
    }

    private function createScheduler()
    {
        $adb = PearDatabase::getInstance();
        $sql = "SELECT id FROM `vtiger_cron_task` WHERE `module` = 'DocumentApprovals'";
        $res = $adb->pquery($sql, array());
        if (!$adb->num_rows($res)) {
            $adb->pquery("INSERT INTO `vtiger_cron_task` (`name`, `handler_file`, `frequency`, `status`, `module`, `sequence`) VALUES ('Document Approvals', 'modules/DocumentApprovals/cron/DocumentApprovals.service', '500', '0', 'DocumentApprovals', '37')", array());
        }
    }
    private function deactiveScheduler()
    {
        $adb = PearDatabase::getInstance();
        $adb->pquery("UPDATE `vtiger_cron_task` SET `status`='1' WHERE (`module`='DocumentApprovals')", array());
    }
    private function deleteScheduler()
    {
        $adb = PearDatabase::getInstance();
        $adb->pquery("DELETE FROM `vtiger_cron_task` WHERE (`module`='DocumentApprovals')", array());
    }
}