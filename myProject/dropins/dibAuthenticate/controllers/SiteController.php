<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/


class SiteController extends Controller {

	private $apiTokens = array();
    
	protected function viewResult($fileName) {
        // Handle site maintenance coverPage
        if(!empty(DIB::$SITEMAINTENANCE['startTime'])){

            $now = Date('Y-m-d H:i');

            // Check if site maintenance start time has arrived
            if($now > DIB::$SITEMAINTENANCE['startTime']) {

                // Allow specified IP addresses to login 
                if(empty(DIB::$SITEMAINTENANCE['allowedIps']) || !in_array($_SESSION['IPaddress'], DIB::$SITEMAINTENANCE['allowedIps'])) {
                    
                    // Display coverPage HTML:
                    if(empty(DIB::$SITEMAINTENANCE['coverPage'])) {
                        $content = "<h2>Scheduled site maintenance in progress.... Come back soon :)</h2>";
                        echo $content;
                    } else 
                        DibApp::load('dibAuthenticate', DIB::$SITEMAINTENANCE['coverPage'], 'views');
                    
                    die();
                }
            }
        }

		// Developers can override the following files in their own /dibAuthenticate dropin:
		DibApp::load('dibAuthenticate', 'View.php', 'components');
		DibApp::load('dibAuthenticate', $fileName.'.php', 'views');
	}

	// For sending values to API requests
	private function apiLoginResult($status, $requestVerificationToken = null) {
        $array = json_encode(array('status' => $status, 'RequestVerificationToken' => $requestVerificationToken));
        header('Content-Type: application/json; charset=utf-8');

        echo $array;
        return null;
    }
	
	// Gets variables in posted data
    private function getPostedVars($vars) {
        $requestPayload = file_get_contents('php://input');

        if(is_null($requestPayload)) return null; 
		
		$arr = array();
		parse_str($requestPayload, $arr);
		
		foreach($vars AS $v) {
			if(!array_key_exists($v, $arr)) {
				$str = (is_array($requestPayload)) ? json_encode($requestPayload) : $requestPayload;
				if(!empty($str) && $v !== 'rememberMe')
					Log::perms('Authentication', null, null, "Expected POST parameter '$v' but did not get it. All params received: $str");
				return null;
			}
		}
		
        return $arr;
	}

	function login($username=null, $password=null, $email1=null, $api=0, $form_token=null, $msg='', $REQUEST_TYPE="POST,GET,ignoretoken") {
	
		if (!empty($msg))
			Log::setMsg($msg);

		if ($api != 1) {
			// Read post data - all these parameters must exist in POST data from HTML form, else $result will be null
			$result = $this->getPostedVars(array('username', 'password', 'email1', 'form_token'));
		
			if (empty($result))
				return $this->viewResult('login');
			
			if(!empty(DIB::$ENABLE_REMEMBERME)) {
				$rememberMe = $this->getPostedVars(array('rememberMe'));
				if(empty($rememberMe) || !array_key_exists('rememberMe', $rememberMe)) $rememberMe['rememberMe'] = null;
			} else
				$rememberMe['rememberMe'] = null;
	
			$username = $result['username'];
			$password = $result['password'];
			$rememberMe = $rememberMe['rememberMe'];
			$email1 = $result['email1'];
			$formToken = $result['form_token'];
			$api = (int)$api;

			//Trap for hacking bots
			if($email1) return 'Invalid request';

			if (DIB::$USER['auth_id'] != $formToken) {
				/*
					The value of DIB::$USER['auth_id'] will change when a public user's PHP session expires.
					Some users have however reported other issues with this check. Keep an eye on it and disable if necessary.
					We'd like to avoid updating a session in the file system with every request...
					https://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
				*/
	
				Log::setMsg("Login page expired. Please refresh the page and try again.");
				return $this->viewResult('login');
			}

			if (empty($username) && empty($password))
            	return $this->viewResult('login');

		} else {
			// This is only for API calls via eg CURL
			// *** Ignore the following if you do not have external applications making API calls to this DIB installation

			// If session is still active, return details
			if(DIB::$USER['username'] !== 'system_public')
				return $this->apiLoginResult('success', DIB::$USER['auth_id']);

			if(empty($username) || empty($password) || empty($form_token) || !empty($email1))
				return $this->apiLoginResult('failed');

			// API users must have fixed or arranged form_token values.
			// Tokens must not be stored in a repository. Store securely on the server
			// Below we use a file in the /configs folder

			if(!empty(DIB::$CONFIGSPATH) && file_exists(DIB::$CONFIGSPATH . 'ApiTokens.php'))
				require DIB::$CONFIGSPATH . 'ApiTokens.php';
			elseif (file_exists(DIB::$BASEPATH . 'configs' . DIRECTORY_SEPARATOR . 'ApiTokens.php'))
				require DIB::$BASEPATH . 'configs' . DIRECTORY_SEPARATOR . 'ApiTokens.php';
			else {
				Log::err('An api request was made to this DIB installation, but could not find the ApiTokens.php file.');
				return $this->apiLoginResult('failed');
			}

			if(!in_array($formToken, $this->apiTokens)) {
				Log::perms('API request failed Authentication stage. ', null, null, "Provided form_token does not exist in the ApiTokens.php file");
				return $this->apiLoginResult('failed');
			}

			$rememberMe = null;

			$login = Authenticator::login($username, $password, $rememberMe);

			if($login == false || !isset($login['id']))
				return $this->apiLoginResult('failed');

			return $this->apiLoginResult('success', DIB::$USER['auth_id']);
		}
        
        $login = Authenticator::login($username, $password, $rememberMe);

		if($login == false || !isset($login['id']))
            return $this->viewResult('login');
		
		// Return to URL (if any) that the user may have requested before session expired
		if (empty(DIB::$RETURN_URL)) {
			session_start();
			if(isset($_SESSION['return_url'])) {
				// Handle user pasting a url eg. /nav/dibDashboard, but he is not logged-in yet... See Url.php
				DIB::$RETURN_URL = $_SESSION['return_url'];
				unset($_SESSION['return_url']);
			} else
				DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
			
			session_write_close();
		}

		// Prohibit issues from users building urls
		if(substr(DIB::$RETURN_URL, 0, 7) !== 'http://' && substr(DIB::$RETURN_URL, 0, 8) !== 'https://') {
			Log::perms('Invalid RETURN_URL specified (not starting with http:// or https://', DIB::$RETURN_URL, '','');
			DIB::$RETURN_URL = DIB::$BASEURL . str_ireplace(DIB::$BASEURL, '', $login['default_url']);
		}

        // Open default / return url
		header("Location: ". DIB::$RETURN_URL);

		// Check if a $AFTERLOGINSCRIPT exists
		if(!empty(DIB::$AFTERLOGINSCRIPT)) require DIB::$BASEPATH . ltrim((string)DIB::$AFTERLOGINSCRIPT, '/');
	}
	
    function forgotpassword($username=null, $email1=null, $REQUEST_TYPE = "POST,GET,ignoretoken") {
    	// Read post data
		$result = $this->getPostedVars(array('username', 'email1','form_token'));
	
		if(empty($result)) 
			return $this->viewResult('forgotpassword');
	
		$username = $result['username'];
		$email1 = $result['email1'];
		$formToken = $result['form_token'];
    	
    	//Trap for hacking bots
		if($email1) return $this->viewResult('login');
		
		if (DIB::$USER['auth_id']!=$formToken) {
			Log::setMsg("Invalid request");
			return $this->viewResult('forgotpassword');
		}
        
        $result = Authenticator::forgotpassword($username);
		return $this->viewResult('forgotpassword');
    }
    
    function resetpassword($REQUEST_TYPE = "POST,GET,ignoretoken") {
        // Read post data
		$result = $this->getPostedVars(array('password', 'password2', 'key', 'email1','form_token'));
	
		if(empty($result)) 
			return $this->viewResult('resetpassword');

		$password = $result['password'];
		$password2 = $result['password2'];
		$key = $result['key'];
        $email1 = $result['email1'];
		$formToken = $result['form_token'];
        
		if (DIB::$USER['auth_id']!=$formToken) {
			Log::setMsg("Invalid request");
			return $this->viewResult('resetpassword');
		}
        //Trap for hacking bots
        if($email1) 
        	return $this->viewResult('login');
	
		// Basic validation
        if(!$key || !is_string($key) || strlen($key)<32 || strlen($key)>64) {
            Log::setMsg("Invalid request received to change the password. Redirected to Login page.");
            return $this->viewResult('login');
        }
        
        $result = Authenticator::resetpassword($key, $password, $password2, FALSE);

        if($result === TRUE) {
        	Log::setMsg("The password was successfully updated.");
        	return $this->viewResult('login');       
        
        } else {
        	Log::setMsg($result);
        	$_SERVER['REQUEST_URI'] = $key; // Need to feed $key value in resetpassword.php
			return $this->viewResult("resetpassword");      
		}	
	}	
    
    function logout($REQUEST_TYPE = "POST,GET,ignoretoken") {
        Authenticator::logout();
        header("Location: /login", true, 307);
    }

}


