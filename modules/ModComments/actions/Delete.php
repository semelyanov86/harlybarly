<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class ModComments_Delete_Action extends Vtiger_Delete_Action {

	function checkPermission(Vtiger_Request $request) {
		//throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        parent::checkPermission($request);
	}

    public function process(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $recordId = $request->get('crmid');

        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
        $moduleModel = $recordModel->getModule();
        $relatedModel = Vtiger_Record_Model::getInstanceById($recordModel->get('related_to'));
        if(vtlib_isModuleActive('ModTracker')) {
            //Track the time the relation was deleted
            require_once 'modules/ModTracker/ModTracker.php';
            ModTracker::unLinkRelation($relatedModel->getModuleName(), $recordModel->get('related_to'), 'ModComments', $recordId);
        }
        $recordModel->delete();

        $response = new Vtiger_Response();
        $response->setResult(array('success' => true));
        return $response;

    }
}
