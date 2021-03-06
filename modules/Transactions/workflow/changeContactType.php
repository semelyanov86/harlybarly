<?php

function changeContactType($ws_entity){
    $supported_types = array('Deposit - credit card', 'Deposit - wire transaction', 'card');
    $supported_status = array('succeeded', 'Approved');
    // WS id
    $ws_id = $ws_entity->getId();
    $module = $ws_entity->getModuleName();
    if (empty($ws_id) || empty($module)) {
        return;
    }

    // CRM id
    $crmid = vtws_getCRMEntityId($ws_id);
    if ($crmid <= 0) {
        return;
    }

    $transactionInstance = Vtiger_Record_Model::getInstanceById($crmid);
    $contactId = $transactionInstance->get('payer');
    if (!$contactId) {
        return;
    }
    $contactInstance = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
    $relListModel = Vtiger_RelationListView_Model::getInstance($contactInstance, 'Transactions', 'Transactions');
    $pagingModel = new Vtiger_Paging_Model();
    $pagingModel->set('page', 1);
    $pagingModel->set('limit', 1000);
    $entries = $relListModel->getEntries($pagingModel);
    $total = 0;
    foreach ($entries as $entry) {
        if (in_array($entry->get('trstatus'), $supported_status) && in_array($entry->get('transaction_type'), $supported_types)) {
            $total++;
        }
    }
    $contactInstance->set('mode', 'edit');
    if ($total >= 2) {
        $contactInstance->set('cf_contacttype', 'Upsale');
    } elseif ($total >= 1) {
        $contactInstance->set('cf_contacttype', 'FTD');
        $contactInstance->set('ftd_owner_id', $contactInstance->get('assigned_user_id'));
    }
    if ($total > 0) {
        $contactInstance->save();
    }
}
