<?php

class EMAILMaker_Import_View extends Vtiger_Index_View {

    public function process(Vtiger_Request $request){
        $viewer = $this->getViewer($request);
        $viewer->assign("MODULE", $request->get('module'));
        $viewer->assign("MODULELABEL", vtranslate($request->get('module'), $request->get('module')));

        $viewer->assign('IMPORT_UPLOAD_SIZE_MB', Vtiger_Util_Helper::getMaxUploadSize());
        $viewer->assign('IMPORT_UPLOAD_SIZE', Vtiger_Util_Helper::getMaxUploadSizeInBytes());

        $viewer->view('ImportEMAILTemplate.tpl', 'EMAILMaker');
    }
    function getHeaderScripts(Vtiger_Request $request){
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            'modules.EMAILMaker.resources.Import'
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}