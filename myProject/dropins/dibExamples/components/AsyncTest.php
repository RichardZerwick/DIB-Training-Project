<?php
// Script called asynchronously from DibFunctionsController.php for demo purposes

// see /vendor/dropinbase/dropinbase/components/Async.php which loads the Dropinbase framework and handles exceptions etc.

// Get variables passed to script
$msg = $AVars['msg'];
$another = $AVars['another_var'];

// Update time between Queue polling calls, and retry count before giving up
//Queue::updateIntervals(2000, 10);

$errMsg = '';
$time = time(); // time in seconds since 1/1/1970 - used to send intermittent progress notifications

// The main loop that runs for a long time
for ($i = 1; $i <= 5; $i++) {
	// Send progress messages to keep the user informed
	if(time() - $time >= 2) {
		Queue::addMsg("Progress", "Processing $i/5 - ", 'notice', 1700);

		// Update progress bar
		ClientFunctions::addAction($actionList, 'set-value', ['progressValue' => (int)(($i / 5) * 100)]);
		Queue::add($actionList);

		$time = time();
	}

	// do something that takes long like sending emails or importing thousands of records
	// Most common Dropinbase class functions are available, like Database.php, DibFunction.php, ClientFunctions.php etc.
	// See see /vendor/dropinbase/dropinbase/components/Async.php for details.
	sleep(2);

	/*
	// if there are any errors, inform the user, log and error message for the Administrator, and quit
	if($result === FALSE) {
		$errMsg = "There was an error processing ... ";
		Queue::addMsg('Error:', $errMsg, 'dialog', 2000, true);
		$error = true;
		break;
	}

	// Alternatively, an exception can be thrown. The message will be logged in the error log, and the user will receive a generic error message.
	if($result === FALSE)
		throw new Exception("Your message contained the word 'error'...");
	*/
}

// Display message to user
if(empty($errMsg))
	Queue::addMsg('Completed!', $msg, 'dialog', 2000, true);

// Note, Async.php will stop the queue for you.
