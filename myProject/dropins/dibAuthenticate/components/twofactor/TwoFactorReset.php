<?php

/**
 * SecretAnswer will just take the user input as the validation however is more the simplist form of the two factor authentication and
 * a good example how to implement.
 * Security level : 1
 */
class TwoFactorReset {
    
    /**
     * Return the reset message for the user, used to 
     * @return string
     */
    public function forgotLinkText($disabledAllowed) : string { 
        return "Change ".($disabledAllowed? " or disable" : "")." two factor authentication?";
    }

    /**
     * Return the user information string that will be displayed on the client side
     * @return string
     */
    public function resetMessage() : string { 
        return "An email was sent, please provide the email pin you have received.";
    }
    /**
     * Reset error message
     * @return string
     */
    public function resetErrorMessage() : string { 
        return "Could not reset, one or more of the provided values did not match, please try again.";
    }
    /**
     *  at will be displayed on the client side
     * @return string
     */
    public function resetLabel() : string { 
        return "Confirm the pin received";
    }
    /**
     *  at will be displayed on the client side
     * @return string
     */
    public function resetConfirmLabel() : string { 
        return "Confirm the email address";
    }
    /**
     * Rest the secret answer with the option to enable/disable the specified secret answer
     */
    public function reset($userInput, $confirmationInput, $enable = true) { 
        //Check if the email and the email pin that is stored in the session match
        if ($confirmationInput!=$_SESSION['UserEmail'] || $userInput!=$_SESSION['EmailPin']) {
            return false;
        }

        $enabled = 1;
        if ($enable==false) $enabled = 0;

        Database::execute(
            "UPDATE pef_twofactor SET `data`=null,`enabled`=:enabled WHERE pef_login_id = :loginId AND class_name='".get_class($this)."'",
            array(":loginId" => $_SESSION['2fa_userId'], ":enabled" => $enabled),
            DIB::LOGINDBINDEX
        );        
        return true;
    }
     /**
     * Before reset load
     *
     * @return void
     */
    public function beforeResetLoad() : void { 
        //we don't have to do anything perhaps just check if the user has set a secret answer
        include_once('EmailPin.php');
        $emailPin = new EmailPin();
        $emailPin->beforeAuthLoad();
    }
    

}