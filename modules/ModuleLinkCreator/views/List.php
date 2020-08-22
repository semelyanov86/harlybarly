<?php

include "modules/ModuleLinkCreator/ModuleLinkCreator.php";
/**
 * Class ModuleLinkCreator_List_View
 */
class ModuleLinkCreator_List_View extends Vtiger_Index_View
{
    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED", "Vtiger"));
        }
    }
    /**
     * @param Vtiger_Request $request
     * @param bool|true $display
     * @return bool|void
     */
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $listViewModel = Vtiger_Module_Model::getInstance($moduleName);
        $linkParams = array("MODULE" => $moduleName, "ACTION" => $request->get("view"));
        $viewer->assign("CUSTOM_VIEWS", CustomView_Record_Model::getAllByGroup($moduleName));
        $this->viewName = $request->get("viewname");
        if (empty($this->viewName)) {
            $customView = new CustomView();
            $this->viewName = $customView->getViewId($moduleName);
        }
        $quickLinkModels = $listViewModel->getSideBarLinks($linkParams);
        $viewer->assign("QUICK_LINKS", $quickLinkModels);
        $this->initializeListViewContents($request, $viewer);
        $viewer->assign("VIEWID", $this->viewName);
        if ($display) {
            $this->preProcessDisplay($request);
        }
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $module);
        $rs = $adb->pquery("SELECT * FROM `vte_modules` WHERE module=? AND valid='1';", array($module));
        if ($adb->num_rows($rs) == 0) {
            $viewer->view("InstallerHeader.tpl", $module);
        }
    }
    /**
     * @param Vtiger_Request $request
     * @param $vTELicense
     */
    public function step2(Vtiger_Request $request, $vTELicense)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("VTELICENSE", $vTELicense);
        $viewer->assign("SITE_URL", $site_URL);
        $viewer->view("Step2.tpl", $module);
    }
    /**
     * @param Vtiger_Request $request
     */
    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view("Step3.tpl", $module);
    }
    /**
     * @param Vtiger_Request $request
     */
    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $vTELicense = new ModuleLinkCreator_VTELicense_Model($module);
        if (!$vTELicense->validate()) {
            $this->step2($request, $vTELicense);
        } else {
            $rs = $adb->pquery("SELECT * FROM `vte_modules` WHERE module=? AND valid='1';", array($module));
            if ($adb->num_rows($rs) == 0) {
                $this->step3($request);
            } else {
                $mode = $request->getMode();
                if ($mode) {
                    $this->{$mode}($request);
                } else {
                    $viewer = $this->getViewer($request);
                    $moduleName = $request->getModule();
                    $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                    $this->viewName = $request->get("viewname");
                    $this->initializeListViewContents($request, $viewer);
                    $viewer->assign("VIEW", $request->get("view"));
                    $viewer->assign("MODULE_MODEL", $moduleModel);
                    $viewer->assign("CURRENT_USER_MODEL", Users_Record_Model::getCurrentUserModel());
                    $viewer->view("ListViewContents.tpl", $moduleName);
                }
            }
        }
    }
    /**
     * Function to initialize the required data in smarty to display the List View Contents
     * @param Vtiger_Request $request
     * @param Vtiger_Viewer $viewer
     */
    public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer)
    {
        $moduleName = $request->getModule();
        $listViewHeaders = array("id" => "LBL_ID", "module_label" => "LBL_MODULE_LABEL", "module_name" => "LBL_MODULE_NAME", "description" => "LBL_DESCRIPTION");
        $viewer->assign("LISTVIEW_HEADERS", $listViewHeaders);
        $recordModel = new ModuleLinkCreator_Record_Model();
        $records = $recordModel->findAll();
        $viewer->assign("RECORDS", $records);
        $viewer->assign("LISTVIEW_ENTRIES_COUNT", count($records));
        $viewer->assign("MODULE", $moduleName);
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules." . $moduleName . ".resources.List");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>