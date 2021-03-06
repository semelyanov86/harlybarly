<?php

class EMAILMaker_Module_Model extends EMAILMaker_EMAILMaker_Model {
    private $profilesActions = array("EDIT" => "EditView", "DETAIL" => "DetailView", "DELETE" => "Delete",);
    private $profilesPermissions;
    public $licensePermissions = array();

    public function getLicensePermissions($type = 'List') {
        if (empty($this->name)) {
            $this->name = explode('_', get_class($this)) [0];
        }
        $installer = 'ITS4YouInstaller';
        $licenseMode = 'Settings_ITS4YouInstaller_License_Model';
        if (vtlib_isModuleActive($installer)) {
            if (class_exists($licenseMode)) {
                $permission = new $licenseMode();
                $result = $permission->permission($this->name, $type);
                $this->licensePermissions['info'] = $result['errors'];
                return $result['success'];
            } else {
                $this->licensePermissions['errors'] = 'LBL_INSTALLER_UPDATE';
            }
        } else {
            $this->licensePermissions['errors'] = 'LBL_INSTALLER_NOT_ACTIVE';
        }
        return $result['success'];
    }

    public function getAlphabetSearchField() {
        return 'templatename';
    }
    public function getSelectThemeUrl() {
        $url = $this->getCreateRecordUrl();
        return $url . '&mode=selectTheme';
    }
    public function getCreateRecordUrl() {
        return 'index.php?module=' . $this->get('name') . '&view=' . $this->getEditViewName();
    }
    public function getCreateThemeRecordUrl() {
        $url = $this->getCreateRecordUrl();
        return $url . '&theme=new&mode=EditTheme';
    }
    public function saveRecord(EMAILMaker_Record_Model $recordModel) {
        $db = PearDatabase::getInstance();
        $templateid = $recordModel->getId();
        if (empty($templateid)) {
            $templateid = $db->getUniqueID('vtiger_emakertemplates');
            $sql = "INSERT INTO vtiger_emakertemplates(templatename, subject, description, body, deleted, templateid) VALUES (?,?,?,?,?,?)";
        } else {
            $sql = "UPDATE vtiger_emakertemplates SET templatename=?, subject=?, description=?, body=?, deleted=? WHERE templateid = ?";
        }
        $params = array(decode_html($recordModel->get('templatename')), decode_html($recordModel->get('subject')), decode_html($recordModel->get('description')), $recordModel->get('body'), 0, $templateid);
        $db->pquery($sql, $params);
        return $recordModel->setId($templateid);
    }
    public function deleteRecord(EMAILMaker_Record_Model $recordModel) {
        $recordId = $recordModel->getId();
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_emakertemplates WHERE templateid = ? ', array($recordId));
    }
    public function deleteAllRecords() {
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_emakertemplates', array());
    }
    public function getAllModuleEmailTemplateFields() {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $allModuleList = $this->getAllModuleList();
        $allRelFields = array();
        foreach ($allModuleList as $index => $module) {
            if ($module == 'Users') {
                $fieldList = $this->getRelatedModuleFieldList($module, $currentUserModel);
            } else {
                $fieldList = $this->getRelatedFields($module, $currentUserModel);
            }
            foreach ($fieldList as $key => $field) {
                $option = array(vtranslate($field['module'], $field['module']) . ':' . vtranslate($field['fieldlabel'], $field['module']), "$" . strtolower($field['module']) . "-" . $field['columnname'] . "$");
                $allFields[] = $option;
                if (!empty($field['referencelist'])) {
                    foreach ($field['referencelist'] as $key => $relField) {
                        $relOption = array(vtranslate($field['fieldlabel'], $field['module']) . ':' . '(' . vtranslate($relField['module'], $relField['module']) . ')' . vtranslate($relField['fieldlabel'], $relField['module']), "$" . strtolower($field['module']) . "-" . $field['columnname'] . ":" . $relField['columnname'] . "$");
                        $allRelFields[] = $relOption;
                    }
                }
            }
            if (is_array($allFields) && is_array($allRelFields)) {
                $allFields = array_merge($allFields, $allRelFields);
                $allRelFields = "";
            }
            $allOptions[vtranslate($module, $module) ] = $allFields;
            $allFields = "";
        }
        $option = array('Current Date', '$custom-currentdate$');
        $allFields[] = $option;
        $option = array('Current Time', '$custom-currenttime$');
        $allFields[] = $option;
        $allOptions['generalFields'] = $allFields;
        return $allOptions;
    }
    function getRelatedFields($module, $currentUserModel) {
        $handler = vtws_getModuleHandlerFromName($module, $currentUserModel);
        $meta = $handler->getMeta();
        $moduleFields = $meta->getModuleFields();
        $returnData = array();
        foreach ($moduleFields as $key => $field) {
            $referencelist = array();
            $relatedField = $field->getReferenceList();
            if ($field->getFieldName() == 'assigned_user_id') {
                $relModule = 'Users';
                $referencelist = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
            }
            if (!empty($relatedField)) {
                foreach ($relatedField as $ind => $relModule) {
                    $referencelist = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
                }
            }
            $returnData[] = array('module' => $module, 'fieldname' => $field->getFieldName(), 'columnname' => $field->getColumnName(), 'fieldlabel' => $field->getFieldLabelKey(), 'referencelist' => $referencelist);
        }
        return $returnData;
    }
    function getRelatedModuleFieldList($relModule, $user) {
        $handler = vtws_getModuleHandlerFromName($relModule, $user);
        $relMeta = $handler->getMeta();
        if (!$relMeta->isModuleEntity()) {
            return null;
        }
        $relModuleFields = $relMeta->getModuleFields();
        $relModuleFieldList = array();
        foreach ($relModuleFields as $relind => $relModuleField) {
            if ($relModule == 'Users') {
                if ($relModuleField->getFieldDataType() == 'string' || $relModuleField->getFieldDataType() == 'email' || $relModuleField->getFieldDataType() == 'phone') {
                    $skipFields = array(98, 115, 116, 31, 32);
                    if (!in_array($relModuleField->getUIType(), $skipFields) && $relModuleField->getFieldName() != 'asterisk_extension') {
                        $relModuleFieldList[] = array('module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey());
                    }
                }
            } else {
                $relModuleFieldList[] = array('module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey());
            }
        }
        return $relModuleFieldList;
    }
    public function getAllModuleList() {
        $db = PearDatabase::getInstance();
        $query = 'SELECT DISTINCT(name) AS modulename FROM vtiger_tab 
                              LEFT JOIN vtiger_field ON vtiger_field.tabid = vtiger_tab.tabid
                              WHERE vtiger_field.uitype = ?';
        $result = $db->pquery($query, array(13));
        $num_rows = $db->num_rows($result);
        $moduleList = array();
        for ($i = 0;$i < $num_rows;$i++) {
            $moduleList[] = $db->query_result($result, $i, 'modulename');
        }
        return $moduleList;
    }
    public function getListViewLinks($linkParams) {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $linkTypes = array('LISTVIEWMASSACTION', 'LISTVIEWSETTING');
        $links = Vtiger_Link_Model::getAllByType($this->getId(), $linkTypes, $linkParams);
        if ($this->CheckPermissions("DELETE")) {
            $massActionLink = array('linktype' => 'LISTVIEWMASSACTION', 'linklabel' => 'LBL_DELETE', 'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module=PDFMaker&action=MassDelete")', 'linkicon' => '');
            $links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
        }
        $quickLinks = array();
        if ($this->CheckPermissions("EDIT")) {
            $quickLinks[] = array('linktype' => 'LISTVIEW', 'linklabel' => 'LBL_IMPORT', 'linkurl' => 'javascript:Vtiger_Import_Js.triggerImportAction("index.php?module=EMAILMaker&view=Import")', 'linkicon' => '');
        }
        if ($this->CheckPermissions("EDIT")) {
            $quickLinks[] = array('linktype' => 'LISTVIEW', 'linklabel' => 'LBL_EXPORT', 'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("index.php?module=EMAILMaker&view=Export")', 'linkicon' => '');
        }
        foreach ($quickLinks as $quickLink) {
            $links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
        }
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks = $this->getSettingLinks();
            foreach ($settingsLinks as $settingsLink) {
                $links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
            }
            $SettingsLinks = $this->GetAvailableSettings();
            foreach ($SettingsLinks as $stype => $sdata) {
                $s_parr = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => $sdata["label"], 'linkurl' => $sdata["location"], 'linkicon' => '');
                $links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($s_parr);
            }
        }
        return $links;
    }
    public function isQuickSearchEnabled() {
        return false;
    }
    public function getPopupUrl() {
        return 'module=PDFMaker&view=Popup';
    }
    function getUtilityActionsNames() {
        return array();
    }
    public function GetProfilesPermissions() {
        if (count($this->profilesPermissions) == 0) {
            $adb = PearDatabase::getInstance();
            $profiles = Settings_Profiles_Record_Model::getAll();
            $res = $adb->pquery("SELECT * FROM vtiger_emakertemplates_profilespermissions", array());
            $permissions = array();
            while ($row = $adb->fetchByAssoc($res)) {
                if (isset($profiles[$row["profileid"]])) $permissions[$row["profileid"]][$row["operation"]] = $row["permissions"];
            }
            foreach ($profiles as $profileid => $profilename) {
                foreach ($this->profilesActions as $actionName) {
                    $actionId = getActionid($actionName);
                    if (!isset($permissions[$profileid][$actionId])) {
                        $permissions[$profileid][$actionId] = "0";
                    }
                }
            }
            ksort($permissions);
            $this->profilesPermissions = $permissions;
        }
        return $this->profilesPermissions;
    }
    public function CheckPermissions($actionKey) {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $profileid = getUserProfile($current_user->id);
        $result = false;
        if (isset($this->profilesActions[$actionKey])) {
            $actionid = getActionid($this->profilesActions[$actionKey]);
            $permissions = $this->GetProfilesPermissions();
            if (isset($permissions[$profileid[0]][$actionid]) && $permissions[$profileid[0]][$actionid] == "0") $result = true;
        }
        return $result;
    }
    public function getModuleBasicLinks() {
        $moduleName = $this->getName();
        if ($this->CheckPermissions("EDIT")) {
            $basicLinks[] = array('linktype' => 'BASIC', 'linklabel' => 'LBL_ADD_TEMPLATE', 'linkurl' => $this->getSelectThemeUrl(), 'linkicon' => 'fa-plus');
            $basicLinks[] = array('linktype' => 'BASIC', 'linklabel' => 'LBL_ADD_THEME', 'linkurl' => $this->getCreateThemeRecordUrl(), 'linkicon' => 'fa-plus');
        }
        return $basicLinks;
    }
    public function getSettingLinks() {
        $settingsLinks = array();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_EXTENSIONS', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=Extensions', 'linkicon' => '');
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_PROFILES', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=ProfilesPrivilegies', 'linkicon' => '');
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_CUSTOM_LABELS', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=CustomLabels', 'linkicon' => '');
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_PRODUCTBLOCKTPL', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=ProductBlocks', 'linkicon' => '');
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_LICENSE', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=License&parent=Settings', 'linkicon' => Vtiger_Theme::getImagePath('proxy.gif'));
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_UPGRADE', $this->getName()), 'linkurl' => 'index.php?module=ModuleManager&parent=Settings&view=ModuleImport&mode=importUserModuleStep1', 'linkicon' => '');
            $settingsLinks[] = array('linktype' => 'LISTVIEWSETTING', 'linklabel' => vtranslate('LBL_UNINSTALL', $this->getName()), 'linkurl' => 'index.php?module=' . $this->getName() . '&view=Uninstall&parent=Settings', 'linkicon' => '');
        }
        return $settingsLinks;
    }
    public function getNameFields() {
        $nameFieldObject = Vtiger_Cache::get('EntityField', $this->getName());
        $moduleName = $this->getName();
        if ($nameFieldObject && $nameFieldObject->fieldname) {
            $this->nameFields = explode(',', $nameFieldObject->fieldname);
        } else {
            $fieldNames = 'filename';
            $this->nameFields = array($fieldNames);
            $entiyObj = new stdClass();
            $entiyObj->basetable = "vtiger_emakertemplates";
            $entiyObj->basetableid = "templateid";
            $entiyObj->fieldname = $fieldNames;
            Vtiger_Cache::set('EntityField', $this->getName(), $entiyObj);
        }
        return $this->nameFields;
    }
    function isStarredEnabled() {
        return false;
    }
    function isFilterColumnEnabled() {
        return false;
    }
    public function getRecordIds($skipRecords) {
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT templateid FROM vtiger_emakertemplates WHERE templateid NOT IN (' . generateQuestionMarks($skipRecords) . ')', $skipRecords);
        $num_rows = $adb->num_rows($result);
        $recordIds = array();
        if ($num_rows > 0) {
            while ($row = $adb->fetchByAssoc($result)) {
                $recordIds[] = $row['templateid'];
            }
        }
        return $recordIds;
    }
    public function getEmailRelatedModules() {
        $userPrivModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $relatedModules = vtws_listtypes(array('email'), Users_Record_Model::getCurrentUserModel());
        $relatedModules = $relatedModules['types'];
        foreach ($relatedModules as $key => $moduleName) {
            if ($moduleName === 'Users') {
                unset($relatedModules[$key]);
            }
        }
        foreach ($relatedModules as $moduleName) {
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            if ($userPrivModel->isAdminUser() || $userPrivModel->hasGlobalReadPermission() || $userPrivModel->hasModulePermission($moduleModel->getId())) {
                $emailRelatedModules[] = $moduleName;
            }
        }
        $emailRelatedModules[] = 'Users';
        return $emailRelatedModules;
    }
    public function searchEmails($searchValue, $moduleName = false) {
        global $current_user;
        $emailsResult = array();
        $db = PearDatabase::getInstance();
        $EmailsModuleModel = Vtiger_Module_Model::getInstance('Emails');
        $emailSupportedModulesList = $EmailsModuleModel->getEmailRelatedModules();
        foreach ($emailSupportedModulesList as $module) {
            if ($module != 'Users' && $module != 'ModComments') {
                $activeModules[] = "'" . $module . "'";
                $activeModuleModel = Vtiger_Module_Model::getInstance($module);
                $moduleEmailFields = $activeModuleModel->getFieldsByType('email');
                foreach ($moduleEmailFields as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewable()) {
                        $fieldIds[] = $fieldModel->get('id');
                    }
                }
            }
        }
        if ($moduleName) {
            $activeModules = array("'" . $moduleName . "'");
        }
        $query = "SELECT vtiger_emailslookup.crmid, vtiger_emailslookup.setype, vtiger_emailslookup.value, 
                          vtiger_crmentity.label FROM vtiger_emailslookup INNER JOIN vtiger_crmentity on 
                          vtiger_crmentity.crmid = vtiger_emailslookup.crmid AND vtiger_crmentity.deleted=0 WHERE 
						  vtiger_emailslookup.fieldid in (" . implode(',', $fieldIds) . ") and 
						  vtiger_emailslookup.setype in (" . implode(',', $activeModules) . ") 
                          and (vtiger_emailslookup.value LIKE ? OR vtiger_crmentity.label LIKE ?)";
        $emailOptOutIds = $EmailsModuleModel->getEmailOptOutRecordIds();
        if (!empty($emailOptOutIds)) {
            $query.= " AND vtiger_emailslookup.crmid NOT IN (" . implode(',', $emailOptOutIds) . ")";
        }
        $result = $db->pquery($query, array('%' . $searchValue . '%', '%' . $searchValue . '%'));
        $isAdmin = is_admin($current_user);
        while ($row = $db->fetchByAssoc($result)) {
            if (!$isAdmin) {
                $recordPermission = Users_Privileges_Model::isPermitted($row['setype'], 'DetailView', $row['crmid']);
                if (!$recordPermission) {
                    continue;
                }
            }
            $emailsResult[vtranslate($row['setype'], $row['setype']) ][$row['crmid']][] = array('value' => $row['value'], 'label' => decode_html($row['label']) . ' <b>(' . $row['value'] . ')</b>', 'name' => decode_html($row['label']), 'module' => $row['setype']);
        }
        $additionalModule = array('Users');
        if (!$moduleName || in_array($moduleName, $additionalModule)) {
            foreach ($additionalModule as $moduleName) {
                $moduleInstance = CRMEntity::getInstance($moduleName);
                $searchFields = array();
                $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                $emailFieldModels = $moduleModel->getFieldsByType('email');
                foreach ($emailFieldModels as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewable()) {
                        $searchFields[] = $fieldName;
                    }
                }
                $emailFields = $searchFields;
                $nameFields = $moduleModel->getNameFields();
                foreach ($nameFields as $fieldName) {
                    $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
                    if ($fieldModel->isViewable()) {
                        $searchFields[] = $fieldName;
                    }
                }
                if ($emailFields) {
                    $userQuery = 'SELECT ' . $moduleInstance->table_index . ', ' . implode(',', $searchFields) . ' FROM vtiger_users WHERE deleted=0';
                    $result = $db->pquery($userQuery, array());
                    $numOfRows = $db->num_rows($result);
                    for ($i = 0;$i < $numOfRows;$i++) {
                        $row = $db->query_result_rowdata($result, $i);
                        foreach ($emailFields as $emailField) {
                            $emailFieldValue = $row[$emailField];
                            if ($emailFieldValue) {
                                $recordLabel = getEntityFieldNameDisplay($moduleName, $nameFields, $row);
                                if (strpos($emailFieldValue, $searchValue) !== false || strpos($recordLabel, $searchValue) !== false) {
                                    $emailsResult[vtranslate($moduleName, $moduleName) ][$row[$moduleInstance->table_index]][] = array('value' => $emailFieldValue, 'name' => $recordLabel, 'label' => $recordLabel . ' <b>(' . $emailFieldValue . ')</b>', 'module' => $moduleName);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $emailsResult;
    }
    public function getMERecipientsListSql() {
        $query = "SELECT vtiger_crmentity.crmid, vtiger_emakertemplates_emails.*, vtiger_activity.*, vtiger_emaildetails.email_flag, vtiger_activity.time_start FROM vtiger_emakertemplates_emails
                            INNER JOIN vtiger_emakertemplates_me
                                ON vtiger_emakertemplates_me.esentid = vtiger_emakertemplates_emails.esentid
                            LEFT JOIN vtiger_activity
                                ON vtiger_activity.activityid = vtiger_emakertemplates_emails.parent_id
                            LEFT JOIN vtiger_crmentity
                                ON vtiger_crmentity.crmid = vtiger_activity.activityid
                            LEFT JOIN vtiger_emaildetails
                                ON vtiger_emaildetails.emailid = vtiger_activity.activityid";
        return $query;
    }
    public function getMERecipientsListCount($recordId = false) {
        $params = array();
        $db = PearDatabase::getInstance();
        $query = $this->getMERecipientsListSql();
        if ($recordId) {
            $query.= " WHERE vtiger_emakertemplates_me.meid=?";
            $params = array($recordId);
        }
        $result = $db->pquery($query, $params);
        return $db->num_rows($result);
    }
    public function getMERecipientsList($mode, $pagingModel, $user, $recordId = false) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();
        if (!$user) {
            $user = $currentUser->getId();
        }
        $nowInUserFormat = Vtiger_Datetime_UIType::getDisplayDateValue(date('Y-m-d H:i:s'));
        $nowInDBFormat = Vtiger_Datetime_UIType::getDBDateTimeValue($nowInUserFormat);
        list($currentDate, $currentTime) = explode(' ', $nowInDBFormat);
        $query = $this->getMERecipientsListSql();
        $query.= " WHERE vtiger_emakertemplates_me.meid=? LIMIT " . $pagingModel->getStartIndex() . ", " . ($pagingModel->getPageLimit() + 1);
        $params = array($recordId);
        $result = $db->pquery($query, $params);
        $numOfRows = $db->num_rows($result);
        $groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
        $recipients = array();
        for ($i = 0;$i < $numOfRows;$i++) {
            $newRow = $db->query_result_rowdata($result, $i);
            $model = Vtiger_Record_Model::getCleanInstance('Emails');
            $ownerId = $newRow['smownerid'];
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $model->setData($newRow);
            $model->setId($newRow['crmid']);
            $pid = $newRow['pid'];
            $pmodule = getSalesEntityType($pid);
            $pmoduleModel = Vtiger_Module_Model::getInstance($pmodule);
            $model->set("pmodule", $pmodule);
            $model->set("pmodulemodel", $pmoduleModel);
            $model->set("status", $newRow['email_flag']);
            $recipients[] = $model;
        }
        $pagingModel->calculatePageRange($recipients);
        if ($numOfRows > $pagingModel->getPageLimit()) {
            array_pop($recipients);
            $pagingModel->set('nextPageExists', true);
        } else {
            $pagingModel->set('nextPageExists', false);
        }
        return $recipients;
    }
    public function GetListviewResult($orderby = "templateid", $dir = "ASC", $request, $all_data = true) {
        $adb = PearDatabase::getInstance();
        $R_Atr = array('0');
        $sql = "SELECT vtiger_emakertemplates_displayed.*, vtiger_emakertemplates.* FROM vtiger_emakertemplates 
                LEFT JOIN vtiger_emakertemplates_displayed USING(templateid)";
        $Search = array();
        $Search_Types = array("module", "description", "sharingtype", "owner");
        $sql.= " WHERE vtiger_emakertemplates.deleted = ? ";
        if ($request) {
            if ($request->has('search_params') && !$request->isEmpty('search_params')) {
                $listSearchParams = $request->get('search_params');
                foreach ($listSearchParams as $groupInfo) {
                    if (empty($groupInfo)) {
                        continue;
                    }
                    foreach ($groupInfo as $fieldSearchInfo) {
                        $st = $fieldSearchInfo[0];
                        $operator = $fieldSearchInfo[1];
                        $search_val = $fieldSearchInfo[2];
                        if (in_array($st, $Search_Types)) {
                            if ($st == "description") {
                                $search_val = "%" . $search_val . "%";
                                $Search[] = "vtiger_pdfmaker." . $st . " LIKE ?";
                            } else {
                                $Search[] = "vtiger_pdfmaker." . $st . " = ?";
                            }
                            $R_Atr[] = $search_val;
                        }
                        if ($st == "status") {
                            $search_status = $search_val;
                        }
                    }
                }
            }
            if (count($Search) > 0) {
                $sql.= " AND ";
                $sql.= implode(" AND ", $Search);
            }
        }
        if (!empty($orderby)) {
            $sql.= " ORDER BY ";
            if ($orderby == "owner" || $orderby == "sharingtype") {
                $sql.= "vtiger_pdfmaker_settings";
            } else {
                $sql.= "vtiger_pdfmaker";
            }
            $sql.= "." . $orderby . " " . $dir;
        }
        $result = $adb->pquery($sql, $R_Atr);
        return $result;
    }
    public function returnTemplatePermissionsData($selected_module = "", $templateid = "") {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $result = true;
        if (!is_admin($current_user)) {
            if ($selected_module != "" && isPermitted($selected_module, '') != "yes") {
                $result = false;
            } elseif ($templateid != "" && $this->CheckSharing($templateid) === false) {
                $result = false;
            }
            $detail_result = $result;
            if (!$this->CheckPermissions("EDIT")) {
                $edit_result = false;
            } else {
                $edit_result = $result;
            }
            if (!$this->CheckPermissions("DELETE")) {
                $delete_result = false;
            } else {
                $delete_result = $result;
            }
            if ($detail_result === false || $edit_result === false || $delete_result === false) {
                $profileGlobalPermission = array();
                require ('user_privileges/user_privileges_' . $current_user->id . '.php');
                require ('user_privileges/sharing_privileges_' . $current_user->id . '.php');
                if ($profileGlobalPermission[1] == 0) {
                    $detail_result = true;
                }
                if ($profileGlobalPermission[2] == 0) {
                    $edit_result = $delete_result = true;
                }
            }
        } else {
            $detail_result = $edit_result = $delete_result = $result;
        }
        return array("detail" => $detail_result, "edit" => $edit_result, "delete" => $delete_result);
    }
    public function CheckSharing($templateid) {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $result = $this->db->pquery("SELECT owner, sharingtype FROM vtiger_emakertemplates WHERE templateid = ?", array($templateid));
        $row = $this->db->fetchByAssoc($result);
        $owner = $row["owner"];
        $sharingtype = $row["sharingtype"];
        $result = false;
        if ($owner == $current_user->id) {
            $result = true;
        } else {
            switch ($sharingtype) {
                case "public":
                    $result = true;
                break;
                case "private":
                    $subordinateUsers = $this->getSubRoleUserIds($current_user->roleid);
                    if (!empty($subordinateUsers) && count($subordinateUsers) > 0) {
                        $result = in_array($owner, $subordinateUsers);
                    } else $result = false;
                    break;
                case "share":
                    $subordinateUsers = $this->getSubRoleUserIds($current_user->roleid);
                    if (!empty($subordinateUsers) && count($subordinateUsers) > 0 && in_array($owner, $subordinateUsers)) $result = true;
                    else {
                        $member_array = $this->GetSharingMemberArray($templateid);
                        if (isset($member_array["users"]) && in_array($current_user->id, $member_array["users"])) $result = true;
                        elseif (isset($member_array["roles"]) && in_array($current_user->roleid, $member_array["roles"])) $result = true;
                        else {
                            if (isset($member_array["rs"])) {
                                foreach ($member_array["rs"] as $roleid) {
                                    $roleAndsubordinateRoles = getRoleAndSubordinatesRoleIds($roleid);
                                    if (in_array($current_user->roleid, $roleAndsubordinateRoles)) {
                                        $result = true;
                                        break;
                                    }
                                }
                            }
                            if ($result == false && isset($member_array["groups"])) {
                                $current_user_groups = explode(",", fetchUserGroupids($current_user->id));
                                $res_array = array_intersect($member_array["groups"], $current_user_groups);
                                if (!empty($res_array) && count($res_array) > 0) $result = true;
                                else $result = false;
                            }
                        }
                    }
                    break;
                }
            }
            return $result;
        }
        private function getSubRoleUserIds($roleid) {
            $subRoleUserIds = array();
            $subordinateUsers = getRoleAndSubordinateUsers($roleid);
            if (!empty($subordinateUsers) && count($subordinateUsers) > 0) {
                $currRoleUserIds = getRoleUserIds($roleid);
                $subRoleUserIds = array_diff($subordinateUsers, $currRoleUserIds);
            }
            return $subRoleUserIds;
        }
    } 

    ?>