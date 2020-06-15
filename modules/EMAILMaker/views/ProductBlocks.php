<?php


class EMAILMaker_ProductBlocks_View extends Vtiger_Index_View {
    public function preProcess(Vtiger_Request $request, $display = true) {
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->assign('QUALIFIED_MODULE', $moduleName);
        Vtiger_Basic_View::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
        $linkModels = $EMAILMaker->getSideBarLinks($linkParams);
        $viewer->assign('QUICK_LINKS', $linkModels);
        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('CURRENT_VIEW', $request->get('view'));
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }
    public function process(Vtiger_Request $request) {
        EMAILMaker_Debugger_Model::GetInstance()->Init();
        $adb = PearDatabase::getInstance();        
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
        $viewer = $this->getViewer($request);
        $currentLanguage = Vtiger_Language_Handler::getLanguage();
        $result = $adb->pquery("SELECT * FROM vtiger_emakertemplates_productbloc_tpl",array());
        while ($row = $adb->fetchByAssoc($result)) {
            $templates[$row["id"]]["name"] = $row["name"];
            $templates[$row["id"]]["body"] = html_entity_decode($row["body"], ENT_QUOTES);
        }
        $viewer->assign("PB_TEMPLATES", $templates);    
        $viewer->view('ProductBlocks.tpl', 'EMAILMaker');
    }

    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            'layouts.v7.modules.EMAILMaker.resources.ProductBlocks'
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}