<?php

$DIR = __DIR__;
ini_set('session.gc_maxlifetime', 21600);
if (file_exists("configs/.env.php")) {
    require "configs/.env.php";
}

if (!empty(getenv('eb_memcache')) || isset($_REQUEST['testmemcache'])) {
    ini_set('session.save_handler', 'memcached');
    if (isset($_REQUEST['testmemcache'])) {
        ini_set('session.save_path', getenv('ebtest_memcache'));
    } else {
        ini_set('session.save_path', getenv('eb_memcache'));
    }
    
    ini_set('session.lazy_write', 'On');
    ini_set('session.sess_locking', 'Off');
    ini_set('memcached.sess_lock_expire', '0');
    ini_set('memcached.allow_failover', '1');
    ini_set('memcached.chunk_size', '32768');
    ini_set('memcached.sess_lock_retries', '10');
    ini_set('memcached.default_port', '11211');
    ini_set('memcached.hash_function', 'crc32');
    ini_set('memcached.sess_prefix', 'memc.sess.key.');
}

if (isset($_REQUEST['phpinfo']) && getenv('phpinfo') == 'yes') {
    ini_set('memory_limit', $_REQUEST['phpinfo']);
    session_start();
    phpinfo(); // To view installed PHP extensions, uncomment this line, and comment out all other lines below
    die;
}


//log but dont show errors
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(30);
ini_set('display_startup_errors', 0);

$vendorPath = empty(getenv('Dropinbase_Vendor_Path')) ? "./vendor/" : getenv('Dropinbase_Vendor_Path');
include_once $vendorPath . "/dropinbase/dropinbase/index.php";
