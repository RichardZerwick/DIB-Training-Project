<?php

// ----- dibtestCompanyForm -----
/*
// click btnadd 
$params = array('action'=>'btnadd', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

// click btnPrompt 
$params = array('action'=>'btnPopup', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

// click btnPopup 
$params = array('action'=>'btnPopup', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

// click btnRun1 
$params = array('action'=>'btnRun1', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

// click tab layoutcolumn 
$params = array('item'=>'layoutcolumn', 'containerName'=>'dibtestCompanyForm');
ClientFunctions::addAction($actionList, 'activate-tab', $params, 'dibtestCompanyForm');

// fill fields 
$params = array(
    'notes'=>"cjlndygez ",
    'id'=>"2",
    'name'=>"cyfnkpmxrjeihbsgzwv",
    'chinese_name'=>"uwzhxdakr",
    'parent_company_id'=>"3",
    'website'=>"csfjyde",
    'parent_company_contact_id'=>"3",
    'icon'=>"2019-10-17"
);
ClientFunctions::addAction($actionList, 'set-value', $params, 'dibtestCompanyForm');

*/
// fill fields 
$params = array(
    'parent_company_contact_id'=>"Da",
);
ClientFunctions::addAction($actionList, 'set-value-list', $params, 'dibtestCompanyForm');

/*
// click btnsave 
$params = array('action'=>'btnsave', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

// click tab dibtestCompanyConsultantGrid 
$params = array('item'=>'dibtestCompanyConsultantGrid', 'containerName'=>'dibtestCompanyForm');
ClientFunctions::addAction($actionList, 'activate-tab', $params, 'dibtestCompanyForm');


// ==> run tests in subcontainer dibtestCompanyConsultantGrid 
include 'dibtestCompanyConsultantGrid.php';

// click tab dibtestConsultantGrid 
$params = array('item'=>'dibtestConsultantGrid', 'containerName'=>'dibtestCompanyForm');
ClientFunctions::addAction($actionList, 'activate-tab', $params, 'dibtestCompanyForm');


// ==> run tests in subcontainer dibtestConsultantPortGrid 
include 'dibtestConsultantPortGrid.php';

// click btndelete 
$params = array('action'=>'btndelete', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyForm', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyForm');

*/