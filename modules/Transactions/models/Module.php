<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: SalesPlatform Ltd
 * The Initial Developer of the Original Code is SalesPlatform Ltd.
 * All Rights Reserved.
 * If you have any questions or comments, please email: devel@salesplatform.ru
 ************************************************************************************/

class Transactions_Module_Model extends Vtiger_Module_Model {

    /**
     * Function to check whether the module is summary view supported
     * @return Boolean - true/false
     */
    public function isSummaryViewSupported() {
        return false;
    }

    public function isQuickCreateSupported()
    {
        return false;
    }

    public function getModuleIcon() {
        $moduleName = $this->getName();
        $lowerModuleName = strtolower($moduleName);
        $title = vtranslate($moduleName, $moduleName);
        $moduleIcon = "<i class='vicon-$lowerModuleName' title='$title'></i>";

        return $moduleIcon;
    }

}
