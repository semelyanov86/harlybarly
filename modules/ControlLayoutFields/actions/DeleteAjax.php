<?php

class ControlLayoutFields_DeleteAjax_Action extends Settings_Vtiger_Index_Action
{
    public function process(Vtiger_Request $request)
    {
        $qualifiedModule = $request->getModule(false);
        $recordId = $request->get("record");
        $response = new Vtiger_Response();
        $recordModel = Settings_ControlLayoutFields_Record_Model::getInstance($recordId);
        if ($recordModel->isDefault()) {
            $response->setError("LBL_DEFAULT_WORKFLOW", vtranslate("LBL_CANNOT_DELETE_DEFAULT_WORKFLOW", $qualifiedModule));
        } else {
            $recordModel->delete();
            $response->setResult(array("success" => "ok"));
        }
        $response->emit();
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>