/*********************************************************************************
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ********************************************************************************/
jQuery.Class("EMAILMaker_Actions_Js",{
    templatesElements : {},
    massEmailForm : false,

    showOtherEmailsSelect: function (container,type){
        container.find('#'+type+'ccLinkContent').addClass('hide');
        container.find('.'+type+'ccContent').removeClass('hide');
    },
    showComposeEmailForm : function(params){
        var aDeferred = jQuery.Deferred();

        app.request.post({data:params}).then(function(err,data){
            app.helper.hideProgress();
            if(err === null) {
                var modalContainer = app.helper.showModal(data, {cb: function(){
                    var emailEditInstance = new EMAILMaker_MassEdit_Js();
                    emailEditInstance.registerEvents();
                }});
                return aDeferred.resolve(modalContainer);
            }
        });
        return aDeferred.promise();
    },
    registerEmailFieldSelectionEvent : function(container){
        var self = this;
        var selectEmailForm = container.find("#SendEmailFormStep1");
        selectEmailForm.on('submit',function(e){
            e.preventDefault();
            var form = jQuery(e.currentTarget);
            self.setSelectedPDFTemplates(form);

            var params = form.serialize();

            var params = self.addEmailsToParams(params,form,"");
            var params = self.addEmailsToParams(params,form,"cc");
            var params = self.addEmailsToParams(params,form,"bcc");

            app.helper.showProgress();
            app.helper.hideModal().then(function(){
                self.showComposeEmailForm(params);
            });
        });
    },
    addEmailsToParams: function(params,form,type){
        var fieldLists = [];
        form.find('#email'+type+'Field').find('option:selected').each(function (i, ob) {
            fieldLists.push(jQuery(ob).val());
        });
        return params + '&'+type+'field_lists=' + JSON.stringify(fieldLists);
    },
    setSelectedPDFTemplates : function(form) {

        var ispdfactive = form.find('#ispdfactive').val();
        var rowValues = '';

        if (ispdfactive === "1") {
            var valueSelectElement = form.find('[name="use_common_pdf_template"]');
            var selectedOptions = valueSelectElement.find('option:selected');
            var newvaluesArr = [];
            jQuery.each(selectedOptions,function(i,e) {
                newvaluesArr.push(jQuery.trim(jQuery(e).val()));
            });

            if(selectedOptions.length > 0){
                rowValues = newvaluesArr.join(';');
            }
        }

        form.find('#pdftemplateid').val(rowValues);
    },

    registerPDFMakerEvents: function (modalContainer){
        modalContainer.find('#addPDFMakerTemplate').on('click',function(){
            modalContainer.find('#EMAILMakerPDFTemplatesContainer').removeClass('hide');
            modalContainer.find('#EMAILMakerPDFTemplatesContainer').show();
            modalContainer.find('#EMAILMakerPDFTemplatesBtn').hide();
            modalContainer.find('#ispdfactive').val('1');
        });
        modalContainer.find('#removePDFMakerTemplate').on('click',function(){
            modalContainer.find('#EMAILMakerPDFTemplatesContainer').hide();
            modalContainer.find('#EMAILMakerPDFTemplatesBtn').show();
            modalContainer.find('#EMAILMakerPDFTemplatesBtn').removeClass('hide');
            modalContainer.find('#ispdfactive').val('0');
        });
    },

    getListViewPopup: function () {
        this.emailmaker_sendMail();
    },
    getMoreParams: function () {
        let forview_val = app.view(),
            params;

        if ('Detail' === forview_val) {
            params = {
                selected_ids: app.getRecordId()
            };

        } else if ('List' === forview_val) {
            let listInstance = this.getListInstance();

            if ('object' === typeof listInstance) {
                if (500 < listInstance.getSelectedRecordCount()) {
                    app.helper.showErrorNotification({message: app.vtranslate('JS_MASS_EDIT_LIMIT')});
                }

                params = listInstance.getListSelectAllParams(true);
            } else {
                params = {};
            }
        }
        return params;
    },
    getSelectedTab : function() {
        var tabContainer = this.getTabContainer();
        return tabContainer.find('li.active');
    },
    getAllTabs : function() {
        var tabContainer = this.getTabContainer();
        return tabContainer.find('li');
    },
    getTabContainer : function() {
        return jQuery('div.related-tabs');
    },
    getRelatedModuleName : function() {
        return jQuery('.relatedModuleName').val();
    },
    emailmaker_sendMail: function (pdftemplateid, pdflanguage, pid, forCampaigns){
        var self = this;
        var source_module =  app.getModuleName();
        var forview_val = app.view();

        app.helper.checkServerConfig('EMAILMaker').then(function(data){
            if (data === true) {


                var postData = {
                    'module': 'EMAILMaker',
                    'view': 'IndexAjax',
                    'mode': 'showComposeEmailForm',
                    'step': 'step1',
                    'pid': pid,
                    'sourceModule': source_module,
                    'selecttemplates': 'true',
                    'forview': forview_val
                };

                var moreParams = self.getMoreParams();
                jQuery.extend(postData, moreParams);

                if (forCampaigns) {

                    var selectedTabElement = self.getSelectedTab();
                    var relatedModuleName = self.getRelatedModuleName();
                    var relatedController = new Campaigns_RelatedList_Js(app.getRecordId(), app.getModuleName(), selectedTabElement, relatedModuleName);

                    var selectedIds = relatedController.readSelectedIds();
                    //var selectedIds = relatedController.reladExcludedIds();
                    if(selectedIds != ''){
                        postData["cid"] = app.getRecordId();
                        postData["sourceModule"] = relatedModuleName;
                        postData["forview"] = "List";
                        postData["selected_ids"] = selectedIds;
                    } else {
                        app.helper.showAlertBox({message: app.vtranslate('JS_PLEASE_SELECT_ONE_RECORD')});
                        return false;
                    }
                }

                if (pdftemplateid !== "") {
                    postData['pdftemplateid'] = pdftemplateid;
                }
                if (pdflanguage !== "") {
                    postData['pdflanguage'] = pdflanguage;
                }

                if (typeof Vtiger_List_Js === 'function') {
                    var listViewInstance = new Vtiger_List_Js();
                    if (typeof listViewInstance.getListSearchParams === 'function') {
                        postData['search_params'] = JSON.stringify(listViewInstance.getListSearchParams());
                    }
                }

                app.helper.showProgress();

                app.request.post({'data' : postData}).then(
                    function(err,response) {
                        if(err === null){

                            app.helper.hideProgress();
                            app.helper.showModal(response, {
                                'cb' : function(modalContainer) {

                                    var templateElement = modalContainer.find('#use_common_email_template');
                                    if (templateElement.length > 0) {
                                        if ( templateElement.is( "select" ) ) {
                                            templateElement.select2();
                                        }
                                    }
                                    var emailTemplateLanguageElement = modalContainer.find('#email_template_language');
                                    if (emailTemplateLanguageElement.length > 0) {
                                        if ( emailTemplateLanguageElement.is( "select" ) ) {
                                            emailTemplateLanguageElement.select2();
                                        }
                                    }

                                    modalContainer.find('.emailFieldSelects').select2();
                                    modalContainer.find('#ccLink').on('click', function(){
                                        self.showOtherEmailsSelect(modalContainer,'');
                                    });
                                    modalContainer.find('#bccLink').on('click', function(){
                                        self.showOtherEmailsSelect(modalContainer,'b');
                                    });

                                    self.registerEmailFieldSelectionEvent(modalContainer);

                                    var PDFTemplatesElement = modalContainer.find('#use_common_pdf_template');
                                    if (PDFTemplatesElement.length > 0) {
                                        PDFTemplatesElement.select2();
                                    }

                                    self.registerPDFMakerEvents(modalContainer);
                                }
                            });
                            /*
                            data = jQuery(data);
                            var form = data.find('#SendEmailFormStep1');
                            var params = form.serializeFormData();
                            var emailFields = form.find('.emailFields');
                            var length = emailFields.val();
                            var total_emailoptout = params['total_emailoptout'];
                            if (total_emailoptout == '' || total_emailoptout == undefined) total_emailoptout = 0;
                            if(length > 1 || total_emailoptout > 0){
                                app.showModalWindow(data, {'text-align': 'left'});
                                thisInstance.callBackFunction(data, module, crmid, pid);
                            } else {
                                var fieldLists = new Array();
                                form.find('.emailToFields').find('option').each(function (i, ob) {
                                    fieldLists.push(jQuery(ob).val());
                                });
                                params['field_lists'] = JSON.stringify(fieldLists);
                                if (pdftemplateid != "") params['pdftemplateid'] = pdftemplateid;
                                if (pdflanguage != "") params['pdflanguage'] = pdflanguage;
                                params['email_template_language'] = jQuery('#email_template_language').val();
                                params['emailtemplateid'] = EMAILMaker_Actions_Js.getSelectedTemplates();
                                thisInstance.openEmailComposeWindow(params, module, crmid, pid)
                            }*/
                        }
                    }
                );
            } else {
                alert(app.vtranslate('JS_EMAIL_SERVER_CONFIGURATION'));
            }
        });
    },
    /*
	 * Function to get the Mass Email Form
	 */
    getMassEmailForm : function(){
        if(this.massEmailForm === false){
            this.massEmailForm = jQuery("#massEmailForm");
        }
        return this.massEmailForm;
    },
    registerAutoCompleteFields : function(container) {
        var thisInstance = this;
        var lastResults = [];
        container.find('#emailField').select2({
            minimumInputLength: 3,
            closeOnSelect : false,

            tags : [],
            tokenSeparators: [","],

            ajax : {
                'url' : 'index.php?module=Emails&action=BasicAjax',
                'dataType' : 'json',
                'data' : function(term,page){
                    var data = {};
                    data['searchValue'] = term;
                    return data;
                },
                'results' : function(data){
                    var finalResult = [];
                    var results = data.result;
                    var resultData = [];
                    for(var moduleName in results) {
                        var moduleResult = [];
                        moduleResult.text = moduleName;

                        var children = [];
                        for(var recordId in data.result[moduleName]) {
                            var emailInfo = data.result[moduleName][recordId];
                            for (var i in emailInfo) {
                                var childrenInfo = [];
                                childrenInfo.recordId = recordId;
                                childrenInfo.id = emailInfo[i].value;
                                childrenInfo.text = emailInfo[i].label;
                                children.push(childrenInfo);
                            }
                        }
                        moduleResult.children = children;
                        resultData.push(moduleResult);
                    }
                    finalResult.results = resultData;
                    lastResults = resultData;
                    return finalResult;
                },
                transport : function(params) {
                    return jQuery.ajax(params);
                }
            },
            createSearchChoice : function(term) {
                //checking for results if there is any if not creating as value
                if(lastResults.length === 0) {
                    return { id: term, text: term };
                }
            },
            escapeMarkup: function(m) {
                // Do not escape HTML in the select options text
                return m;
            }
        }).on("change", function (selectedData) {
            var addedElement = selectedData.added;
            if (typeof addedElement !== 'undefined') {
                var data = {
                    'id' : addedElement.recordId,
                    'name' : addedElement.text,
                    'emailid' : addedElement.id
                };
                thisInstance.addToEmails(data);
                if (typeof addedElement.recordId !== 'undefined') {
                    thisInstance.addToEmailAddressData(data);
                    thisInstance.appendToSelectedIds(addedElement.recordId);
                }

                var preloadData = thisInstance.getPreloadData();
                var emailInfo = {
                    'id' : addedElement.id
                };
                if (typeof addedElement.recordId !== 'undefined') {
                    emailInfo['text'] = addedElement.text;
                    emailInfo['recordId'] = addedElement.recordId;
                } else {
                    emailInfo['text'] = addedElement.id;
                }
                preloadData.push(emailInfo);
                thisInstance.setPreloadData(preloadData);
            }

            var removedElement = selectedData.removed;
            if (typeof removedElement !== 'undefined') {
                var data = {
                    'id' : removedElement.recordId,
                    'name' : removedElement.text,
                    'emailid' : removedElement.id
                };
                thisInstance.removeFromEmails(data);
                if (typeof removedElement.recordId !== 'undefined') {
                    thisInstance.removeFromSelectedIds(removedElement.recordId);
                    thisInstance.removeFromEmailAddressData(data);
                }

                var preloadData = thisInstance.getPreloadData();
                var updatedPreloadData = [];
                for(var j in preloadData) {
                    var preloadDataInfo = preloadData[j];
                    var skip = false;
                    if (removedElement.id == preloadDataInfo.id) {
                        skip = true;
                    }
                    if (skip === false) {
                        updatedPreloadData.push(preloadDataInfo);
                    }
                }
                thisInstance.setPreloadData(updatedPreloadData);
            }
        });

        container.find('#emailField').select2("container").find("ul.select2-choices").sortable({
            containment: 'parent',
            start: function(){
                container.find('#emailField').select2("onSortStart");
            },
            update: function(){
                container.find('#emailField').select2("onSortEnd");
            }
        });

        var toEmailNamesList = JSON.parse(container.find('[name="toMailNamesList"]').val());
        var toEmailInfo = JSON.parse(container.find('[name="toemailinfo"]').val());
        var toEmails = container.find('[name="toEmail"]').val();
        var toFieldValues = [];
        if (toEmails.length > 0) {
            toFieldValues = toEmails.split(',');
        }

        var preloadData = thisInstance.getPreloadData();
        if (typeof toEmailInfo !== 'undefined') {
            for(var key1 in toEmailInfo) {
                if (toEmailNamesList.hasOwnProperty(key1)) {
                    for (var k in toEmailNamesList[key1]) {
                        var emailId = toEmailNamesList[key1][k].value;
                        var emailInfo = {
                            'recordId' : key1,
                            'id' : emailId,
                            'text' : toEmailNamesList[key1][k].label+' <b>('+emailId+')</b>'
                        };
                        preloadData.push(emailInfo);
                        if (jQuery.inArray(emailId, toFieldValues) != -1) {
                            var index = toFieldValues.indexOf(emailId);
                            if (index !== -1) {
                                toFieldValues.splice(index, 1);
                            }
                        }
                    }
                }
            }
        }
        if (typeof toFieldValues !== 'undefined') {
            for(var i in toFieldValues) {
                var emailId = toFieldValues[i];
                var emailInfo = {
                    'id' : emailId,
                    'text' : emailId
                };
                preloadData.push(emailInfo);
            }
        }
        if (typeof preloadData != 'undefined') {
            thisInstance.setPreloadData(preloadData);
            container.find('#emailField').select2('data', preloadData);
        }
    },

    getListInstance : function() {
        var listInstance = window.app.controller();
        return listInstance;
    }
},{
    getLinkKey : function(){
        var link_key = '';
        var tabContainer =  jQuery('div.related-tabs');
        if (typeof tabContainer != 'undefined') {
            var active_tab = tabContainer.find('li.active');
            if (typeof active_tab != 'undefined') {
                var link_key = active_tab.data('link-key');
                if (typeof link_key == 'undefined') {
                    link_key = '';
                }
            }
        }
        return link_key;
    },

    addButtons: function (container, forCampaigns) {
        if (!container.find('#EMAILMakerContentDiv').length) {
            let recordId = app.getRecordId(),
                source_module = app.getModuleName(),
                view = app.view(),
                params = {
                    module: 'EMAILMaker',
                    source_module: source_module,
                    view: 'GetEMAILActions',
                    record: recordId,
                    mode: 'getButtons',
                    forview: view
                };

            app.request.post({'data': params}).then(function (error, data) {
                if (!error) {
                    if (data) {
                        container.append(data);
                        container.find('.selectEMAILTemplates').on('click', function () {
                            EMAILMaker_Actions_Js.emailmaker_sendMail('', '', '', forCampaigns);
                        });
                    }
                }
            });
        }
    },

    addRelatedButtons: function (){
        if (app.getModuleName() == "Campaigns") {
            const sendEmailCampaignContainer = jQuery('.sendEmail');

            if (sendEmailCampaignContainer.length > 0) {
                var newElement = jQuery("<div class='btn-group'></div>");
                sendEmailCampaignContainer.closest('.btn-toolbar').append(newElement);
                this.addButtons(newElement, true);
            }
        }
    },

    registerRelatedListLoad: function (){
        var self = this;

        app.event.on('post.relatedListLoad.click', function (event, searchRow) {
            var linkKey = self.getLinkKey();

            if (linkKey != 'LBL_RECORD_DETAILS' && linkKey != 'LBL_RECORD_SUMMARY') {
                self.addRelatedButtons();
            }

        });
    },
    registerDraggable: function () {
        jQuery(document).ajaxComplete(function (event, request, settings) {
            if (settings.data && -1 < settings.data.indexOf("EMAILMaker")) {
                jQuery("#sendEmailContainer, #composeEmailContainer").draggable({handle: '.modal-header'});
            }
        });
    },
    registerEvents: function () {
        var detailViewButtonContainerDiv = jQuery('.detailview-header');

        if (detailViewButtonContainerDiv.length) {
            this.addButtons(detailViewButtonContainerDiv, false);
        }
        this.addRelatedButtons();
        this.registerRelatedListLoad();
        this.registerDraggable()
    }
});
jQuery(document).ready(function(){
    var instance = new EMAILMaker_Actions_Js();
    instance.registerEvents();
});
