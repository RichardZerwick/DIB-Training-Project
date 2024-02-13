<?php

class Tests {
	
	/**
	* Validates, transforms and returns user message 
	* Called from eg BasicsController.php's loadClasses function 
	*
	* @param string $msg user message
	* @return string error message on failure, or processed message on success
	*/
    public static function testMsg($msg) {
    	if(!ctype_alnum(str_replace(' ', '', $msg)))
    		return array('error', "The message may only contain alpha-numeric characters and spaces. Change the code and try again... ");
    		
    	return "Testing test message: '$msg'";
    }
    
    
}