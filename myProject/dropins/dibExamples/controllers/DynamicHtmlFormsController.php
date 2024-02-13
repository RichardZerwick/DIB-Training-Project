<?php

class DynamicHtmlFormsController extends Controller {

    // *** important for security ***
    // NOTE, this can be stored in db as JSON together with HTML ... 
    // allowed keys: type/min/max/minlength/maxlength/regex/default/userMsg
    // allowed types: stored in pef_validation_type table
    private $security = [
        'firstname' => [
            'type' => 'plaintext', // text data will be html-encoded
            'required' => true, // must at least match db field
            'maxlength' => 64, // ensure it will fit in db field
            'regex' => '/^[- \w]*$/',
            'default' => 'test default',
            'userMsg' => 'Required value, and must not be longer than 64 characters'
        ],
        'dob' => [
            'type' => 'date', // not html-encoded but tested to see if valid date in format yyyy-mm-dd
            'min' => '1920-01-01', // ***TODO THESE SHOULD BE CALCULATED VALUES
            'max' => '2005-01-01',
            'userMsg' => 'Enter a valid birthdate in yyyy-mm-dd format'
        ],
        'manager' => [
            'type' => 'boolean', // not html-encoded but tested to see if boolean (1/0/'1'/'0')
            'userMsg' => 'Not a valid value'
        ]
    ];
	
    public function getData ($containerName, $itemEventId='123', $clientData=array()) {
        // Get existing data if any from db and validate and escape it before sending to browser
        $data = [
            'firstname' => 'Peter',
            'dob' => '2000-02-19',
            'manager' => 1
        ];

        DibApp::load('dibDynamicUI', 'DDynamicUI.php', 'components');
        $htmlEncode = TRUE; // remove dangerouse characters injected in 'value' of html input field (default is TRUE)
        $setNullOnError = TRUE; // whether data that does not validate should be set to null before sending to browser (default is FALSE)
        $result = DDynamicUI::validateAndEncode($data, $this->security, $htmlEncode, $setNullOnError);

        if($result !== true) {
            Log::err('Data stored in database failed validation!');
            return $this->invalidResult('Error! Could not retrieve stored data');
        }
        
        $info = [
            'data'=>$data,
            'security'=> $this->security
        ];

		return $this->records($info);
	}

	public function save ($containerName, $itemEventId=123, $clientData=array(), $formData=array()) {
        // ***IMPORTANT*** - server validation before storing data
        if(empty($formData))
            return $this->invalidResult('There is nothing to save. Please fill in fields and try again');
        
        DibApp::load('dibDynamicUI', 'DDynamicUI.php', 'components');

        $result = DDynamicUI::validateAndEncode($formData, $this->security, FALSE);

        if($result !== true)
            return $this->invalidResult($result);
        
        // else store the data in the db... 
        

        return $this->validResult(null, 'Values stored successfully');
	}
	
}