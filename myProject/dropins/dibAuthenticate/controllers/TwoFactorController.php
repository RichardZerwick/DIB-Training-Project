<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

class TwoFactorController extends Controller {
    
	protected function viewResult($fileName) {
		// Users can override the following files in their own /dibAuthenticate dropin:
		
		DibApp::load('dibAuthenticate', 'View.php', 'components');
		DibApp::load('dibAuthenticate', $fileName.'.php', 'views');
        // require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'View.php';        
		//require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$fileName.'.php';
	}
	        
	// Gets variables in posted data
    private function getPostedVars($vars) {
        $requestPayload = file_get_contents('php://input');
        
        if(is_null($requestPayload)) return null;
		
		$arr = array();
		parse_str($requestPayload, $arr);
		
		foreach($vars AS $v)
			if(!array_key_exists($v, $arr)) return null;
		
        return $arr;
	}
	
	/**
	 * Implementation of the twofactor service for dibAuthenticate user login
	 */
	function authenticate($password=null, $email1=null, $REQUEST_TYPE = "POST,GET,ignoretoken") {
		DibApp::load('dibAuthenticate', 'TwoFactorService.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');
/*
		if (empty($_SESSION['2fa_userId'])) {
			Log::setMsg("Timeout... please try again");
			return $this->viewResult('login');
		}
*/

		$twoFactorService = new TwoFactorService($_SESSION['2fa_userId'], 'dibAuthenticate');
		Log::$infoMsg = $twoFactorService->startAuthentication();

		// Read post data
		$result = $this->getPostedVars(array('password', 'email1','form_token'));
        
		if(empty($result) && $twoFactorService->isAuthenticated() == false) {
			session_write_close();
			return $this->viewResult('twofactor');
		}
        
		$password = $result['password'];
		$email1 = $result['email1'];
		
		$formToken = $result['form_token'];
		
		if (DIB::$USER['auth_id']!=$formToken  && $twoFactorService->isAuthenticated() == false) {
			Log::setMsg("Invalid request");
			return $this->viewResult('twofactor');
		}

		//Trap for hacking bots
        if($email1) return 'Invalid request';

		list($resultType, $result) = $twoFactorService->authenticate($password);

		if ($resultType == 'error') { 
			Log::setMsg($result);
			return $this->viewResult('twofactor');
		}
		if ($resultType == 'next') { 
			Log::$infoMsg = $result;
			return $this->viewResult('twofactor');
		}
		
		Authenticator::logUserIn($_SESSION['2fa_userId']);

		$login = DIB::$USER;
		//Ignore any actions that the twofactor service may take and just use the return url for this implementation

		if (empty(DIB::$RETURN_URL)) {
			if(isset($_SESSION['return_url'])) {
				// Handle user pasting a url eg. /nav/dibDashboard, but he is not logged-in yet... See Url.php
				DIB::$RETURN_URL = $_SESSION['return_url'];
				unset($_SESSION['return_url']);
			} else
				DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
			
			session_write_close();
		}		

		// DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
			
		// Prohibit someone from building urls
		if(substr(DIB::$RETURN_URL, 0, 7) !== 'http://' && substr(DIB::$RETURN_URL, 0, 8) !== 'https://') {
			Log::perms('Invalid RETURN_URL specified (not starting with http:// or https://', DIB::$RETURN_URL, '','');
			DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
		}
        // Open default / return url
		header("Location: ". DIB::$RETURN_URL);

		// Check if a $AFTERLOGINSCRIPT exists
		if(!empty(DIB::$AFTERLOGINSCRIPT)) require DIB::$BASEPATH . ltrim((string)DIB::$AFTERLOGINSCRIPT, '/');
	}
	/**
	 * Implementation of the twofactor service for dibAuthenticate user login
	 */
	function reset($password=null, $email1=null, $REQUEST_TYPE = "POST,GET,ignoretoken") {
		DibApp::load('dibAuthenticate', 'TwoFactorService.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');

		$twoFactorService = new TwoFactorService($_SESSION['2fa_userId'], 'dibAuthenticate');
		//Check if the two factor service that is running now is configured
		if ($twoFactorService->isConfigured() == false) { 
			header("Location: authenticate");
			die();
		}

		Log::$infoMsg = $twoFactorService->startReset();

		// Read post data
		$result = $this->getPostedVars(array('password', 'email1', 'form_token', 'confirmation', 'reset'));
		if(empty($result)) {
			//Trying without the reset 
			$result = $this->getPostedVars(array('password', 'email1', 'form_token', 'confirmation'));
		}
		
		if(empty($result)) {
			session_write_close();
			return $this->viewResult('twofactorreset');
		}
        
		$password = $result['password'];
		$email1 = $result['email1'];
		$confirmation = $result['confirmation'];
		$disabled = isset($result['reset'])? $result['reset'] : 'off';
		$enabled = true;
		if ($disabled=='on') $enabled = false;

		$formToken = $result['form_token'];
		
		if (DIB::$USER['auth_id']!=$formToken) {
			Log::setMsg("Invalid request");
			return $this->viewResult('twofactorreset');
		}

		//Trap for hacking bots
        if($email1) return 'Invalid request';

		list($resultType, $result) = $twoFactorService->reset($password,$confirmation,$enabled);

		if ($resultType == 'error') { 
			Log::setMsg($result);
			return $this->viewResult('twofactorreset');
		}
		if ($resultType == 'next') { 
			header("Location: authenticate");
			die();
		}
		
		Authenticator::logUserIn($_SESSION['2fa_userId']);

		$login = DIB::$USER;
		//Ignore any actions that the twofactor service may take and just use the return url for this implementation

		if (empty(DIB::$RETURN_URL)) {
			if(isset($_SESSION['return_url'])) {
				// Handle user pasting a url eg. /nav/dibDashboard, but he is not logged-in yet... See Url.php
				DIB::$RETURN_URL = $_SESSION['return_url'];
				unset($_SESSION['return_url']);
			} else
				DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
			
			session_write_close();
		}		

		// DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
			
		// Prohibit someone from building urls
		if(substr(DIB::$RETURN_URL, 0, 7) !== 'http://' && substr(DIB::$RETURN_URL, 0, 8) !== 'https://') {
			Log::perms('Invalid RETURN_URL specified (not starting with http:// or https://', DIB::$RETURN_URL, '','');
			DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
		}
        // Open default / return url
		header("Location: ". DIB::$RETURN_URL);

		// Check if a $AFTERLOGINSCRIPT exists
		if(!empty(DIB::$AFTERLOGINSCRIPT)) require DIB::$BASEPATH . ltrim((string)DIB::$AFTERLOGINSCRIPT, '/');

	}

	/**
	 * Implementation of the twofactor service for dibAuthenticate user login
	 */
	function adminRequest($password=null, $email1=null, $REQUEST_TYPE = "POST,GET,ignoretoken") {
		DibApp::load('dibAuthenticate', 'TwoFactorService.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');

		$twoFactorService = new TwoFactorService($_SESSION['2fa_userId'], 'dibAuthenticate');


		$rst = Database::fetch(TwoFactorConfig::ADMIN_EMAILS_RECOVERY_FIELD_SQL, array(':loginId'=>$_SESSION['2fa_userId']), DIB::LOGINDBINDEX);
        $template = TwoFactorConfig::ADMIN_EMAILS_RECOVERY_SUBJECT;
		foreach($rst as $field=>$value) {
			$value = (empty($value)) ? '' : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
			$template  = str_replace("~~$field", $value, $template);
		}
		DibApp::load('dibMail', 'DSendMail.php', 'components');
		$result = DSendMail::sendSmtp(
			TwoFactorConfig::ADMIN_EMAILS, 
			TwoFactorConfig::ADMIN_EMAILS_RECOVERY_SUBJECT,
			$template, NULL, NULL, 'info@dropinbase.com');
		
		Log::setMsg("Please note that an email was sent to the administrator.");
		return $this->viewResult('login');
	}
}
