<?php

// ----- dibtestCompanyConsultantGrid -----

// click btninlineadd 
$params = array('action'=>'btninlineadd', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyConsultantGrid', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyConsultantGrid');

// click btnSubmissionDataGrid 
$params = array('action'=>'btnSubmissionDataGrid', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyConsultantGrid', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyConsultantGrid');

// click btngriddelete 
$params = array('action'=>'btngriddelete', 'actionType'=>'itemAlias', 'trigger'=>'click', 'containerName'=>'dibtestCompanyConsultantGrid', 'portId'=>null);
ClientFunctions::addAction($actionList, 'run', $params, 'dibtestCompanyConsultantGrid');

