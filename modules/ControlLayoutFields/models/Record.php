<?php

class ControlLayoutFields_Record_Model extends Vtiger_Record_Model
{
    public function getTasks()
    {
        $adb = PearDatabase::getInstance();
        $sql = "SELECT * FROM vte_control_layout_fields_task WHERE clf_id = ?";
        $result = $adb->pquery($sql, array($this->getId()));
        $taskList = array();
        $noOfFields = $adb->num_rows($result);
        if (0 < $noOfFields) {
            for ($i = 0; $i < $noOfFields; $i++) {
                $taskId = $adb->query_result($result, $i, "id");
                $clf_id = $adb->query_result($result, $i, "clf_id");
                $active = $adb->query_result($result, $i, "active");
                $title = $adb->query_result($result, $i, "name");
                $actions = $adb->query_result($result, $i, "actions");
                $active_link = "?module=ControlLayoutFields&parent=Settings&action=TaskAjax&mode=ChangeStatus&task_id=" . $taskId . "&active=" . $active;
                $remove_link = "?module=ControlLayoutFields&parent=Settings&action=TaskAjax&mode=Delete&task_id=" . $taskId;
                $taskList[$i] = array("id" => $taskId, "active" => $active, "title" => $title, "clf_id" => $clf_id, "actions" => $actions, "active_url" => $active_link, "remove_link" => $remove_link);
            }
        }
        return $taskList;
    }
    public function getInfo()
    {
        $adb = PearDatabase::getInstance();
        $sql = "SELECT * FROM vte_control_layout_fields WHERE id = ? LIMIT 0,1";
        $result = $adb->pquery($sql, array($this->getId()));
        $clf_info = array();
        if ($noOfFields = 0 < $adb->num_rows($result)) {
            $clf_info["id"] = $adb->query_result($result, 0, "id");
            $clf_info["module"] = $adb->query_result($result, 0, "module");
            $clf_info["description"] = $adb->query_result($result, 0, "description");
            $clf_info["condition"] = $adb->query_result($result, 0, "condition");
        }
        return $clf_info;
    }
}

?>