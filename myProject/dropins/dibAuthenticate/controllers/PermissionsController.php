<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

class PermissionsController extends Controller {
    
    /**
	* Used by Administrator to change user's passwords
	* 
	* @return
	*/
    public function setNewPassword($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {        
		// Only admin users allowed here... 
		if(DIB::$USER['admin_user'] != 1)
			return $this->invalidResult('Invalid request.');
        
        if(empty($clientData['alias_self']['login_id']))
        	return $this->invalidResult("First save the record and try again.");
        	
        // password policy checks...
        if(empty($clientData['alias_self']['pswd']))
        	return $this->invalidResult("Provide a value for 'New Password' and try again.");
        
        $password = $clientData['alias_self']['pswd'];
        $id = $clientData['alias_self']['login_id'];

		// Ensure that if the developer has overriden Authenticator.php but not PermissionController, that their Authenticator is called.
		DibApp::load('dibAuthenticate', 'Authenticator.php', 'components');
        
		$result = Authenticator::resetpassword($id, $password, $password, TRUE);
		if($result !== TRUE)
			return $this->invalidResult($result);
        
        return $this->validResult(NULL, 'Password updated successfully', 'success');
    }

}