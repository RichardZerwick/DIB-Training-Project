<?php 
/**
 * This service wraps all the functionality with regards to 
 * stepping through the two factor authentication services 
 * that is required to pass a certain test before the user 
 * can finalize a specific user journey
 * 
 * Typical usage for the develop would be as follows
 * !Initialising the component in your login function for example
 * $twoFactorService = new TwoFactorService('userId','myCustomAuth',listActions);
 * $twoFactorService->add(new SecretAnswer());
 * $twoFactorService->add(new SMS2FA());
 * $twoFactorService->end();
 * 
 * !In your authenticate function where the user gets authenticated successfully 
 * !you need to add the following the user is technically not yet logged in
 * $twoFactorService = new TwoFactorService('userId','myCustomAuth');
 * $twoFactorService->startAuthentication(); -> show this response to the user client side for more info with an input box
 * 
 * !When you receive a response from the user for the above
 * $twoFactorService = new TwoFactorService('userId''myCustomAuth');
 * $result = $twoFactorService->authenticate($userInput); -> @this returns an array you need to validate it as follows
 * array('success',actions) //User is now authenticated and execute the actions initially given
 * array('error',message) //Show the user the error that his authentication did not match our 2FA text
 * array('next',message) //Starts the next 2FA for the user, change the information client side
 */
class TwoFactorService { 
    /**
     * Used to give the user more information
     */
    public static $infoMessage;
    /**
     * Used to give the label
     */
    public static $inputLabel;
    public static $forgotLinkText;
    public static $currentTitle;
    public static $resetLabel;
    public static $resetConfirmLabel;
    
    const SESSION_REFERENCE_NAME = 'DIB2FA';
    private $userId=0;
    /**
     * When creating the two factor service it is important to give 
     * it a service name that you can reference when you reuse the functionality 
     * later on
     * The list of actions is what the browser needs to excecute when successfull
     */
    function __construct($userId, $instanceName, $actions = null) {
        $this->userId = $userId;    
        $this->instanceName=$instanceName; 
        $this->start($actions); 

        $configLocation = DIB::$BASEPATH."configs".DIRECTORY_SEPARATOR."TwoFactorConfig.php";

        if(file_exists($configLocation))
           include_once($configLocation);

        // Write the config file for the user if the user don't have the click a tell config file
        if (!class_exists("TwoFactorConfig")) {
            $configFile = fopen($configLocation, "w") or die("Unable to open $configLocation!");
            // We know we have click a tell as the default provider in DIB so we initialize the config file
            $txt = '<?php 
            class TwoFactorConfig { 
                // Add comma seperated emails
                const ADMIN_EMAILS = "admin@example.com,admin2@example.com";

                // Recovery subject
                const ADMIN_EMAILS_RECOVERY_SUBJECT = "System Name: Reset my two factor";

                // Recovery template
                const ADMIN_EMAILS_RECOVERY_BODY = "Dear Admin, Please note ~~first_name ~~last_name
                would like to reset their two factor authentication. Please contact them urgently. (~~mobile) (~~email)<br><br>
                Regards
                Your system";

                // Recovery template sql 
                const ADMIN_EMAILS_RECOVERY_FIELD_SQL = "SELECT first_name, last_name, mobile, email FROM pef_login WHERE id = :loginId";

                // Recovery admin email sent message
                const ADMIN_EMAIL_SENT_MSG = "Please note that an email was sent to the administrator.";

                // Alternatively specify another Controller to handle the reset class
                const ADMIN_RECOVERY_LINK = "/dropins/dibAuthenticate/TwoFactor/adminRequest";

                // Allow users to disable their two factor method when they go through a reset of the two factor authentication
                const ALLOW_USER_TO_DISABLE_DURING_FORGOT_PROCESS = true;
            }
            ?>';
            fwrite($configFile, $txt);
            include_once($configLocation);
        }
    }

    /**
     * Ensure there is a reference
     */
    private function start($actions) { 
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[self::SESSION_REFERENCE_NAME]) || !isset($_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName])) {
            $_SESSION[self::SESSION_REFERENCE_NAME] = [];
            $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName] = []; 
            $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['actions'] = $actions; 
            $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['2fa'] = []; 
        }      
    }

    private $instanceName;

    /**
     * The authentications can be configured when the TheFactorService is initialized
     */
    private $authentications = [];

    /**
     * Add a two factor implementation to be executed.
     */
    public function add(string $twoFactor) { 
        array_push($_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['2fa'],$twoFactor);
    }

    /**
     * Will remove the first 2FA from the array
     */
    public function passed() {
        array_shift($_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['2fa']);
    }

    /**
     * Need to see how many login attempts the user had on the two factor authentication
     */
    private function loginAttempts($addAttempt = 0) {
        $result = Database::fetch(
            "SELECT `login_attempts`,`login_lockout` FROM pef_login WHERE id = :loginId",
            array(":loginId"  => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );
        
        $now = date('Y-m-d H:i:s', time());

        // Block automated scripts trying to login	
        if(!empty($result['login_lockout'])) {
            if ($result['login_lockout'] > $now) {
                // Just return the login attemps
                return 3;
            } else {
                // reset the attempts
                $result['login_attempts'] = 0;
            }
        } 

        if (empty($result) || empty($result['login_attempts'])) {
            $attempts = 0;
        } else {
            $attempts = intval($result['login_attempts']);
        }
        
        $attempts+=$addAttempt;
        
        $lockout = null;
        if($attempts >= 3) {
            $later = new DateTime();
            $later->add(date_interval_create_from_date_string('30 seconds'));
            $lockout = $later->format('Y-m-d H:i:s');
            Log::Perms('TwoFactorService', 'attempts', "TwoFactorService failed on attempt 3 for user id: '".$_SESSION['2fa_userId']."'.");
        }
        Database::execute("UPDATE pef_login SET `login_attempts`=:attempts, `login_lockout`=:lockout where id=:loginId",
            array(
                ":loginId" => $_SESSION['2fa_userId'],
                ":attempts" => $attempts,
                ":lockout" => $lockout
            ), DIB::LOGINDBINDEX);
    
        return $attempts;
    }


    /**
     * Get the array of two factor implementation that is still remaining authentication
     */
    private function get() {
        return $_SESSION[self::SESSION_REFERENCE_NAME][$this->$referenceName];
    }

    /**
     * Gets the current 2FA method
     */
    private function current2FA() : TwoFactorImplementation {
        $className = $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['2fa'][0];
        //We need to load the class name for the specified method
        DibApp::load('dibAuthenticate', $className.'.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');
        $twoFactorAuth = new $className($this->userId);
        TwoFactorService::$inputLabel = $twoFactorAuth->inputLabel();
        TwoFactorService::$resetLabel = $twoFactorAuth->resetLabel();
        TwoFactorService::$resetConfirmLabel = $twoFactorAuth->resetConfirmLabel();
        
        TwoFactorService::$forgotLinkText = $twoFactorAuth->forgotLinkText(TwoFactorConfig::ALLOW_USER_TO_DISABLE_DURING_FORGOT_PROCESS);
        TwoFactorService::$currentTitle = $twoFactorAuth->title();
        
        
        return $twoFactorAuth;
    }

    /**
     * Gets the current 2FA method
     */
    private function get2FAs() : array {
        return $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['2fa'];
    }
    /**
     * Gets the current actions required for execution
     */
    private function actions()  {
        return $_SESSION[self::SESSION_REFERENCE_NAME][$this->instanceName]['actions'];
    }

    /**
     * Checks if there are more 2FA left this determines if the user is authenticated
     */
    public function isAuthenticated() {
        if (count($this->get2FAs())==0) return true;
        return false;
    }

    /**
     * Check if the current two factor function is configured.
     * @return boolean
     */
    public function isConfigured() { 
        $twoFactorAuth = $this->current2FA();
        return $twoFactorAuth->isConfigured();
    }

    /**
     * Start the authentication process with the given authentication processes
     */
    public function startAuthentication() {
        if ($this->isAuthenticated()) return $this->actions();
        // Get the first 2fa item in the array
        $twoFactorAuth = $this->current2FA();
        // Execute the before auth load (For example if it implements that the user need to receive 
        // something this will need to be handeled in the auth class)
        if ($twoFactorAuth->isConfigured()) {
            $twoFactorAuth->beforeAuthLoad();
            return $twoFactorAuth->infoMessage();
        } else { 
            TwoFactorService::$inputLabel = $twoFactorAuth->configLabel();
            return $twoFactorAuth->setupMessage();
        }
    }

    public function startReset() {
        if ($this->isAuthenticated()) return $this->actions();
        // Get the first 2fa item in the array
        $twoFactorAuth = $this->current2FA();
        // Execute the before auth load (For example if it implements that the user need to receive 
        // something this will need to be handeled in the auth class)
        if ($twoFactorAuth->isConfigured()) {
            $twoFactorAuth->beforeResetLoad();
            return $twoFactorAuth->resetMessage();
        } else { 
            TwoFactorService::$inputLabel = $twoFactorAuth->configLabel();
            return $twoFactorAuth->setupMessage();
        }
    }

    public function reset($userInput, $confirmationInput, $enabled) { 

        // The user automatically athorized if there isn't any 2fa left
        if ($this->isAuthenticated()) return array('success',$this->actions());
        
        $twoFactorAuth = $this->current2FA();
        
        // Checking if the authentication passed
        $loginAttempts = $this->loginAttempts();

        if ($loginAttempts < 3 && $twoFactorAuth->reset($userInput, $confirmationInput, $enabled)) { 
            // If the user chooses to disable the two factor we need to remove the two factor method from cache, and fake as if the user has passed this authentication.
            if ($enabled == false) {
                $this->passed();
            }
            return array('next', 'Going to the authenticate page');
        }
        // Add one login attempt that failed
        $this->loginAttempts(1);
        // Add count so user cant try for 3 times - has to wait for a period if incorrect.
        if ($loginAttempts >=3) return array('error', "Too many attempts... please wait 30 seconds and try again");
        // Not one rule matched user is not authorized yet.
        return array('error', $twoFactorAuth->resetErrorMessage());

    
    }

    /**
     * Checking if the user is authorized given the input from the user and the current 
     * two factor authentication method in question
     */
    public function authenticate($userInput) {
        // The user automatically athorized if there isn't any 2fa left
        if ($this->isAuthenticated()) return array('success',$this->actions());
        
        $twoFactorAuth = $this->current2FA();
        if ($twoFactorAuth->isConfigured()) {
            TwoFactorService::$inputLabel = $twoFactorAuth->inputLabel();
            // Checking if the authentication passed
            $loginAttempts = $this->loginAttempts();

            if ($loginAttempts  < 3 && $twoFactorAuth->authenticate($userInput)) { 
                // Remove the authentication method since the user passed and can now move on too the next two factor authentication method
                $this->passed();
                // If there isn't any 2fa left then we just return that the user is now authorized
                if ($this->isAuthenticated()) return array('success',$this->actions());
                // Get the next action ready
                return array('next', $this->startAuthentication());
            }
            // Add one login attempt that failed
            $this->loginAttempts(1);
            // Add count so user cant try for 3 times - has to wait for a period if incorrect.
            if ($loginAttempts >=3) return array('error', "Too many attempts... please wait 30 seconds and try again");
            // Not one rule matched user is not authorized yet.
            return array('error', $twoFactorAuth->errorMessage());

        } else {
            TwoFactorService::$inputLabel = $twoFactorAuth->configLabel();
            list($success, $configureMsg) = $twoFactorAuth->configure($userInput);
            if ($success) {
                // We now need to tell the client side that the next message is the info message
                return array('next', $this->startAuthentication());  
            } else {
                return array('error', $configureMsg);
            }
        }
    }
    
    /**
     * End the two factor service session
     */
    public function flush() {
        session_write_close();
    }

    /**
     * Should you like to clear the requirements for logins for the specified service
     */
    public static function clear($referenceName) { 
        $_SESSION[self::SESSION_REFERENCE_NAME][$referenceName] = []; 
        session_write_close();
    }
}