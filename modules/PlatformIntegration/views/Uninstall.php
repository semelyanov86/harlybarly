<?php

include_once "vtlib/Vtiger/Module.php";
class PlatformIntegration_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>PlatformIntegration</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance("PlatformIntegration");
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        $res_template_v6 = $this->delete_folder("layouts/vlayout/modules/PlatformIntegration");
        $res_template_v7 = $this->delete_folder("layouts/v7/modules/PlatformIntegration");
        echo "&nbsp;&nbsp;- Delete PlatformIntegration template folder<br>";
        if ($res_template_v6) {
            echo "&nbsp;&nbsp;+ vlayout - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        if ($res_template_v7) {
            echo "&nbsp;&nbsp;+ v7 - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $res_module = $this->delete_folder("modules/PlatformIntegration");
        echo "&nbsp;&nbsp;- Delete PlatformIntegration module folder";
        if ($res_module) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("PlatformIntegration"));
        echo "Module was Uninstalled.</div>";
    }
    public function delete_folder($tmp_path)
    {
        if (!is_dir($tmp_path)) {
            return false;
        }
        if (!is_writeable($tmp_path) && is_dir($tmp_path)) {
            chmod($tmp_path, 511);
        }
        $handle = opendir($tmp_path);
        while ($tmp = readdir($handle)) {
            if ($tmp != ".." && $tmp != "." && $tmp != "") {
                if (is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp)) {
                    unlink($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp)) {
                        chmod($tmp_path . DS . $tmp, 438);
                        unlink($tmp_path . DS . $tmp);
                    }
                }
                if (is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp)) {
                    $this->delete_folder($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp)) {
                        chmod($tmp_path . DS . $tmp, 511);
                        $this->delete_folder($tmp_path . DS . $tmp);
                    }
                }
            }
        }
        closedir($handle);
        rmdir($tmp_path);
        if (!is_dir($tmp_path)) {
            return true;
        }
        return false;
    }
    public function removeData()
    {
        global $adb;
        $message = "";
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("PlatformIntegration"));
        $sql = "DROP TABLE `platformintegration_api`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_modules`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_mapping_modules`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_mapping_fields`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_modules_fields`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_picklist_fields`;";
        $result = $adb->pquery($sql, array());
        $sql = "DROP TABLE `platformintegration_mapping_tax`;";
        $result = $adb->pquery($sql, array());
        $message .= "&nbsp;&nbsp;- Delete PlatformIntegration tables";
        if ($result) {
            $message .= " - DONE";
        } else {
            $message .= " - <b>ERROR</b>";
        }
        $message .= "<br>";
        return $message;
    }
}

?>