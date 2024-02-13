<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

class Authenticator {
	
	/**
	* Verifies login credentials and creates a PHP user session on success
	* @param string $username
	* @param string $password
	* @param string $rememberMe
	* 
	* @return mixed the user's login record on success, FALSE on failure
	*/
	public static function login($username, $password, $rememberMe = false) {

        try {
            
            // Basic validation
            if(empty($username) || empty($password) || !is_string($username) || !is_string($password)){
                Log::setMsg('Invalid username or password. Please try again.'); // Set return msg to display on login screen
                return false;
            }

            // *** NOTE the error message is the same for all errors - the less info one gives hackers the better!
            
            // Check if username contains allowed and required chars set in Dib.php
            if(DibFunctions::validateRegex($username, DIB::$USERNAME_REGEX) == FALSE){
                Log::setMsg('Invalid username or password. Please try again.'); // Set return msg to display on login screen
                return false;
            }           
            
            // Avoid text that may contain urls like javascript:// or php:// or data://
            if(str_replace("://", '', $username) !== $username || str_replace("://", '', $password) !== $password){
                Log::setMsg('Invalid username or password. Please try again.'); // Set return msg to display on login screen
                return false;
            }
            
            // Find login record
            $sql = "SELECT l.id, l.password, l.login_lockout, l.login_attempts, l.login_blocked_count, l.password_expiry, l.default_url, l.pef_security_policy_id,
                           s.ip_address_range, s.name as policyName, s.retry_attempts, s.retry_retry_attempts, s.retry_lockout_time
                    FROM `pef_login` l 
                        INNER JOIN pef_security_policy s ON l.pef_security_policy_id = s.id 
                    WHERE `username`=:username OR `email`=:username";
            $login = Database::fetch($sql, array(':username'=>$username), DIB::LOGINDBINDEX);
            
            // *** NOTE, users that have expired permissions are initially logged in ... 
            //     The code in DibApp and PPermission will then check for any remaining groups where they may still have permissions. 
            //     If none are found they are logged in as system_public
            
            if($login === false) {
                Log::setMsg('System error. Please contact the System Administrator.');
                return false;
            }
            
            if(empty($login)) {
                Log::setMsg('Invalid username or password. Please try again.');
                //Log::Perms('Authentication', 'login', "Username does not exist: '$username' (password used: '$password')");
                return false;
            }
            
            $now = date('Y-m-d H:i:s', time());
            
            // Block automated scripts trying to login
            if(!empty($login['login_lockout']) && ($login['login_lockout'] > $now)) {
                Log::setMsg('Too many attempts... please wait 30 seconds and try again');
                return false;
            }
            
            // For older versions : < php 5.5 (but > 5.3.7)
            if (!function_exists('password_verify'))
                require DIB::$EXTPATH . 'PasswordCompat' . DIRECTORY_SEPARATOR . 'password.php';
            
            // Verify password. Lock potential automated scripts out for retry_lockout_time seconds after login_attempts unsuccessfull attempts
            // If user has been locked out in this way, more than retry_retry_attempts, then permanently block user
            if(!password_verify($password, $login['password'])) {
                $attempts = (int)$login['login_attempts'];
                if(is_null($attempts)) $attempts = 0;
                $attempts++;
                $attempMax = (int)$login['retry_attempts'];
                $lockout = null;
                $set = '';
                
                if($attempts >= $attempMax) {
                    $later = new DateTime();
                    $lockoutSeconds = (int)$login['retry_lockout_time'];
                    $later->add(date_interval_create_from_date_string("$lockoutSeconds seconds"));
                    $lockout = $later->format('Y-m-d H:i:s');
                    $blockedCount = (int)$login['login_blocked_count'];
                    $retryRetryAttempts = (int)$login['retry_retry_attempts'];

                    if($blockedCount < $retryRetryAttempts) {
                        Log::Perms('Authentication', 'login', "Password failed on attempt $attempMax for username: '$username'. Password: '$password'");
                        Log::setMsg("An incorrect password was entered $attempMax times. You will be locked out for a while before you may try again.");

                    } else {
                        $msg = "*** Password failed on attempt $attempMax x $retryRetryAttempts for username: '$username' from IP address: " . DibApp::getRealIpAddr() . " - This account was permanently blocked by changing the password!\r\nReset the field pef_login.login_blocked_count if needed. Check /runtime/logs/Perms.log for history.";
                        Log::err($msg);
                        Log::Perms('Authentication', 'login', $msg);
                        Log::setMsg('Sorry, your account has been blocked. Please contact support.');
                        $set .= ", password = '***BLOCKED - see login_blocked_count'";
                    }

                    $set .= ', login_blocked_count = login_blocked_count + 1';

                } else
                    Log::setMsg('Invalid username or password. Please try again.');

                $sql = "UPDATE `pef_login` SET `login_attempts`=:attempts, `login_lockout`=:lockout $set WHERE `id`=:id";
                $params = array(':attempts'=>$attempts, ':lockout'=>$lockout, ':id'=>$login['id']);
                Database::execute($sql, $params, DIB::LOGINDBINDEX);

                return false;
            }

            // Do security policy checks
            
            // IP address
            if(!empty($login['ip_address_range'])) {
                $result = self::checkIp($username, $login['ip_address_range']);
                if($result !== TRUE) {
                    Log::setMsg('This IP address is not allowed.'); 
                    Log::Perms('Authentication', 'login', $result);
                    return false;
                }
            }
            
            // Password expiry
            if(!empty($login['password_expiry']) && ($login['password_expiry'] < $now)) {
                Log::setMsg("Your password has expired. Please use the 'Forgotten Password' link to change your password.");					
                return false;
            }
            
            // Check if any twofactor authentication is enabled for this user.
            DibApp::load('dibAuthenticate', 'UserTwoFactor.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');
            $twoFactor = new UserTwoFactor();
            
            if ($twoFactor->checkTwoFactor($login['id'])) {
                header("Location: ". DIB::$BASEURL . '/dropins/dibAuthenticate/TwoFactor/authenticate');
                die();
            }
            
            // Store the remember me state 
            if (!empty($rememberMe) && !empty(DIB::$ENABLE_REMEMBERME)) { 
                self::configureRememberMe($login);
            } else {
                // Clear the cookies
                setcookie("dibRandomPassword", "", 1, '/', $_SERVER['SERVER_NAME']);
                setcookie("dibRandomSelector", "", 1, '/', $_SERVER['SERVER_NAME']);
                setcookie("dibRememberLogin", "", 1, '/', $_SERVER['SERVER_NAME']);
            }
            
            // No twofactor auth is enabled, and credentials are valid, so log user in and set session
            self::logUserIn($login['id']);
            
            return $login;

        } catch (Exception $e) {
            Log::setMsg('Sorry the system is not available at present. Please try again later.');
            Log::err('*** WARNING! The login function code is not working. Please check /runtime/error.log and PHP error logs for details.');
            return false;
        }
    }

    /**
	* Creates a PHP session for the user, and sets global variables in DIB
	*
	* @param int $loginId used as key to get session args from pef_login - overrides $sessionArgs parameter
    * @param boolean $updateAuthId update the security token that is passed by the angular code
	* @param boolean $unitTestUser whether this is a special login, used for unit testing (pef_login.test_user=1)
	
	* @return boolean TRUE/FALSE
	*/
	public static function logUserIn ($loginId=null, $updateAuthId=true, $unitTestUser=FALSE) {
        try {
            if(empty($loginId) || $loginId !=(string)(int)$loginId){
                Log::err("*** Security warning. Non integer \$loginId value passed to logUserIn() function.");
                die();
            }

            if(!empty(DIB::$CONFIGSPATH) && file_exists(DIB::$CONFIGSPATH . 'DibUserParams.php')) {
                include_once(DIB::$CONFIGSPATH . 'DibUserParams.php');
                $sessionArgs = DibUserParams::getUserParams($loginId);

            } elseif(file_exists(DIB::$BASEPATH . 'configs' . DIRECTORY_SEPARATOR . 'DibUserParams.php')) {
                include_once(DIB::$BASEPATH . 'configs' . DIRECTORY_SEPARATOR . 'DibUserParams.php');
                $sessionArgs = DibUserParams::getUserParams($loginId);

            } else {
                // Basic fields required by DIB, and useful fields. 
                // All these fields are stored in the PHP user session, and are available in query parameters etc.
                // Override this code with the following class (see code above):  /configs/DibUserParams.php -> getUserParams($login)
                $sql = "SELECT l.id, l.username, l.perm_group, l.admin_user, l.email, l.test_user, l.default_url, l.larger_font,
                            l.pef_security_policy_id, s.name as policyName, l.login_expiry, l.login_group_expiry, l.first_name, 
                            l.last_name, l.language, l.supplier_code, l.dib_username
                        FROM `pef_login` l 
                            INNER JOIN pef_security_policy s ON l.pef_security_policy_id = s.id 
                        WHERE l.id = $loginId";

                $sessionArgs = Database::fetch($sql, null, DIB::LOGINDBINDEX);
            }

            if($sessionArgs === FALSE) {
                Log::err("*** Security warning. The login query failed to execute. Please see previous errors logged.",10);
                die();

            } elseif(empty($sessionArgs)){
                Log::err("*** Security warning. Attempt to login as a user that does not exist, while bypassing password and other security checks, using Login Id $loginId");
                die();

            } elseif(is_string($sessionArgs)) {
                // Display error to user
                Log::setMsg($sessionArgs);
                return false;
            }

            // Set auth_id
            if ($updateAuthId || !isset($_SESSION['auth'])) {
                // Generate cryptographically strong 16 character string
                $sessionArgs['auth_id'] = DibApp::randomToken(16); 
                $sessionArgs['unique_id'] = DibApp::randomToken(12); 
            } else { 
                $sessionArgs['auth_id'] = $_SESSION['auth']['auth_id'];
                $sessionArgs['unique_id'] = $_SESSION['auth']['unique_id'];

                // If you login and you don't want to update the secure Id it means you have remember me activated, so we need to update the remember me information
                if(!empty(DIB::$ENABLE_REMEMBERME)) self::configureRememberMe($sessionArgs);
            }
            
            // Update the client value
            DibApp::$setAuthIdOnClient = TRUE;

            if($sessionArgs['auth_id'] === FALSE) {
                // Could not generate secure string. Log an error and warn user. 
                Log::err("Attempt to generate a cryptographically secure string for the user failed. Please upgrade the PHP installation.");
                die();
            }

            DIB::$USER = $sessionArgs;
            if(!$unitTestUser) 
                DibApp::regenerateSession(TRUE, TRUE);

            elseif((int)$sessionArgs['test_user'] !== 1) {
                Log::err("Calling login method failed for user where 'test_user'<>1");
                return FALSE;
            }
            
            DIB::$PERMGROUP = $sessionArgs['perm_group'];
            
            // Update last login date and store in Audit Trail table
            $now = date('Y-m-d H:i:s', time());

            $params = array(
                '!id' => $sessionArgs['id'],
                'last_login_datetime' => date('Y-m-d H:i:s', time()),
                'login_attempts' => NULL,
                'login_lockout' => NULL,
                'password_expiry' => NULL
            );

            $rst = Database::update('pef_login', $params, DIB::LOGINDBINDEX, "id = :id", 'detail');
            if($rst === FALSE) 
                Log::err("Could not update login record!", 5);
            
            return TRUE;

        } catch (Exception $e) {
            Log::err('*** WARNING! The logUserIn function code is not working. Please check /runtime/error.log and PHP error logs for details.');
            return FALSE;
        }
	}
    
    private static function configureRememberMe($login) {
        if(empty(DIB::$ENABLE_REMEMBERME)) return TRUE;
        
        // Get Current date, time
        $currentTime = time();
        $currentDate = date("Y-m-d H:i:s", $currentTime);
        
        $daysExpiry = Database::fetch("SELECT expiry_day_count_remember_me as dayCount FROM pef_security_policy  WHERE id=:pef_security_policy_id ", array(
            ":pef_security_policy_id" =>$login['pef_security_policy_id']
        ), DIB::LOGINDBINDEX);

        // Set Cookie expiration for 1 month
        $cookieExpirationTime = $currentTime + (intval($daysExpiry['dayCount']) * 24 * 60 * 60);  // for 1 month

        $randomPassword = DibApp::randomToken(16);
        setcookie("dibRandomPassword", $randomPassword, $cookieExpirationTime, '/', $_SERVER['SERVER_NAME'], true, true, 'Strict');

        $randomSelector = DibApp::randomToken(32);
        setcookie("dibRandomSelector", $randomSelector, $cookieExpirationTime, '/', $_SERVER['SERVER_NAME'], true, true, 'Strict');
        
        setcookie("dibRememberLogin", $login['id'], $cookieExpirationTime, '/', $_SERVER['SERVER_NAME'], true, true, 'Strict');

        $randomPasswordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        $randomSelectorHash = password_hash($randomSelector, PASSWORD_DEFAULT);

        $expiryDate = date("Y-m-d H:i:s", $cookieExpirationTime);

        // Insert new token
        Database::execute("UPDATE pef_login 
                           SET  password_remember_expiry=:expiry_date, password_remember=:password_hash, password_remember_hash=:selector_hash
                           WHERE id=:login_id",array(':login_id' => $login['id'],
                                    ":password_hash" => $randomPasswordHash,
                                    ":selector_hash" => $randomSelectorHash,
                                    ":expiry_date" => $expiryDate), 
                         DIB::LOGINDBINDEX
        );
    }
	
	/**
	* Sends email and prepares user's login record to handle changing of their password
	* @param string $username
	* 
	* @return TRUE on success, FALSE on error, if any validation fails, or mail could not be sent
	*/
	public static function forgotpassword($username = null) {
    	try {
            if(empty($username))
                return false;
            
            if(!is_string($username) || strlen($username)>120)  {
                Log::setMsg("Please provide a valid username/email and try again.");
                return FALSE;        
            }
            
            // Check if username contains allowed and required chars
            // *** Can't do this since we're allowing email as username now too
            /*if(DibFunctions::validateRegex($username, DIB::$USERNAME_REGEX) == FALSE) {
                Log::setMsg("Please provide a valid username/email and try again.");
                return FALSE; 
            }*/
            
            $sql = "SELECT `id`, `email`, `first_name`, `last_name`, reset_expiry 
                    FROM pef_login WHERE `username`=:username OR `email`=:username";
            $login = Database::fetch($sql, array(':username' => $username), DIB::LOGINDBINDEX);
            
            if (Database::count()===0 || $login === FALSE) {
                // username doesn't exist... try again with a blank form
                Log::setMsg("Please provide a valid username/email and try again.");
                return FALSE;
            }
            
            // Check if a mail was already sent recently
            if($login['reset_expiry'] > date("Y-m-d H:i:s", strtotime('+20 minutes'))){
                Log::setMsg("An e-mail with instructions to reset the password has already been sent in the past 20 minutes. Please check your inbox and spam folders. This facility can be used to send a new email only every 20 minutes.");
                return FALSE;
            }  
            
            // Check if email address exists
            if (!$login['email']) {
                Log::setMsg("This username's email address is not stored on the system. Please contact the System Administrator for assistance.");
                return FALSE;
            }
            
            //Generate random key to allow user to reset his password
            $key = DibApp::randomToken(32);
            if($key === FALSE) {
                Log::err("WARNING! Could not generate a random token; the PHP random_bytes function exists but does not work. Please repair your PHP installation: " . $e->getMessage());
                Log::setMsg("System Error! Please contact the System Administrator for further assistance.");
                return FALSE;
            }
            
            $expiry = date("Y-m-d H:i:s", strtotime('+20 minutes'));
            
            $sql = "UPDATE pef_login SET reset_guid=:key, reset_expiry=:resetDate WHERE `id` = :id";
            $result = Database::execute($sql, array(':key'=>$key, ':resetDate'=>$expiry, ':id'=>$login['id']), DIB::LOGINDBINDEX);
            
            // Create email with url  ***TODO implement email template...
            $url = DIB::$BASEURL . '/dropins/dibAuthenticate/Site/resetpassword/'.$key;         
            $body = 'Dear ' . $login['first_name'] . ' ' . $login['last_name'] . "<br><br>Your request to reset the login account's password was received.<br>Use the following url to open a form where you can provide a new password:<br><br> <a href='$url'>$url</a>. <br><br> Thanks<br><br> System Admin";
            $plainText = 'Dear ' . $login['first_name'] . ' ' . $login['last_name'] . "\r\n\r\n Your request to reset the login account's password was received. \r\nUse the following url to open a form where you can provide a new password: \r\n\r\n$url.\r\n\r\nThank you \r\n System Admin";
            
            //Send email
            DibApp::load('dibMail', 'DSendMail.php', 'components');

            $result = DSendMail::sendSmtp($login['email'], 'Confirm Reset Password', $body, null, null, $plainText);
            
            if($result !== TRUE) {
                Log::err("Could not send reset-password email for user '$username' to mail address '" . $login['email'] . "'. Please check credentials in /config/Mail.php. Error returned: " . $result);
                Log::setMsg("The system encountered an error when trying to send the email. Please try again, or contact the System Administrator for assistance.");

            } else {
                // Send onscreen message to user
                Log::setMsg("An email was sent to the user's registered email address. Please click the link contained therein to complete the process of resetting the password.");
            }
            
            return TRUE;

        } catch (Exception $e) {
            Log::setMsg('Apologies, this function is not available at present. Please try again later.');
            Log::err('*** WARNING! The forgot password function code is not working. Please check /runtime/error.log and PHP error logs for details.');
            return false;
        }
    }
    
    /**
	* Facility to reset user's password
	* @param string $key
	* @param string $password
	* @param string $password2 user is requested to retype a matching password
	* @param boolean $keyIsLoginId whether $key is the pef_login.id value OR not (ie the reset_GUID)
	* 
	* @return TRUE on success, string error or validation failure
	*/
    public static function resetpassword($key = null, $password = null, $password2 = null, $keyIsLoginId = FALSE) {
        try {
            // Basic validation
            if(empty($key) || ($keyIsLoginId === FALSE && (strlen($key)<32 || strlen($key)>64 || !is_string($key))))
                return "Invalid request received to change the password. Perhaps the url used was incorrect. Please try again.";

            if(empty($password2) || !is_string($password2))
                return "Please provide values for both 'New Password' and 'Confirm Password' and try again.";
            
            if($password !== $password2)
                return "The two passwords must match. Please try again.";
            
            $now = date('Y-m-d H:i:s', time());
            $sql = "SELECT l.`id`, l.`username`, s.password_regex, s.password_regex_error_message, s.cant_use_pwd_count, l.password_history,
                        s.expiry_day_count, s.ip_address_range, s.name AS policyName, l.password
                    FROM pef_login l LEFT JOIN pef_security_policy s ON l.pef_security_policy_id = s.id
                    WHERE ";        
            $sql .= ($keyIsLoginId===TRUE) ? "l.id = :key" : "reset_guid = :key AND reset_expiry > '$now'";
            
            $login = Database::fetch($sql, array(':key'=>$key), DIB::LOGINDBINDEX);
            
            if(Database::count()===0 || $login === FALSE)
                return "The system received an invalid or expired request to reset a password. You probably waited longer than 20 minutes to reset the password. Please return to the login page to try again.";
            
            // Detailed validation
            $result = self::isValidPassword($login['username'], $password, FALSE);

            if($result !== TRUE) 
                return $result;

            $pwdHash = self::encryptPassword($password);

            $params = array(':pwd'=>$pwdHash, ':id'=>$login['id']);

            // Handle whether new password has been used before
            $set = '';
            $pwdCount = (int)$login['cant_use_pwd_count'];

            if(!empty($pwdCount)) {
                // Manage list of historical passwords 
                $pwdStr = $login['password_history'];

                $pwdStr = $pwdHash . ';' . $pwdStr;
                $pwds = explode(';', $pwdStr);
                if(count($pwds) > $pwdCount)
                    $last = array_pop($pwds);
                
                $pwdStr = implode(';', $pwds);
                $params[':pwdHist'] = $pwdStr;
                $set .= ', password_history = :pwdHist';
            }

            // Handle password expiry day count
            if($login['expiry_day_count'] && $login['expiry_day_count'] > 0) {
                $expiry = date("Y-m-d H:i:s", strtotime('+' . $login['expiry_day_count'] . ' days'));
                $params[':expiry'] = $expiry;
                $set = ', password_expiry = :expiry';
            }
            
            $sql = "UPDATE pef_login SET `password` = :pwd, reset_guid = null, reset_expiry = null $set WHERE id = :id";
            $result = Database::execute($sql, $params, DIB::LOGINDBINDEX);
            
            //Notify user and log them in automatically
            if($result !== FALSE) 
                return TRUE;
            
            return "A system error occured while resetting the password. Please contact the System Administrator.";

        } catch (Exception $e) {
            Log::err('*** WARNING! The reset password function code is not working. Please check /runtime/error.log and PHP error logs for details.');
            return 'Apologies, this function is not available at present. Please try again later.';
        }
    }

    public static function confirmPassword($userInput,$passwordHash) {
        if (!function_exists('password_verify'))
            require DIB::$EXTPATH . 'PasswordCompat' . DIRECTORY_SEPARATOR . 'password.php';
    
    // Verify password. Lock potential automated scripts out for 30 seconds after 3 unsuccessfull attempts
        return password_verify($userInput,$passwordHash);
    }
    
	public static function encryptPassword($password) {        
        // For older versions : < php 5.5 (but > 5.3.7)
		if (!function_exists('password_hash'))
			require DIB::$EXTPATH . 'PasswordCompat' . DIRECTORY_SEPARATOR . 'password.php';
		
		// Encrypt password for storing
       return password_hash($password, PASSWORD_DEFAULT);
    }
    
	/**
	* Log user out and destroy session
	* 
	* @return true
	*/
	public static function logout() {
    	try {
	    	session_start();
			// resets the session data for the rest of the runtime
			$_SESSION = array();
			// sends as Set-Cookie to invalidate the session cookie
			if (isset($_COOKIE[session_name()])) { 
			    $params = session_get_cookie_params();
			    setcookie(session_name(), '', 1, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
            }

            if(isset($_COOKIE["dibRememberLogin"])) {
                $sql = "UPDATE pef_login SET  password_remember_expiry=null, password_remember=null, password_remember_expiry=null 
                        WHERE id=:loginId";
                Database::execute($sql, array(':loginId' => $_COOKIE["dibRememberLogin"]), DIB::LOGINDBINDEX);
            }

            // Clear remember me cookies
            setcookie("dibRandomPassword", '', 1, '/', $_SERVER['SERVER_NAME']);
            setcookie("dibRandomSelector", '', 1, '/', $_SERVER['SERVER_NAME']);
            setcookie("dibRememberLogin", '', 1, '/', $_SERVER['SERVER_NAME']);

			session_unset();
            session_destroy();

	        return true;
	        
        } catch (Exception $e) {
            Log::fatal("*** WARNING! Could not log user '" . DIB::$USER['username'] . "' out successfully. PHP error details: ". $e->getMessage());
            return false;
        }
    }   
    
    /**
	* Checks user's IP address against a whitelist
	* @param string $username
	* @param string $ipAddressRange semicolon delimited list of valid IP addresses (can contain '*' as wild-card, and '-' to indicate a range)
    * @param string $policyName name field in pef_security_policy (used for error reporting only and not required)
	* 
	* @return mixed TRUE on success, string with error message for admin user on failure
	*/
	private static function checkIp($username, $ipAddressRange, $policyName='') {
		if(empty($ipAddressRange))
			return TRUE;
			
		$ip = DibApp::getRealIpAddr();
		$myIp = explode('.', $ip);
		$ranges = explode(';', $ipAddressRange);
		
		$valid = false;
		foreach($ranges as $range) {
			$theirIp = explode('.', $range);
			if(count($theirIp) != count($myIp)) continue;
			$validIp = true;
			for($i=0; $i<count($myIp); $i++) {
				$validIp &= self::isValidIPGroup($myIp[$i], $theirIp[$i]);
			}
			$valid |= $validIp; 
		}
		
		if($valid === FALSE) 
			return "IP address '" . $ip . "' failed login policy '$policyName' for username: '$username'";
		
		return TRUE;
	}

	private static function isValidIPGroup($myIpGroup, $theirIpGroup) {
		if($theirIpGroup === '*') return true;
		if($theirIpGroup === $myIpGroup) return true;
		if(($start = strpos($theirIpGroup, '[')) !== false && ($end = strpos($theirIpGroup, ']')) !== false) {
			$range = explode('-', substr($theirIpGroup, $start+1, $end-1));
			if($myIpGroup >= $range[0] && $myIpGroup <= $range[1]) return true;
		}
		return false;
	}	
	
    /**
	* Checks password against security policy (called from eg setSencha/dibAdmin/PermissionsController.php)
	* @param string $username
	* @param string $password
	* 
	* @return mixed boolean TRUE on success, or string with user error message on failure
	*/
    public static function isValidPassword($username, $password, $setLogMsg=FALSE) {
        $sql = "SELECT *, s.name as policyName
                FROM pef_security_policy s
                	INNER JOIN pef_login l ON l.pef_security_policy_id = s.`id`
                WHERE l.`username` = :name";
        $policy = Database::fetch($sql, array(':name' => $username), DIB::LOGINDBINDEX);
        
        if(Database::count() ===0 || $policy === false) {
            if($setLogMsg) Log::setMsg("System error... please contact the System Administrator.");
            Log::err("Security policy for user $username not found. The following query returned no records, executing on database id " . 
            		 DIB::LOGINDBINDEX . " :\r\n" . $sql);
            return "System error... please contact the System Administrator.";
        }
		
        if(empty($password) || !is_string($password) || strlen($password)>50){
            $msg = "Please provide a valid password of less than 50 characters, and try again.";
            if($setLogMsg) Log::setMsg($msg);
            return $msg;
        }
        
        if(strpos($password, "://") !== FALSE) { // avoid passwords that may contain urls like javascript:// or php:// or data://
            $msg = "Invalid password, please try again.";
            if($setLogMsg) Log::setMsg($msg);
            return $msg;
        }
        
        // Handle IP address validation
        if(!self::checkIp($username, $policy['ip_address_range'], $policy['policyName'])){
            $msg =  "This request is not allowed. Please contact the System Administrator for more info.";
            if($setLogMsg) Log::setMsg($msg);
            Log::Perms('Authentication', 'login', "A request to reset a password was received from an invalid IP address. IP: " . 
            			DibApp::getRealIpAddr() . ", username: " . $username);
            return $msg;
        }
        
        if(DibFunctions::validateRegex($password, $policy['password_regex']) === FALSE) {
            $msg = ($policy['password_regex_error_message']) ? $policy['password_regex_error_message'] : 'The password is not secure enough. Please try again.';
			if($setLogMsg) Log::setMsg($msg);
            return $msg;
        }

        // Handle whether new password has been used before
        $pwdHash = self::encryptPassword($password);

        $pwdCount = $policy['cant_use_pwd_count'];
        if(!empty($pwdCount)) {

            $pwdStr = $login['password_history'];

            if(!empty($pwdStr)) {
                $pwds = explode(';', $pwdStr);
                foreach($pwds as $hash) {
                    if(password_verify($password, $hash)){
                        $msg =  "The last $pwdCount passwords used before are not allowed. Please try again.";
                        if($setLogMsg) Log::setMsg($msg);
                        return $msg;
                    }
                }
            }
        }
        
        return true;
    }
   
    public static function loginTestUser($testUserId) {
        if(DIB::$USER['admin_user'] != 1 || DIB::$ENVIRONMENT !== 'development') {
            Log::err("WARNING! Attempt blocked to login as test user from an account that is not an administrator (pef_login.admin_user), or ENVIRONMENT that is not 'development' (Dib.php)");
            die();
        }

        if($testUserId != (string)(int)$testUserId){
            Log::err("WARNING! Attempt blocked to login as test user with an invalid pef_login.id value: " . $testUserId);
            die();
        }

        $sql = "SELECT test_user FROM pef_login WHERE id = :id";
        $rst = Database::fetch($sql, array(':id'=>$testUserId), DIB::LOGINDBINDEX);

        if(empty($rst) || $rst['test_user'] != 1){
            Log::err("WARNING! Attempt blocked to login as test user with id '$testUserId' (check that pef_login.test_user=1 for this record)");
            die();
        }

        $result = self::logUserIn($testUserId, true, true);
        
        if($result === FALSE) {
            Log::err('Invalid test user id: ' . $testUserId);
            die();
        }
        
    }			
}
