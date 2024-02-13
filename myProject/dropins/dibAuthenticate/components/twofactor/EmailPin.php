<?php

include_once('TwoFactorImplementation.php');
include_once('TwoFactorReset.php');
//  Importing the emailng class

DibApp::load('dibMail', 'DSendMail.php', 'components');

/**
 * Email Activation link will send the user a GUID code to their email to validate the email address
 * We will store the GUID key in the session for reference on the authenticated
 * Security level : 2
 */
class EmailPin extends TwoFactorReset implements TwoFactorImplementation {

    // Define default values if not provided in /configs/TwoFactorAuthConfig.php
    public $emailSubject = 'Two-factor OTP';

    public $emailTmpl =  "Dear ~~first_name ~~last_name<br><br>
    Please use the following OTP: ~~randomPin<br><br>
    Regards
    System Admin";

    public $emailTmplParamsSql = "SELECT first_name, last_name FROM pef_login WHERE id = :loginId";

    public $emailAddressSql = "SELECT email FROM pef_login WHERE id = :loginId";

    /**
     * Before auth load
     *
     * @return void
     */
    public function beforeAuthLoad() : void { 
        // On authentication we need to generate a random number and 
        // try to get the credentials of the user in order to send the 
        // email to the user
        if (empty($_SESSION['EmailPin'])) {
            
            $randomPin = rand(10000, 99999); 

             // Get SQL statement used to obtain email address, and variables that will be used in the email template
            $path = DIB::$BASEPATH . 'configs' . DIRECTORY_SEPARATOR . 'TwoFactorAuthConfig.php';
             
            if(file_exists($path))
                require_once $path;

            $result = Database::fetch($this->emailAddressSql, array(':loginId' => $_SESSION['2fa_userId']), DIB::LOGINDBINDEX);

            //We need to get the logged in users email
            if (empty($result['email'])) {
                Log::err("The user is trying to use one time pin for authentication; however the SQL statement used to return the email address returns an empty result. Please check error.log for SQL errors in $path");
                $_SESSION['EmailPinMessage'] = "Your email was not found. Please contact the System Administrator in order to continue.";
                return;
            }

            $rst = Database::fetch($this->emailTmplParamsSql, array(':loginId'=>$_SESSION['2fa_userId']), DIB::LOGINDBINDEX);
            if(empty($rst)) {
                Log::err("There were no parameters returned to be used in the email template. Please check error.log for SQL errors in $path");
                $_SESSION['EmailPinMessage'] = "Your email was not sent. Please contact the System Administrator in order to continue.";
                return;
            }

            $rst['randomPin'] = $randomPin;

            foreach($rst as $field=>$value) {
                $value = (empty($value)) ? '' : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                $this->emailTmpl = str_replace("~~$field", $value, $this->emailTmpl);
            }
                        
            // Send mail
            DibApp::load('dibMail', 'DSendMail.php', 'components');
            $email = $result['email'];
            $result = DSendMail::sendSmtp($result['email'], $this->emailSubject, $this->emailTmpl, NULL, NULL, 'info@dropinbase.com');
            
            if (gettype($result) == 'string')
                $_SESSION['EmailPinMessage'] = $result;
            else {
                $_SESSION['EmailPin'] = $randomPin;
                $_SESSION['UserEmail'] = $email;
                $_SESSION['EmailPinMessage'] = "An email with an OTP was sent.";
            }
            session_write_close();
        }
    }

    /**
     * We need to compare the $userInput with the secret answer
     *
     * @param [string] $userInput
     * @return void
     */
    public function authenticate($userInput) { 
        //Get the anser from the database and compare with the $userInput
        $oneTimePin = $_SESSION['EmailPin'];

        //We need to clear the session pin once the user has tried the pin
        $_SESSION['EmailPin'] = null;

        if ($userInput==$oneTimePin) 
            return true;

        return false;
    }

    /**
     * Return the user information string that will be displayed on the client side
     * @return string
     */
    public function infoMessage() : string { 
        return $_SESSION['EmailPinMessage'];
    }

    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function errorMessage() : string { 
        // Perhaps detect how many errors the user has made and redirect them 
        // back to the login screen
        // @todo Also think about adding a resend link option
        return "The one time pin provided was not correct. Please try again.";
    }
    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function inputLabel() : string { 
        return "Enter the one time pin received";
    }
    /**
     * Return the label when you configure this setting.
     */
    public function configLabel() : string {
        return "Confirm your email by entering the one time pin emailed here";
    }

    /**
     * Configure the user secret answer for the user
     */
    public function configure($userInput) { 
       if(!filter_var($userInput, FILTER_VALIDATE_EMAIL))
            return array(false,"Not a valid email adress");

        $success = true;
        $result = Database::execute(
            "UPDATE pef_login SET `email`=:answer WHERE id = :loginId",
            array(":loginId"  => $_SESSION['2fa_userId'], ":answer"  => $userInput),
            DIB::LOGINDBINDEX
        );
                
        //Since the answer is now configured, we just want the user to now validate this step so we return the infoMessage 
        return array($success,$this->infoMessage());
    }

    
    /**
     * Overide the reset functionality
     */
    public function reset($userInput, $confirmationInput, $enable = true) { 
        $result = parent::reset($userInput,$confirmationInput,$enable);
        
        if (!$result) {
            return false;
        } 
        
        //reset the sms pin in session
        $_SESSION['EmailPin'] = null;

        Database::execute(
            "UPDATE pef_login SET `email`=null WHERE id = :loginId",
            array(":loginId" => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );        
        return true;
    }

    /**
     * Returns the message to the user if the user needs to setup the twofactor message
     * @return string
     */
    public function setupMessage() : string { 
        return "Please confirm your email address.";
    }
    
    public function title() : string { 
        return "Email Two Factor Authentication";
    }
    /**
     * Check if the user has configured the SecretAnswer functionality
     */
    public function isConfigured() {
        $result = Database::fetch(
            "SELECT `email` FROM pef_login WHERE id = :loginId",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );
        
        if (empty($result) || empty($result['email']))
            return false;
        
        return true;
    }

}