<?php
class UserTwoFactor { 
    private $referenceName;
    private $userId;
    private $twoFactorMethods;


    /**
     * check if the user has twofactor services. If so, initialize services.
     */
    function checkTwoFactor($userId = null, $referenceName='dibAuthenticate') {
       // Get any twofactor
        
        $this->twoFactorMethods = Database::execute(
            "SELECT * FROM pef_twofactor WHERE `enabled` = 1 AND pef_login_id = :loginId ORDER BY `order_no`",
            array(':loginId' => $userId),
            DIB::LOGINDBINDEX
        );

        if(empty($this->twoFactorMethods))
            return FALSE;

        $this->referenceName = $referenceName;
        $this->userId = $userId;
        
        session_start();
        $_SESSION['2fa_userId'] = $userId;
        session_write_close();

        DibApp::load('dibAuthenticate', 'TwoFactorService.php', 'components'.DIRECTORY_SEPARATOR.'twofactor');
		
        // Reset the two factor service
        TwoFactorService::clear($this->referenceName);

		$twoFactorService = new TwoFactorService($userId, $referenceName);
        // Add all authentication methods enabled for the user in pef_twofactor 
        // using reflection to the TwoFactor service       

        foreach ($this->twoFactorMethods as $index => $twoFactorMethod) {
            $className = $twoFactorMethod['class_name'];
            $twoFactorService->add($className);
        }
        
        return TRUE;
    }

    /**
     * Checks if the user has twofactor authentication enabled and what two factor services 
     * the user would like us to execute for him to access his account
     */
    function enabled() {
        $twoFactorService = new TwoFactorService($this->userId, $this->referenceName);
        return !$twoFactorService->isAuthenticated();
    }

    /**
     * Flush the session and store the information 
     */
    function flush() { 
        $twoFactorService = new TwoFactorService($this->userId, $this->referenceName);
        $twoFactorService->flush();
    }
}