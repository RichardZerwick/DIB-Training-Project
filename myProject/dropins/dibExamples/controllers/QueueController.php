<?php

/*
Demonstrates the use of Queues to execute tasks asynchronously.
Queues are useful for long-running scripts...

HOW IT WORKS:
The client is instructed to start polling the server (by calling the QueueController) every asyncInterval seconds for any actions or messages that are waiting.
This instruction is either merged into the UI by secifying the asyncInterval(seconds) in the response_type field of the Item/Container event tables, 
  OR the server can include asyncInterval as part of a validResult response to the client.
  Note, when response_type is used, the client will not wait for the server to send a validResult or invalidResult response.
In the meantime, the server prepares actions and messages and adds them to the Queue. 
Actions/Messages are executed on the client in the order that they were added to the Queue.
The server can at any point change the value of asyncInterval, or set it to 'stop' (which instructs the client to stop polling).
If the client polls retryCount times (default is 10) without receiving any response, the polling will stop automatically.
  The value of retryCount can also be updated at any time.
Queues are stored per loginId, and it is possible to target another loginId - ensure their clients are polling though.

*/

class QueueController extends Controller {

	// btnStartQueueFromClient on dibexOverview, dibexQueue, dibexActionRefreshQueue and dibexEventBasics
    public function startQueueFromClient($containerName, $itemEventId, $clientData=array(), $itemAlias='') {
		// Note, this code assumes that the Response Type of the request that called this function started the queue's polling requests.

        // Do some validation here. If validation failes, stop the client from waiting for a reponse, and from polling the Queue
        if(Date('mm-dd') == '01-01')
            return $this->invalidResult("Sorry this button does nothing on New Year's day.", 'dialog', 1500, true, 'stop');

        // Set the PHP timeout (0 = infinite, but rarely a good idea)
		set_time_limit(60 * 2); // 120 seconds
		
		// Stop client from waiting for a response for the original request, to prevent client-side errors (note, polling requests will continue)
		$this->flushResult();

		// Note, the available parameters are the same as validResult, so you could do this:
		// $this->flushResult($actionList, "Please wait, this script can take up to 40 seconds to complete!", 'notice', 5000);

        // If there is a chance that the server will not generate a response in time due to for eg. a long running SQL statement
        // increase the retry count for polling 
        Queue::updateIntervals(1200, 20); // interval remains 1200 (same as the Respones Type), but retries now set to 20 (default is 10)

		// The dibexActionRefreshQueue demo needs us to set the queueUid field for the demo buttons to work.
		ClientFunctions::addAction($actionList, 'set-value', ['queueUid' => DibApp::$queueUid]);
		Queue::add($actionList);
      	
		$loopCount = ($containerName == 'dibexActionRefreshQueue') ? 15 : 5;
        $this->doLengthyTask($loopCount);

        Queue::addMsg('Finito', "Yay, we're all done :)", 'dialog', 1500, true, 'stop');
    }

	// Works on NGINX. Apache Web Servers require very specific configurations to allow immediate flushing of content to the client.
	// btnStartQueueFromServer on dibexQueue
    public function startQueueFromServer($containerName, $itemEventId, $clientData=array(), $itemAlias='', $actionData=[]) {

        // Do some validation here if needed, and stop the client from polling
        if(Date('mm-dd') == '01-01')
            return $this->invalidResult("Sorry this button does nothing on New Year's day.");

		if(!array_key_exists('validationPassed', $actionData)) {
			// Create a unique identifier for the queue, 
			//   used for polling requests, and 
			//   the name of the file that stores the queued actions (/runtime/tmp/QueuedActionsFor_xxx)
			// DibApp::randomToken() generates a cryptographically secure random string which is important to prevent hackers predicting the value.
			// NOTE, the queueUid will be rejected if it is not alphanumeric or > 30 characters.
			$queueId = DIB::$USER['id'] .'X'. DibApp::randomToken(10);

			// Configure DIB to return queueId to the client
			DibApp::$queueUid = $queueId;
			
			// The client request that called this function is waiting for a result
			//   and will display an error message if nothing is returned after 30 seconds.
			// End the wait and send the queueId value along with a desired polling interval time.
			$timeBetweenPollingRequests = 2000;

			ClientFunctions::addAction($actionList, 'run', array('actionType'=>'itemAlias', 'action'=>'btnStartQueueFromServer', 'actionData'=>array('validationPassed'=>true, 'queueId'=>$queueId)));
			return $this->validResult($actionList, null, null, null, $timeBetweenPollingRequests);
		}

		// Configure id to use for adding actions to the queue
		if(!isset($actionData['queueId']))
			return $this->invalidResult('System error. Please refresh the browser and try again.');

		DibApp::$queueUid = $actionData['queueId']; // This value was passed with the 'run' action above.

		// Stop client from waiting for a response to prevent client-side errors
		$this->flushResult();

        // If the server will potentially be busy for a long time it is a good idea to set the PHP timeout...
		set_time_limit(120); // 120 seconds
		
        // Do something that takes a long time and keep the user informed using progress bars etc.
		// Note its important to send a message/action intermittently to prevent the queue from stopping due to no response
		// Set the $asyncInterval (time between polling requests) and $asyncRetryCount (count of empty results to allow before stopping) if necessary.
		// Queue::updateIntervals($asyncInterval, $asyncRetryCount)
		$this->doLengthyTask(5);
        
        Queue::addMsg('Finito', "Yay, we're all done :)", 'dialog', 2000, true, 'stop');
    }
	
    public function queueWithPrompt($containerName, $itemEventId, $clientData=null) {
		// Sometimes a result is reached which makes it clear that more info is required from the user before further processing
		// We can then use a prompt to get the answer, and then call a different function with parameters, 
		// or the same function with the same parameters and the prompt answer

		// Note it is important to stop the original browser request from waiting for a result within 30 seconds, 
		// using $this->flushResult()/validResult()/invalidResult()

		// See if this call contains the prompt answer
		$answer = array_key_exists('prompt', $clientData) ? $clientData['prompt'] : null;

		$maxCount = 5;

		$rst = Database::fetch("SELECT count(*) as counter FROM test_client");
		if($rst === false) {
			$errMsg = "There was a database error. We expected the test_client table in the main Dropinbase database. Please check your database connection";
			return $this->invalidResult($errMsg, 'dialog', 2000, null, 'stop');
		}

		$total = $rst['counter'];

		if($total > $maxCount && $answer === null) {
			// Whoops, we need more info
			// Use itemAlias style action to call the same function
			ClientFunctions::addPrompt($actionList, 'Warning', "There are $total clients. How many do you want to process now?", 'itemAlias', 'btnQueueWithPrompt', TRUE, null, null, '/^[0-9]*$/', 'Please provide an integer > 0');
			
			// Or use a Y/N popup:
			// ClientFunctions::addMsgPopup($actionList, 'Warning', "There are $total clients. Do you want to process them all?", 'itemAlias', 'btnHidden');
			Queue::add($actionList, true, 'stop');

			// Stop original request from waiting for an answer
			// It is assumed that everything so far has been processed under 30 seconds, else flushResult() would have been required earlier on (after validation).
        	return $this->validResult(null); 
		}

		if($answer != (string)(int)$answer)
			return $this->invalidResult('You must provide an integer value. Please try again', 'dialog', 2000, null, 'stop');
		
		if($total > $answer) $total = $answer;

		if($total < 1)
			return $this->invalidResult('No clients were processed.', 'dialog', 2000, null, 'stop');

		// We're entering code that will take a long time, so stop the original request from waiting.
		$this->flushResult();

        // Call our worker function which adds actions to the Queue...
        $this->doLengthyTask(5);

        Queue::addMsg('Finito', "Finished processing $total clients :)", 'dialog', 1500, true, 'stop');

		/**
		 * ClientFunctions::addPrompt
		 * 
		 * Add a prompt action to $actionList, requesting an answer from the user 
		 * @param array $actionList array passed by reference which will have new action added to it
		 * @param string $title HTML title of the message
		 * @param string $text HTML main message body 
		 * @param string $actionType = submitUrl/action/itemAlias
		 * @param mixed $action = For 'submitUrl' - a server-side or client-side uri eg '/peff/DibTasks/DeleteCont'
		 *         For 'action' - an array, e.g. array('submitUrl' => 'shared.action.setValue', 'params'=>array('first_name' => 'Tom'))
		 *         For 'itemAlias' - the Alias of another item (normally an invisible button) of which the click event will be fired
		 * 
		 * @param boolean $forceInput - whether the user will be forced to provide a value.
		 * @param int $cancelActionType - same as $actionType
		 * @param bool $cancelAction - same as $action
		 * @param string $regexRules - regular expression specifying client-side validation rules for the user's answer
		 * @param string $errorMsg - error message to be displayed when validation fails
		 *
		 */
    }

	 
    // btnQueueWithPromptII on dibexQueue
    public function queueWithPromptII($containerName, $itemEventId, $stage='validation', $clientData=array()) {
        // This method if often cleaner than the previous one in that it only starts the queue when needed.
		// It uses a variable named $stage to know what part of the code to execute with each call

        // The function is called twice:
        //   once to send a prompt for an answer          => $stage = validation, actionType = actions
        //   and again to process the long-running part using the answer          => $stage = action, actionType = 2000
         
        // Do some validation here if needed, and stop the client from polling if something is wrong

        if(Date('mm-dd') == '01-01')
            return $this->invalidResult("Sorry this button does nothing on New Year's day.");

        if($stage == 'validation') {
            // Prompt the user for some info
            $question = 'How many times would you like to repeat the actions?';
            // setup a URL to call this same function, but this time set $stage to 'action'
            $url = $_SERVER['REQUEST_URI'] . "&itemEventId=$itemEventId&stage=action";
            // set the responseType to 1200 milliseconds (default is 'actions') - just add semicolon and 1200 to URL (see prompt action example)
            $url .= ';1200';
            // Force an answer from the user
            $forceInput = TRUE;

            ClientFunctions::addPrompt($actionList, 'Please enter a value', $question, 'submitUrl', $url, $forceInput);
            return $this->validResult($actionList);
        }

        if($stage != 'action' || !array_key_exists('prompt', $clientData)) {
            // invalid stage or clientData - possible hacking - stop everything
            return $this->invalidResult("Invalid request. Please refresh the browser and try again.", 'dialog', null, true, 'stop');
        }

        // Note $clientData['prompt'] will contain the answer to the prompt (which is sent below)
        // Make sure it is not empty (an hacker can send anything), is integer, and not too big or small
        $answer = (empty($clientData['prompt'])) ? 0 : $clientData['prompt'];

        // Stop the queue if needed
        if($answer != (string)(int)$answer || $answer > 10 || $answer < 3)
            return $this->invalidResult("An integer value between 4 and 10 is required. Please try again.", 'dialog', null, true, 'stop');

        // Accept all is in order, continue with the long running part

        // If the server will potentially be busy for a long time it is a good idea to set the PHP timeout...
		set_time_limit(120); // 120 seconds
		  
		// The client request that initiated the call always waits for a result
        //   and will display an error message if nothing is returned after 30 seconds.
        //   So let's end the wait by sending an empty response that does nothing.
		$this->flushResult();

		// Note, the available parameters are the same as invalidResult, so you could do this:
		// $this->flushResult("Please wait, this script can take up to 40 seconds to complete!", 'notice', 5000);

        // If there is a chance that the server will not generate a response in time due to for eg. a long running SQL statement
        // increase the retry count for polling 
        Queue::updateIntervals(1200, 20); // interval remains 1200 (same as the Respones Type), but retries now set to 20 (default is 10)
      	
        // Simulate a long running operation
        for($i = 1; $i <= $answer; $i++) {
            // update the hidden number (progressValue) which the progressBar is bound to
            ClientFunctions::addAction($actionList, 'set-value', array('progressValue' => intval(($i / $answer) * 100)));

            // add this action to the queue
            Queue::add($actionList); // Note, $actionList is automatically emptied by the Queue::add function

            Queue::addMsg('Progress', "Performed $i of $answer actions", 'success');

            // Wait two seconds (simulate long running actions...)
            sleep(2);
        }

        Queue::addMsg('Finito', "Yay, we're all done :)", 'dialog', 2000, true, 'stop');
    }
	
    
    public function sendMsg($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null, $async = false) {
		// Prevent public access
		if(DIB::$USER['name'] === 'system_public')
			return  $this->invalidResult('This function is not available for public users');
		
        list($msg, $loginId) = DibApp::getSubmitVal($submissionData, 'sIA.s', array('queueMsg', 'queueLoginId'));
		
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult();

        // Change the retryCount - now the queue will stop automatically after receiving 20 empty results
        Queue::updateIntervals(null, 20);
        
        // validate loginId
        if($loginId == (string)(int)$loginId) {
        	$sql = "SELECT l1.first_name as name1, l2.first_name as name2, l2.notes
        			FROM pef_login l1 LEFT JOIN pef_login l2 ON l2.id = :lId2
        			WHERE l1.id = :lId1";
			$rst = Database::fetch($sql, array(':lId1'=>DIB::$USER['id'], ':lId2'=>$loginId), DIB::LOGINDBINDEX);
			
			if(Database::count()===1 && strlen($rst['notes'])>2) {
				// sanitize msg and add it to the user's queue
				$me = $rst['name1'];
				$them = $rst['name2'];
				$msg = (empty($msg)) ? 'nothing' : preg_replace('/[^\w ]/', '', $msg); 
				$queueUid = $rst['notes'];
				Queue::addMsg('Message', "Message sent", 'notice', 4000, true, 1000);
				return Queue::addMsg('Message', "Hello $them! Msg from $me: " . $msg, 'notice', 5000, true, 1000, $queueUid);
			}
		}
        
        return Queue::addMsg('Error', "Sorry, the selected login is not listening, or is invalid.", 'dialog', 0, true, 2000);
    }
    
    public function listen($containerName, $itemEventId, $submissionData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		// Prevent public access
		if(DIB::$USER['name'] === 'system_public')
			return  $this->invalidResult('This function is not available for public users');

		// Store queueUid against Login id (***TODO, handle reset of pef_login field after 20s of no activity)
    	Database::execute("UPDATE pef_login SET notes = :uid WHERE id = " . DIB::$USER['id'], array(':uid'=> DibApp::$queueUid));
		Queue::addMsg('Messages', 'Listening for messages... The queue will automatically stop after 20s of no activity', 'notice', 5000, true, 1000);
		
		// The client request that initiated the call always waits for a result so let's satisfy its need:
		$this->flushResult();
	}

	// *** VERY IMPORTANT to make this function private, else public users will have access to it (since $containerName and $itemEventId are not function parameters)
	private function doLengthyTask($loopCount) {
		set_time_limit(60*10);

        for($i = 1; $i <= $loopCount; $i++) {
            // update the hidden number (progressValue) which the progressBar is bound to
            ClientFunctions::addAction($actionList, 'set-value', array('progressValue' => $i * 20));

            // Add this action to the queue
			// Note, $actionList below is automatically emptied by the Queue::add function,
			//    making it ready for the next ClientFunctions::addAction($actionList, ...) call
            Queue::add($actionList); 

            Queue::addMsg('Progress', "Performed $i of $loopCount actions", 'success', 1000);

            // Wait two seconds (simulate long running actions...)
            sleep(2);
        }
	}

}