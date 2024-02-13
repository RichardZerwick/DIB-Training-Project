<?php 

class DibFunctionsController extends Controller {

    	// Asynchronous execution of concurrent PHP threads
        // /nav/dibexPhpAsync
        public function asyncThread($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
           
                $msg = DibApp::clientData('a_s', 'msg');

                if(empty($msg))
                    return $this->invalidResult("First specify a message and try again.");
                
                // Since $msg will be echoed to the user, it is important to validate the contents to avoid possible xss attacks
                if(!ctype_alnum(str_replace(' ', '', $msg)))
                    return $this->invalidResult("Aikona! The message may only contain alpha-numeric characters and spaces.<br>Please amend and try again.");
                
                // Prepare variables to send to the asynchronous script
                $args = array(
                    'msg'=>$msg,
                    'another_var'=>'here I am'
                );
                
                // Since we'll initiate the Queue server-side, we need to set a unique id that will be used by the client when requesting actions for this user
                // Note DibApp::randomToken() generates a cryptographically secure random string which is important to prevent hackers predicting the value.
                // The call to DibFunctions::async below will send this value to the asynchronous script to use in any Queue actions.
                // Dropinbase will package this value for return the client along with the validResult response below.
                // NOTE, the queueUid will be rejected if it is not alphanumeric or > 30 characters.
                DibApp::$queueUid = DIB::$USER['id'] .'X'. DibApp::randomToken(10);
                
                // Get the path to the script
                $file = DibApp::load('dibExamples', 'AsyncTest.php', 'components', FALSE);
                
                // Start a asyncronous PHP thread to execute the script, and give it a timeout of 5 minutes (0 = infinite = not a good idea).
                // Any errors will be logged in /runtime/logs in a .log file with a random name, prefixed with 'AsyncTest_'
                // The script will run within a Try Catch, so exceptions can be thrown ( eg throw new Exception("Error msg goes here..."); )
                // (the randomness in the log file's name prohibits file-locking behaviour of PHP's shell_exec function with concurrent users)
                
                // Note the following line merely starts the PHP thread (and passes it the DibApp::$queueUid value) - it completes in milliseconds.
                // The thread itself will execute code that can take hours.
                $result = DibFunctions::async($file, $args, (60 * 5), 'AsyncTest_');
                
                // If the thread could be started, $result will be the processId of the running script. On failure it will be FALSE. 
                if($result === FALSE)
                    return $this->invalidResult('System error! Please contact the System Administrator.');

                // MORE concurrent threads can be started here ...
                //$result = DibFunctions::async($fileB, $argsB, (60 * 5), 'AsyncTestB_');
                
                // Send a response to stop the client from waiting, and to start the Queue to poll for messages
                return $this->validResult(
                    TRUE, 
                    "Cooking up some goodness which takes time... In the meantime you may continue using the system for other tasks.",
                    'notice', 4000, 1000
                );
            
            }
            
            // Escaping functions
            public function escaping($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
            /*
                When building responses using text that in any way originated from users, it is important to escape it properly to avoid css attacks.
                (Note Sql injection is avoided by always using PDO parameters as explained above)
                
                The following are places where user data is displayed
                1. Container Components (eg grid/form fields)
                2. Trees
                3. dropdown lists 
                4. (In)ValidResult messages		
                5. msgPopup
                6. Prompt
                7. setValue
                8. ItemHandler		
                
                The first 3 cases are handled by Angular's ng-bind and ng-bind-html directives that escapes data 
                
                The remaining cases must preferably be escaped serverside if there is any possibility that the content may be insecure.
            */
            }
            
            // Other functions ***TODO...
            public function other($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
                DibApp::createPath();
                DibApp::getRealIpAddr();
                Log::err("mmm");
            
            }
        
           
        
}