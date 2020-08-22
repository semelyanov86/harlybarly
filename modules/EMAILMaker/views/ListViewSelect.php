<?php

class EMAILMaker_ListViewSelect_View extends Vtiger_IndexAjax_View {

    function checkPermission(Vtiger_Request $request){
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        EMAILMaker_Debugger_Model::GetInstance()->Init();
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
        if ($EMAILMaker->CheckPermissions("DETAIL") == false) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }

    public function process(Vtiger_Request $request){
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $recordIds = $this->getRecordsListFromRequest($request);
        $viewer = $this->getViewer($request);
        global  $current_language;
        $adb = PearDatabase::getInstance();        
        EMAILMaker_Debugger_Model::GetInstance()->Init();
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();

        if ($EMAILMaker->CheckPermissions("DETAIL") == false){
            $output = '<table border=0 cellspacing=0 cellpadding=5 width=100% class=layerHeadingULine>
                <tr>
                      <td width="90%" align="left" class="genHeaderSmall" id="EMAILListViewDivHandle" style="cursor:move;">' . vtranslate("LBL_EMAIL_ACTIONS",'EMAILMaker') . '                 			
                      </td>
                      <td width="10%" align="right">
                              <a href="javascript:fninvsh(\'EMAILListViewDiv\');"><img title="' . vtranslate("LBL_CLOSE") . '" alt="' . vtranslate("LBL_CLOSE") . '" src="themes/images/close.gif" border="0"  align="absmiddle" /></a>
                      </td>
                </tr>
                </table>
                <table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
                    <tr><td class="small">
                        <table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
                        <tr>
                          <td class="dvtCellInfo" style="width:100%;border-top:1px solid #DEDEDE;text-align:center;">
                            <strong>' . vtranslate("LBL_PERMISSION_DENIED") . '</strong>
                          </td>
                        </tr>
                        <tr>
                                      <td class="dvtCellInfo" style="width:100%;" align="center">
                            <input type="button" class="crmbutton small cancel" value="' . vtranslate("LBL_CANCEL") . '" onclick="fninvsh(\'EMAILListViewDiv\');" />      
                          </td>
                              </tr>      		
                        </table>
                    </td></tr>
                </table>
                ';
            die($output);
        }

        $_REQUEST['idslist'] = implode(";", $recordIds);
        $request->set('idlist', $_REQUEST['idslist']);
        $current_language = Vtiger_Language_Handler::getLanguage();
        $templates = $EMAILMaker->GetAvailableTemplatesArray($request->get('return_module'), true);        
        if (count($templates) > 0)
            $no_templates_exist = 0;
        else
            $no_templates_exist = 1;
        $viewer->assign('CRM_TEMPLATES', $templates);
        $viewer->assign('CRM_TEMPLATES_EXIST', $no_templates_exist);

        $template_output = $language_output = "";

        if ($options != "") {
            $template_output = '
		    <tr>
		  		<td class="dvtCellInfo" style="width:100%;border-top:1px solid #DEDEDE;">
		  			<select name="use_common_template" id="use_common_template" class="detailedViewTextBox" multiple style="width:90%;" size="5">
		        ' . $options . '
		        </select>
		  		</td>
				</tr>
		  ';
            $templates_select = '<select name="use_common_template" id="use_common_template" class="detailedViewTextBox" multiple style="width:90%;" size="5">
		        ' . $options . '
		        </select>';
            $temp_res = $adb->pquery("SELECT label, prefix FROM vtiger_language WHERE active = ?",array('1'));
            while ($temp_row = $adb->fetchByAssoc($temp_res)) {
                $template_languages[$temp_row["prefix"]] = $temp_row["label"];
            }

            //LANGUAGES BLOCK  
            if (count($template_languages) > 1){
                $options = "";
                foreach ($template_languages as $prefix => $label){
                    if ($current_language != $prefix)
                        $options.='<option value="' . $prefix . '">' . $label . '</option>';
                    else
                        $options.='<option value="' . $prefix . '" selected="selected">' . $label . '</option>';
                }

                $language_output = '<tr>
		  		<td class="dvtCellInfo" style="width:100%;">    	
		          <select name="template_language" id="template_language" class="detailedViewTextBox" style="width:90%;" size="1">
		  		    ' . $options . '
		          </select>
		  		</td>
		      </tr>';
                $languages_select = '<select name="template_language" id="template_language" class="detailedViewTextBox" style="width:90%;" size="1">
		  		    ' . $options . '
		          </select>';
            } else {
                foreach ($template_languages as $prefix => $label)
                    $languages_select.='<input type="hidden" name="template_language" id="template_language" value="' . $prefix . '"/>';
            }
        } else {
            $template_output = '<tr>
		                		<td class="dvtCellInfo" style="width:100%;border-top:1px solid #DEDEDE;">
		                		  ' . vtranslate("CRM_TEMPLATES_DONT_EXIST",'EMAILMaker');
            $template_output.='</td></tr>';
        }
        $viewer->assign('templates_select', $templates_select);
        $viewer->assign('languages_select', $languages_select);

        $viewer->assign('idslist', $_REQUEST['idslist']);
        $viewer->assign('relmodule', $request->get('return_module'));
        $viewer->view("ListViewSelect.tpl", 'EMAILMaker');
    }    
    function getRecordsListFromRequest(Vtiger_Request $request){
        $cvId = $request->get('viewname');
        if ($cvId == "") $cvId = $request->get('cvid');
        $selectedIds = $request->get('selected_ids');
        $excludedIds = $request->get('excluded_ids');

        if(!empty($selectedIds) && $selectedIds != 'all'){
            if(!empty($selectedIds) && count($selectedIds) > 0){
                return $selectedIds;
            }
        }

        $customViewModel = CustomView_Record_Model::getInstanceById($cvId);
        if($customViewModel){
            $searchKey = $request->get('search_key');
            $searchValue = $request->get('search_value');
            $operator = $request->get('operator');
            if(!empty($operator)) {
                $customViewModel->set('operator', $operator);
                $customViewModel->set('search_key', $searchKey);
                $customViewModel->set('search_value', $searchValue);
            }
            return $customViewModel->getRecordIds($excludedIds);
        }
    }
}