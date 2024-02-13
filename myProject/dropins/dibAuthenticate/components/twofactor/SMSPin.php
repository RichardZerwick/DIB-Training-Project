<?php

include_once('TwoFactorImplementation.php');
include_once('TwoFactorReset.php');
//Importing dibClickatell library - perhaps toe be replaced with another generic Dib library 
//that wraps the SMS functionality
DibApp::load('dibSms', 'SmsProvider.php', 'components');
      
/**
 * SMS Pin will send the user a pin code to his mobile number to validate is authenticated
 * we will store the SMS key in the session for reference on the authenticated
 * Security level : 2
 */
class SMSPin extends TwoFactorReset implements TwoFactorImplementation {

    private $validMobile = false;
    function __construct() { 

        $result = Database::fetch(
            "SELECT mobile_number FROM pef_login WHERE id = :loginId",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );

        //We need to get the logged in users mobile number
        if (empty($result['mobile_number'])) {
           // Log::err("The user is configured to use SMS for two-factor authentication however pef_login doesn't have a mobile_number stored");
            $_SESSION['SMSPinMessage'] = "Your mobile number was not found please contact the system admin, in order to continue.";
            return;
        }
        $_SESSION['SMSPinMessage'] = "An SMS with an OTP was sent.";
        $this->validMobile = $result['mobile_number'];
    }
    /**
     * Before auth load
     *
     * @return void
     */
    public function beforeAuthLoad(): void { 
        // On authentication we need to generate a random number and 
        // try to get the credentials of the user in order to send the 
        // sms to the user
        if (empty($_SESSION['SMSPin']) && $this->validMobile != false) {

            $randomPin = rand(10000, 99999);
            $message =  SmsProvider::otpMessage();
            $result = SmsProvider::sendSms($this->validMobile, "$message $randomPin (".strlen($randomPin)." digits)." );

            if (gettype($result) == 'string') {
                $_SESSION['SMSPinMessage'] = $result;                
            } else {
                $_SESSION['SMSPin'] = $randomPin;    
                $_SESSION['SMSPinMessage'] = "An SMS with an OTP was sent.";
                Log::$infoMsg = "Enter the OTP";
            }
            return;
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
        $oneTimePin = $_SESSION['SMSPin'];

        //We need to clear the session pin once the user has tried the pin       
        $_SESSION['SMSPin'] = null;

        if ($userInput==$oneTimePin) return true;
        return false;
    }

    /**
     * Return the user information string that will be displayed on the client side
     * @return string
     */
    public function infoMessage() : string { 
        return $_SESSION['SMSPinMessage'];
    }

    public function title() : string { 
        return "SMS Pin Authentication";
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
        return "Confirm the OTP sent to your mobile number";
    }
    /**
     * Return the label when you configure this setting.
     */
    public function configLabel() : string {
        return "Please provide your mobile number";
    }

    private function validate_phone_number($phone) {
        // Allow +, - and . in phone number
        $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        // Remove "-" from number
        $phone_to_check = str_replace("-", "", $filtered_phone_number);
        // Check the lenght of number
        // This can be customized if you want phone number from a specific country
        if (strlen($phone_to_check) < 10 || strlen($phone_to_check) > 14) {
            return false;
        } else {
        return true;
        }
    }

    /**
     * Configure the user secret answer for the user
     */
    public function configure($userInput) { 
        if(!$this->validate_phone_number($userInput)) {
           return array(false,"Not valid phone number.");
        }
        $success = true;
        $result = Database::execute("UPDATE pef_login SET `mobile_number`=:answer where id = :loginId",
            array(
                ":loginId"  => $_SESSION['2fa_userId'],
                ":answer"  => $userInput,
            ), DIB::LOGINDBINDEX
        );
                
        //Since the answer is now configured, we just want the user to now validate this step so we return the infoMessage 
        return array($success, $this->infoMessage());
    }

    /**
     * Returns the message to the user if the user needs to setup the twofactor message
     * @return string
     */ 
    public function setupMessage() : string { 
        return "Please confirm your phone number.";
    }

    /**
     * Check if the user has configured the mobile number to send the notification functionality
     */
    public function isConfigured() {
        $result = Database::fetch(
            "SELECT `mobile_number` FROM pef_login WHERE id = :loginId",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );
        if (empty($result) || empty($result['mobile_number'])) {
            return false;
        }
        return true;
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
        $_SESSION['SMSPin'] = null;

        Database::execute(
            "UPDATE pef_login SET `mobile_number`=null WHERE id = :loginId",
            array(":loginId" => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );        
        return true;
    }
}