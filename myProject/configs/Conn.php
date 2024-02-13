<?php

// NOTES: The index of the main dropinbase table must match the DBINDEX value in Dib.php
//        Using an IP address as host (eg 127.0.0.1 instead of 'localhost') can affect performance

DIB::$DATABASES = array(
1 => array(
        'database' => 'dropinbase',
        'dbType' => 'mysql',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => '',
        'host' => 'host.docker.internal',
        'port' => 3307,
        'emulatePrepare' => true,
        'dbDropin' => 'dibMySqlPdo',
        'systemDropin' => true
    ),
5 => array(
        'database' => 'warehouseDB',
        'dbType' => 'mysql',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => '',
        'host' => 'host.docker.internal',
        'port' => 3307,
        'emulatePrepare' => true,
        'dbDropin' => 'dibMySqlPdo',
        'systemDropin' => true
    ),
);