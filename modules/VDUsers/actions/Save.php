<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class VDUsers_Save_Action extends Vtiger_Action_Controller
{
	public function process(Vtiger_Request $request)
	{
		$this->saveRoles($request);
//		$customView = new CustomView_Save_Action();
//		$customView->process($request);
		$this->cvprocess($request);
	}
	
	private function saveRoles(Vtiger_Request $request)
	{
		global $adb;
		$roles = $request->get('roles');
		$adb->pquery('DELETE FROM vtiger_vdusers_roles', array());
		//$adb->pquery('DELETE FROM vtiger_customview WHERE viewname = ?', array('VDUsers'));
		if ($roles) {
			foreach($roles as $role) {
				$rolelist = $request->get($role);
				if(empty($rolelist)){
                    $data = array(
                        'roleid' => $role,
                        'roles' =>  '');

                }
                else {
                    $data = array(
                        'roleid' => $role,
                        'roles' => implode(' |##| ', $rolelist)
                    );
                }
				$adb->run_insert_data('vtiger_vdusers_roles', $data);
			}
		}
	}
	
	public function validateRequest(Vtiger_Request $request)
	{
		return true;
	}
	
	public function checkPermission(Vtiger_Request $request)
	{
		return true;
	}

    public function cvprocess(Vtiger_Request $request) {
        $sourceModuleName = $request->get('source_module');
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModuleName);
        $customViewModel = $this->getCVModelFromRequest($request);
        $response = new Vtiger_Response();
        $curModel = Vtiger_Module_Model::getInstance('VDUsers');

        if (!$customViewModel->checkDuplicate()) {
            $customViewModel->save();
            $cvId = $customViewModel->getId();
            /**
             * We are setting list_headers in session when we manage columns.
             * we should clear this from session in order to apply view
             */
            $listViewSessionKey = $sourceModuleName.'_'.$cvId;
            Vtiger_ListView_Model::deleteParamsSession($listViewSessionKey,'list_headers');
            $response->setResult(array('id'=>$cvId, 'listviewurl'=>$curModel->getListViewUrl().'&viewname='.$cvId));
        } else {
            $response->setError(vtranslate('LBL_CUSTOM_VIEW_NAME_DUPLICATES_EXIST', $moduleName));
        }

        $response->emit();
    }

    /**
     * Function to get the custom view model based on the request parameters
     * @param Vtiger_Request $request
     * @return CustomView_Record_Model or Module specific Record Model instance
     */
    private function getCVModelFromRequest(Vtiger_Request $request) {
        $cvId = $request->get('record');

        if(!empty($cvId)) {
            $customViewModel = CustomView_Record_Model::getInstanceById($cvId);
        } else {
            $customViewModel = CustomView_Record_Model::getCleanInstance();
            $customViewModel->setModule($request->get('source_module'));
        }

        $customViewData = array(
            'cvid' => $cvId,
            'viewname' => $request->get('viewname'),
            'setdefault' => $request->get('setdefault'),
            'setmetrics' => $request->get('setmetrics'),
            'status' => $request->get('status')
        );
        $selectedColumnsList = $request->get('columnslist');
        if(!empty($selectedColumnsList)) {
            $customViewData['columnslist'] = $selectedColumnsList;
        }
        $stdFilterList = $request->get('stdfilterlist');
        if(!empty($stdFilterList)) {
            $customViewData['stdfilterlist'] = $stdFilterList;
        }
        $advFilterList = $request->get('advfilterlist');
        if(!empty($advFilterList)) {
            $customViewData['advfilterlist'] = $advFilterList;
        }
        if($request->has('sharelist')) {
            $customViewData['sharelist'] = $request->get('sharelist');
            if($customViewData['sharelist'] == '1')
                $customViewData['members'] = $request->get('members');
        }
        return $customViewModel->setData($customViewData);
    }

}
