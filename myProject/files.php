<?php
$DIR = __DIR__;
$vendorPath = empty(getenv('Dropinbase_Vendor_Path'))? "./vendor/" :getenv('Dropinbase_Vendor_Path');  
include_once($vendorPath."/dropinbase/dropinbase/files.php");
