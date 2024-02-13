<?php 
/*
    public static $SITEMAINTENANCE=array(
        'startTime' => '2023-07-24 13:22', 
        'warningMsg' => 'The site will be down for upgrades & maintenance from xxx to xxx today.',
        'coverPageMsg' => 'Oops!<br>Temporarily unavailable due to scheduled upgrades & maintenance<br>We should be up again by xxx.',
        'coverPage' => 'sitemaintenance.php',
        'allowedIps' => array()
    );
*/

if(empty(DIB::$SITEMAINTENANCE['startTime'])){
    Log::err("The \$SITEMAINTENANCE array in Dib.php must either be commented out, or a 'startTime'=>'yyyy-mm-dd H:i' array value must be provided .");
    DIB::$SITEMAINTENANCE['startTime'] = '2000-01-01';
}

// Allow specified IP addresses to continue with normal operations
if(!empty(DIB::$SITEMAINTENANCE['allowedIps']) && in_array($_SESSION['IPaddress'], DIB::$SITEMAINTENANCE['allowedIps']))
    return;

$now = Date('Y-m-d H:i');

if($now > DIB::$SITEMAINTENANCE['startTime']) {

    // Log the user out
    if(DIB::$USER['username'] <> 'system_public') {
        DibApp::load('dibAuthenticate', 'Authenticator.php', 'components');
        Authenticator::logout();
    }

    // Let the environment.js and /login and /logout requests through,
    // which will cause a redirect to the /login page, which will then display the SiteMaintenance page
    // Anything else gets 401 

    $last6 = substr($_SERVER['REQUEST_URI'], -6);
    if($last6 != '/login' && $last6 != 'logout' && (strtolower(DibApp::$url->callType) !== 'peff' || strtolower(DibApp::$url->controller) !== 'template')) {

        header('HTTP/1.0 401 Unauthorized', true, 401);

        if(array_key_exists('HTTP_REQUESTVERIFICATIONTOKEN', $_SERVER)) {
            //ClientFunctions::addAction($actionList, 'open-url', array('value'=>'/login'));
            $c = New Controller();
            $result = $c->invalidResult("The system is now down for scheduled maintenance, please visit later.", 'dialog');
            echo $result;
        }

        die();
    }

    return;
}

// See if the user has already been informed
if(!empty($_SESSION['siteMaintainMsg']))
    return;

// See if there is a 10 minute difference to site down time
$mins = (int)((strtotime(DIB::$SITEMAINTENANCE['startTime']) - time()) / 60);
if($mins > 10)
    return;

// Check whether this user has already seen the 10 minute warning msg
// Only display it on requests that handle dialog messages 
if(empty(DibApp::$url->callType) || empty(DibApp::$url->controller))
    return;

// Only return message on request types that definitely allow messages   //  || strtolower(DibApp::$url->controller) === 'api'
if(strtolower(DibApp::$url->callType) === 'peff' && (strtolower(DibApp::$url->controller) === 'crud')) {
    $maintainMsg = DIB::$SITEMAINTENANCE['warningMsg'] . '<br><br>Time remaining: ' . $mins . ' minutes';
    DibApp::setClientMsg($maintainMsg, 'dialog');
    
    session_start();
    $_SESSION['siteMaintainMsg'] = true;
    session_write_close();
}
