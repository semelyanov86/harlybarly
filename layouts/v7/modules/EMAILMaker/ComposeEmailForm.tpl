{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/ComposeEmail.php *}

{strip}
    <div class="SendEmailFormStep2 modal-dialog modal-lg" id="composeEmailContainer">
        <div class="modal-content">
            <form class="form-horizontal" id="massEmailForm" method="post" action="index.php" enctype="multipart/form-data" name="massEmailForm">
                {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE={vtranslate('LBL_COMPOSE_EMAIL', $MODULE)}}
                <div class="modal-body">
                    <input type="hidden" name="selected_ids" value='{ZEND_JSON::encode($SELECTED_IDS)}' />
                    <input type="hidden" name="excluded_ids" value='{ZEND_JSON::encode($EXCLUDED_IDS)}' />
                    <input type="hidden" name="viewname" value="{$VIEWNAME}" />
                    <input type="hidden" name="module" value="EMAILMaker"/>
                    <input type="hidden" name="mode" value="massSave" />
                    <input type="hidden" name="view" value="MassSaveAjax" />
                    <input type="hidden" name="selected_sourceid" value="{$SELECTED_SOURCEID}">
                    {foreach item=source_name key=SID name="sourcenames" from=$SOURCE_NAMES}
                        {*if $SINGLE_RECORD neq 'yes'}
                            {assign var=SID value=$source_id}
                        {else}
                            {assign var=SID value="0"}
                        {/if*}
                        <input type="hidden" name="{$SID}toemailinfo" value='{ZEND_JSON::encode($TOMAIL_INFO[$SID])}' />
                        <input type="hidden" name="{$SID}ccemailinfo" value='{ZEND_JSON::encode($CCMAIL_INFO[$SID])}' />
                        <input type="hidden" name="{$SID}bccemailinfo" value='{ZEND_JSON::encode($BCCMAIL_INFO[$SID])}' />
                        <input type="hidden"  name="{$SID}toMailNamesList" value='{ZEND_JSON::encode($TOMAIL_NAMES_LIST[$SID])}'/>
                        <input type="hidden"  name="{$SID}ccMailNamesList" value='{ZEND_JSON::encode($CCMAIL_NAMES_LIST[$SID])}'/>
                        <input type="hidden"  name="{$SID}bccMailNamesList" value='{ZEND_JSON::encode($BCCMAIL_NAMES_LIST[$SID])}'/>
                    {/foreach}
            {*else}
                <input type="hidden" name="toemailinfo" value='{ZEND_JSON::encode($TOMAIL_INFO)}' />
                <input type="hidden" name="ccemailinfo" value='{ZEND_JSON::encode($CCMAIL_INFO)}' />
                <input type="hidden" name="bccemailinfo" value='{ZEND_JSON::encode($BCCMAIL_INFO)}' />
                <input type="hidden"  name="toMailNamesList" value='{$TOMAIL_NAMES_LIST}'/>
                <input type="hidden"  name="ccMailNamesList" value='{$CCMAIL_NAMES_LIST}'/>
                <input type="hidden"  name="bccMailNamesList" value='{$BCCMAIL_NAMES_LIST}'/>*}
                    <input type="hidden"  name="to" value='{ZEND_JSON::encode($TO)}' />
                    <input type="hidden"  name="cc" value='{ZEND_JSON::encode($CC)}' />
                    <input type="hidden"  name="bcc" value='{ZEND_JSON::encode($BCC)}' />

                    <input type="hidden" id="flag" name="flag" value="" />
                    <input type="hidden" id="maxUploadSize" value="{$MAX_UPLOAD_SIZE}" />
                    <input type="hidden" id="documentIds" name="documentids" value="" />
                    <input type="hidden" name="emailMode" value="{$EMAIL_MODE}" />
                    <input type="hidden" name="source_module" value="{$SOURCE_MODULE}" />
                    {if !empty($PARENT_EMAIL_ID)}
                        <input type="hidden" name="parent_id" value="{$PARENT_EMAIL_ID}" />
                        <input type="hidden" name="parent_record_id" value="{$PARENT_RECORD}" />
                    {/if}
                    {if !empty($RECORDID)}
                        <input type="hidden" name="record" value="{$RECORDID}" />
                    {/if}
                    <input type="hidden" name="search_key" value= "{$SEARCH_KEY}" />
                    <input type="hidden" name="operator" value="{$OPERATOR}" />
                    <input type="hidden" name="search_value" value="{$ALPHABET_VALUE}" />
                    <input type="hidden" name="search_params" value='{ZEND_JSON::encode($SEARCH_PARAMS)}' />
                    <input type="hidden" name="email_template_language" value='{$EMAIL_TEMPLATE_LANGUAGE}' />
                    <div class="topContent">
                        <div class="row toEmailField">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_FROM_EMAIL','EMAILMaker')}&nbsp</span>
                                </div>
                                <div class="col-lg-6">
                                    <select name="from_email" class="select2 inputElement">
                                        {html_options  options=$FROM_EMAILS selected=$SELECTED_DEFAULT_FROM}
                                    </select>
                                </div>
                            </div>
                        </div>
                        {if $SINGLE_RECORD neq 'yes'}
                            <div class="row toEmailField">
                                <div class="col-lg-12">
                                    <div class="col-lg-2">
                                        <span class="pull-right">{vtranslate('LBL_RECORDS_LIST',$SOURCEMODULE)}&nbsp</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="emailSourcesList" class="select2 inputElement emailSourcesList">
                                            {html_options  options=$SOURCE_NAMES selected=$SELECTED_SOURCEID}
                                        </select>
                                        {*{foreach item=source_name key=source_id name="sourcenames" from=$SOURCE_NAMES}
                                                <option value="{$source_id}" {if $SELECTED_SOURCEID eq $source_id}selected{/if}>{$source_name}</option>
                                            {/foreach}*}
                                    </div>
                                </div>
                            </div>
                        {/if}
                        <div class="row toEmailField">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_TO',$MODULE)}&nbsp;<span class="redColor">*</span></span>
                                </div>
                                <div class="col-lg-6">
                                    {*if !empty($TO)}
                                        {assign var=TO_EMAILS value=","|implode:$TO}
                                    {/if*}
                                    <input id="emailField" style="width:100%" name="toEmail" type="text" class="autoComplete sourceField select2" data-rule-required="true" data-rule-multiEmails="true" value="" placeholder="{vtranslate('LBL_TYPE_AND_SEARCH',$MODULE)}">
                                    <!-- //ITS4You:{$TO_EMAILS}-->
                                </div>
                                <div class="col-lg-4 input-group">
                                    <select style="width: 140px;" class="select2 emailModulesList pull-right">
                                        {foreach item=MODULE_NAME from=$RELATED_MODULES}
                                            <option value="{$MODULE_NAME}" {if $MODULE_NAME eq $FIELD_MODULE} selected {/if}>{vtranslate($MODULE_NAME,$MODULE_NAME)}</option>
                                        {/foreach}
                                    </select>
                                    <a href="#" class="clearReferenceSelection cursorPointer" name="clearToEmailField"> X </a>
                                    <span class="input-group-addon">
                                        <span class="selectEmail cursorPointer">
                                            <i class="fa fa-search" title="{vtranslate('LBL_SELECT', $MODULE)}"></i>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    
                        <div class="row {if empty($CC)} hide {/if} ccContainer ccEmailField">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_CC',$MODULE)}</span>
                                </div>
                                <div class="col-lg-6">
                                    {if !empty($CC)}
                                        {assign var=CC_EMAILS value=","|implode:$CC}
                                    {/if}
                                    <input id="emailccField" style="width:100%" name="ccEmail" type="text" class="autoComplete sourceField select2"  data-rule-multiEmails="true" value="{*$CC_EMAILS*}" placeholder="{vtranslate('LBL_TYPE_AND_SEARCH',$MODULE)}">
                                </div>
                                <div class="col-lg-4"></div>
                            </div>
                        </div>

                        <div class="row {if empty($BCC)} hide {/if} bccContainer bccEmailField">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_BCC',$MODULE)}</span>
                                </div>
                                <div class="col-lg-6">
                                    {if !empty($BCC)}
                                        {assign var=BCC_EMAILS value=","|implode:$BCC}
                                    {/if}
                                    <input id="emailbccField" style="width:100%" name="bccEmail" type="text" class="autoComplete sourceField select2"  data-rule-multiEmails="true" value="{*$BCC_EMAILS*}" placeholder="{vtranslate('LBL_TYPE_AND_SEARCH',$MODULE)}">
                                </div>
                                <div class="col-lg-4"></div>
                            </div>
                        </div>
                    
                        <div class="row {if (!empty($CC)) and (!empty($BCC))} hide {/if} ">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                </div>
                                <div class="col-lg-6">
                                    <a href="#" class="cursorPointer {if (!empty($CC))}hide{/if}" id="ccLink">{vtranslate('LBL_ADD_CC', $MODULE)}</a>&nbsp;&nbsp;
                                    <a href="#" class="cursorPointer {if (!empty($BCC))}hide{/if}" id="bccLink">{vtranslate('LBL_ADD_BCC', $MODULE)}</a>
                                </div>
                                <div class="col-lg-4"></div>
                            </div>
                        </div>
                    
                        <div class="row subjectField">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_SUBJECT',$MODULE)}&nbsp;<span class="redColor">*</span></span>
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" name="subject" value="{$SUBJECT}" data-rule-required="true" id="subject" spellcheck="true" class="inputElement"/>
                                </div>
                                <div class="col-lg-4"></div>
                            </div>
                        </div>
                            
                        <div class="row attachment">
                            <div class="col-lg-12">
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_ATTACHMENT',$MODULE)}</span>
                                </div>
                                <div class="col-lg-9">
                                    <div class="row">
                                        <div class="col-lg-4 browse">
                                            <input type="file" {if $FILE_ATTACHED}class="removeNoFileChosen"{/if} id="multiFile" name="file[]"/>&nbsp;
                                        </div>
                                        <div class="col-lg-4 brownseInCrm">
                                            <button type="button" class="btn btn-small btn-default" id="browseCrm" data-url="{$DOCUMENTS_URL}" title="{vtranslate('LBL_BROWSE_CRM',$MODULE)}">{vtranslate('LBL_BROWSE_CRM',$MODULE)}</button>
                                        </div>
                                        <div class="col-lg-4 insertTemplate">
                                            <button id="selectEmailTemplate" class="btn btn-success pull-right" data-url="module=EMAILMaker&view=Popup&src_record={$SOURCERECORD}&src_module={$SOURCEMODULE}">{vtranslate('LBL_SELECT_EMAIL_TEMPLATE',$MODULE)}</button>
                                        </div>
                                    </div>
                                    <div id="attachments">
                                        {foreach item=ATTACHMENT from=$ATTACHMENTS}
                                            {if ('docid'|array_key_exists:$ATTACHMENT)}
                                                {assign var=DOCUMENT_ID value=$ATTACHMENT['docid']}
                                                {assign var=FILE_TYPE value="document"}
                                            {else}
                                                {assign var=FILE_TYPE value="file"}
                                            {/if}
                                            <div class="MultiFile-label customAttachment" data-file-id="{$ATTACHMENT['fileid']}" data-file-type="{$FILE_TYPE}"  data-file-size="{$ATTACHMENT['size']}" {if $FILE_TYPE eq "document"} data-document-id="{$DOCUMENT_ID}"{/if}>
                                                {if $ATTACHMENT['nondeletable'] neq true}
                                                    <a name="removeAttachment" class="cursorPointer">x </a>
                                                {/if}
                                                <span>{$ATTACHMENT['attachment']}</span>
                                            </div>
                                        {/foreach}
                                    </div>
                                    {if $PDFTEMPLATES neq ""}
                                        <input type="hidden" name="pdftemplateids" value="{$PDFTEMPLATEIDS}">
                                        <input type="hidden" name="pdflanguage" value="{$PDFLANGUAGE}">
                                        {foreach key=pdftemplateid item=pdftemplatename from=$PDFTEMPLATES}
                                            <div class="row"><a href="#" class="generatePreviewPDF cursorPointer" data-templateid="{$pdftemplateid}"><i class="fa fa-file-pdf-o" aria-hidden="true"></i>&nbsp;{$pdftemplatename}
                                                </a></div>
                                        {/foreach}
                                   {/if}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <button type="button" class="btn btn-default includeSignature">{vtranslate('LBL_INCLUDE_SIGNATURE',$MODULE)}</button>
                                 {*
                                <div class="col-lg-2">
                                    <span class="pull-right">{vtranslate('LBL_INCLUDE_SIGNATURE',$MODULE)}</span>
                                </div>
                                <div class="item col-lg-9">
                                    <input class="" type="checkbox" name="signature" value="Yes" checked="checked" id="signature">
                                </div>*}
                            </div>
                        </div>
                    </div>
                    <div class="container-fluid hide" id='emailTemplateWarning'>
                        <div class="alert alert-warning fade in">
                            <a href="#" class="close" data-dismiss="alert">&times;</a>
                            <p>{vtranslate('LBL_EMAILTEMPLATE_WARNING_CONTENT',$MODULE)}</p>
                        </div>
                    </div>         
                    <div class="row templateContent">
                        <div class="col-lg-12">
                            <textarea style="width:390px;height:200px;" id="description" name="description">{$DESCRIPTION}</textarea>
                        </div>
                    </div>
                    
                    {if $RELATED_LOAD eq true}
                        <input type="hidden" name="related_load" value={$RELATED_LOAD} />
                    {/if}
                    <input type="hidden" name="attachments" value='{ZEND_JSON::encode($ATTACHMENTS)}' />
                    <div id="emailTemplateWarningContent" style="display: none;">
                        {vtranslate('LBL_EMAILTEMPLATE_WARNING_CONTENT',$MODULE)}
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="pull-right cancelLinkContainer">
                        <a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                    </div>
                    <button id="sendEmail" name="sendemail" class="btn btn-success" title="{vtranslate("LBL_SEND_EMAIL",$MODULE)}" type="submit"><strong>{vtranslate("LBL_SEND_EMAIL",$MODULE)}</strong></button>
                </div>
            </form>
        </div>
    </div>
{/strip}
