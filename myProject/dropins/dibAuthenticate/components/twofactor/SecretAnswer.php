<?php

include_once('TwoFactorImplementation.php');
include_once('TwoFactorReset.php');
/**
 * SecretAnswer will just take the user input as the validation however is more the simplist form of the two factor authentication and
 * a good example how to implement.
 * Security level : 1
 */
class SecretAnswer extends TwoFactorReset implements TwoFactorImplementation {
    
    public function beforeAuthLoad(): void { 
        // We dont need to do anything
    }

    /**
     * We need to compare the $userInput with the secret answer
     *
     * @param [string] $userInput
     * @return void
     */
    public function authenticate($userInput) { 
        //Get the anser from the database and compare with the $userInput
        $result = Database::fetch(
            "SELECT `data` FROM pef_twofactor WHERE pef_login_id = :loginId AND class_name='SecretAnswer'",
            array(":loginId"  => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );

        if (empty($result) || empty($result['data'])) 
            return false; 

        if (Authenticator::confirmPassword($userInput,$result['data'])) 
            return true;

        return false;
    }

    /**
     * Return the user information string that will be displayed on the client side
     * @return string
     */
    public function infoMessage() : string { 
        return "Enter your secret message you have entered during setup";
    }


    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function errorMessage() : string { 
        return "The secret message you have provided is not correct";
    }

    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function inputLabel() : string { 
        return "Confirm your secret message";
    }


    /**
     * Specify the title for the secret answer
     */
    public function title() : string { 
        return "Secret answer";
    }

    /**
     * Return the label when you configure this setting.
     */
    public function configLabel() : string {
        return "What is your secret message?";
    }

    /**
     * Configure the user secret answer for the user
     */
    public function configure($userInput) { 
        $success = true;
        $result = Database::execute(
            "UPDATE pef_twofactor SET `data`=:answer WHERE pef_login_id = :loginId AND class_name='SecretAnswer'",
            array(":loginId" => $_SESSION['2fa_userId'], ":answer" => Authenticator::encryptPassword($userInput)),
            DIB::LOGINDBINDEX
        );        
        
        //Since the answer is now configured, we just want the user to now validate this step so we return the infoMessage 
        return array($success,$this->infoMessage());
    }

    /**
     * Returns the message to the user if the user needs to setup the twofactor message
     * @return string
     */
    public function setupMessage() : string { 
        return "Your secret answer is not yet saved, please provide it now.";
    }

    /**
     * Check if the user has configured the SecretAnswer functionality
     */
    public function isConfigured() {
        $result = Database::fetch(
            "SELECT `data` FROM pef_twofactor WHERE pef_login_id = :loginId AND class_name='SecretAnswer'",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );

        if (empty($result) || empty($result['data']))
            return false;
            
        return true;
    }

    // We will be using the base reset functionality that is defined on TwoFactorReset.php

}