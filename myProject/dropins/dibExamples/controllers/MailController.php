<?php

class MailController extends Controller {

	public function sendEmail($containerName, $itemEventId, $clientData=array()) {
		list($to, $subject, $bodyHtml) = DibApp::clientData('a_s', array('to', 'subject', 'body'));

		// load email class
        DibApp::load('dibMail', 'DSendMail.php', 'components');

		// Validate email addresses
		$toArray = explode(';', str_replace(',', ';', $to)); // Note, DSendMail::sendSmtp() also splits the email addresses using the same code
		foreach($toArray as $t) {
			if(!DValidate::_email($t))
				return $this->invalidResult('Invalid email address supplied');
		}

		if(empty($subject) || strlen($subject)>50 || !ctype_alnum(str_replace(array('-', ' ', ':'), '', $subject)))
			return $this->invalidResult('Email subject must be less than 51 characters and contain only alphanumeric characters (spaces, dashes and colons allowed)');

		$attachments = [];
		$fromAddress = '';

        $result = DSendMail::sendSmtp($to, $subject, $bodyHtml, null, $attachments, '', $fromAddress);

        if($result !== TRUE) {
			Log::err('Failed to send email. Error: ' . $result);
            return $this->invalidResult("Could not send email. The error was logged in the system error log.");
		}

        return $this->validResult(null, 'Email sent');

		/**
		* DSendMail::sendSmtp
		* Sends an email using PHPMailer (install with composer)
		* @param mixed $recipients  string containing one or more semicolon delimitted addresses,
		* 		        OR array of email addresses eg $recipients=array('joe@domain.com','...')
		* 			    OR $recipients=array('to'=>array('joe@domain.com','paul@examples.com', ...), 'cc'=>..., 'bcc'=>...)
		* @param string $subject  email subject
		* @param string $bodyHtml email html body
		* @param array $account  array of email account field values. If empty, the values are read from /config/Mail.php
		* @param array $attachments  array of paths to files, eg. array('/var/tmp/aaa.jpg','/var/tmp/bbb.jpg',...)  
		* 							      OR array(array('/var/tmp/aaa.jpg','aaa.jpg'), array('...','...'), ...)
		* @param string $plainTextBody  optional plain text email body
		* @param string $fromAddress  optional to override the account's from address
		* @param string $fromName  optional to override the account's from name
		* @param boolean $sendAltPlainTxt if $plainTextBody is empty, strip HTML tags from $bodyHtml and use that
		* @return mixed TRUE on success, or string $errMsg on error.
		*/
	}
}