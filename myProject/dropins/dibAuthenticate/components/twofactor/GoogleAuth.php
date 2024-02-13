<?php

include_once('TwoFactorImplementation.php');
include_once('GoogleAuth'.DIRECTORY_SEPARATOR.'Google2FA.php');
include_once('TwoFactorReset.php');
/**
 * Google authenticato
 * Security level : 5
 */
class GoogleAuth extends TwoFactorReset implements TwoFactorImplementation {

    /**
     * Need to include configs when GoogleAuth loads
     */
    function __construct() {
        $configLocation = DIB::$BASEPATH."configs".DIRECTORY_SEPARATOR."GoogleAuthConfig.php";

        if(file_exists($configLocation))
           include_once($configLocation);

        //Write the config file for the user if the user don't have the click a tell config file
        if (!class_exists("GoogleAuthConfig")) {
            $configFile = fopen($configLocation, "w") or die("Unable to open $configLocation!");
            //We know we have click a tell as the default provider in DIB so we initialize the config file
            $txt = '<?php 
            class GoogleAuthConfig { 
                const SALT = "YOURSALTGOESHERE";
            }
            ?>';
            fwrite($configFile, $txt);
            include_once($configLocation);
        }
    }


    private function encrypt($string, $key)
    {
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', "SECURE" ), 0, 16 );

        $encrypt_method = "AES-256-CBC";
            
        return base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }

    private function decrypt($string, $key)
    {
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', "SECURE" ), 0, 16 );

        $encrypt_method = "AES-256-CBC";
            
        return openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
  
    }
    /**
     * Before auth load
     *
     * @return void
     */
    public function beforeAuthLoad() : void { 
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
            "SELECT `data` FROM pef_twofactor WHERE pef_login_id = :loginId AND class_name='GoogleAuth'",
            array(":loginId"  => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );

        if (empty($result) || empty($result['data'])) 
            return false; 
        // Set the inital key
        // Use this to verify a key as it allows for some time drift.
        $decryptedValue = $this->decrypt($result['data'], GoogleAuthConfig::SALT);
        if(empty($decryptedValue))
            return FALSE;

        return Google2FA::verify_key($decryptedValue, $userInput);
    }

    /**
     * Return the user information string that will be displayed on the client side
     * @return string
     */
    public function infoMessage() : string { 
        return "Google 2-step Authenticator";
    }

    public function title() : string {
        return "Google 2-step Authenticator";
    }

    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function errorMessage() : string { 
        return "2FA verification code error";
    }

    /**
     * Return the user error message if the authentication message didng work string that will be displayed on the client side
     * @return string
     */
    public function inputLabel() : string { 
        return "Enter Google Auth secret code";
    }

    /**
     * Return the label when you configure this setting.
     */
    public function configLabel() : string {
        return "After the scan with Google Authenticator, enter the secret code provided here:";
    }


    /**
     * Configure the google auth for the user
     */
    public function configure($userInput) { 
        if (Google2FA::verify_key($_SESSION['2fa_g2faInitalizationKey'], $userInput))  {
            $encryptedValue = $this->encrypt($_SESSION['2fa_g2faInitalizationKey'],GoogleAuthConfig::SALT);
            $result = Database::execute(
                "UPDATE pef_twofactor SET `data`=:answer WHERE pef_login_id = :loginId AND class_name='GoogleAuth'",
                array(
                    ":loginId" => $_SESSION['2fa_userId'], 
                    ":answer" => $encryptedValue),
                DIB::LOGINDBINDEX
            );        
            
            //Since the answer is now configured, we just want the user to now validate this step so we return the infoMessage 
            return array(true,$this->infoMessage());    
        }
        return array(false,"The code received is not valid.");
    }

    /**
     * Returns the message to the user if the user needs to setup the twofactor message
     * @return string
     */
    public function setupMessage() : string {
        if (!isset($_SESSION['2fa_g2faInitalizationKey'])) {
            $_SESSION['2fa_g2faInitalizationKey'] = Google2FA::generate_secret_key();
        }
        //Need 
        $_SESSION['2fa_g2fatimestamp'] = Google2FA::get_timestamp();
        $_SESSION['2fa_secretkey'] = Google2FA::base32_decode($_SESSION['2fa_g2faInitalizationKey']);	// Decode it into binary
    
        $result = Database::fetch(
            "SELECT `email` FROM pef_login WHERE id = :loginId",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );
        $reference = DIB::$SITENAME;
        if (!empty($result) && isset($result['email'])) {
            $reference = $result['email'];
        }
        
        $qrCode= Google2FA::getQRCode(DIB::$SITENAME,$reference, $_SESSION['2fa_g2faInitalizationKey']);

        return "<span style='text-align:center;display:block'>Please store this secret key somewhere safe before you continue.<br/>Secret key: <b>".$_SESSION['2fa_g2faInitalizationKey']."</b>
        <br/>Now download Google Authenticator from your phone's App store (Google Play Store,iOS App Store) and scan the code below.
        <br/><img src='".$qrCode."'/></span>";
    }

    /**
     * Check if the user has configured the SecretAnswer functionality
     */
    public function isConfigured() {
        $result = Database::fetch(
            "SELECT `data` FROM pef_twofactor WHERE pef_login_id = :loginId AND class_name='GoogleAuth'",
            array(':loginId' => $_SESSION['2fa_userId']),
            DIB::LOGINDBINDEX
        );

        if (empty($result) || empty($result['data']))
            return false;
            
        return true;
    }

}

