<?php

/*
Demonstrates basic event functionality

NOTES:
Controller class names must always end in Controller.php, eg EventsController.php.
Functions must always use either the validResult() or invalidResult() functions
   of the Controller.php class to return a response to the client, that will be waiting,
   unless the event initiates the Queue with response_type being an integer.
It is therefore recommened to always extend the Controller class.

If there is nothing of value to return to the client, use:
   return $this->validResult(NULL);
*/

class EventsController extends Controller {

    // many places, eg dibexEventBasics.btnResponseActions
    public function helloWorld($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {        
        // NOTES:
        // Adding $containerName to the function parameters causes DIB to check that the user's permGroup has rights to the container,
        //   by checking for the existence of the container name in pef_perm_active for the user's permGroup (eg x1x).
        // Excluding containerName or giving it a default value in the function parameters will invalidate permission checking on container level.
        // The same applies for $itemEventId which causes DIB to check the pef_perm_active.item_event_detail field against the permGroup for this specific event.
        
        // Return an empty actionlist, and a message using style 'dialog' which is the default (available styles = success/notice/warning/dialog)
      return $this->validResult(NULL, 'Hello World!');
  
    }

    // afterViewInit on dibexEventBasics
    // Note, for a container event, the $containerName, $containerEventId parameters ensure permissions are checked to run this function.
    public function setHtml($containerName, $containerEventId) {
        $params = [
            'spanContEvent' => '<h3 style="color:crimson">The afterViewInit container event was triggered and set this message</h3>'
        ];

        ClientFunctions::addAction($actionList, 'set-span-html', $params, 'dibexEventBasics');
        return $this->validResult($actionList);
    }
    
    // btnActionCountClients on dibexEventBasics
    // Note, for an item event the $containerName, $itemEventId parameters ensure permissions are checked to run this function.
    public function countClients() {
        $rst = Database::fetch("SELECT count(*) as counter FROM test_client");
        $counter = (empty($rst)) ? 0 : $rst['counter'];

        return $this->validResult(null, "There are <span style='color:red;font-weight:bold'>$counter</span> clients");
    }

    // btnResponseRedirect on dibexEventBasics
    // Note last parameter: $REQUEST_TYPE='GET,ignoretoken' which allows this function to be called (only) by GET requests, like actions that expect file downloads or HTML content
    // Also see /nav/dibexPhpFilesEleutheria
    public function createDocx($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {

        // Extract value of id from clientData
        list($clientId) = DibApp::clientData('a_s', array('id'));

        // Ensure $clientId is integer to avoid SQL issues when merging docx.
        if(empty($clientId) || $clientId != (string)(int)$clientId)
            return $this->invalidResult("Invalid request, please navigate to an existing client record and try again");
        
        $debugMode = 0; // docx breaks with settings > 1
        $e = new Eleutheria(true, $debugMode);
        $tmpl = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'ClientMergeTmpl.docx';

        $outputFile = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid('docx_', true) . '.docx'; ;
        $outputToBrowser = true;
        $outputToMemory = false;
        $params = array('clientId'=>$clientId, 'name'=>DIB::$USER['first_name']);

        $result = $e->mergeTmpl('docx', $tmpl, $outputFile, $outputToBrowser, $outputToMemory, $params);

        return ($result === TRUE) ? $this->validResult(NULL) : $this->invalidResult('System error - see Error Log for deatils');
    }

    // redirectToHoliday on dibexEventBasics
    // Incase you need to change to another website in the same browser window...
    public function redirectToHoliday($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {
        header("Location: https://www.tripadvisor.co.za/Hotels-g312618-c3-zff17-Kruger_National_Park-Hotels.html");
        return null;
    }
   
    // getHtml on dibexEventBasics.btnResponseWindow
    // Note last parameter: $REQUEST_TYPE='GET,ignoretoken' which allows this function to be called (only) by GET requests, like actions that expect file downloads or HTML content
    public function getHtml($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {
        list($name) = DibApp::clientData('a_s', array('name'));

        // Always escape HTML echo'd to the server
        $name = htmlentities((string)$name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        // Let Dropinbase echo it to the client
        // (alternatively use echo() and return null)
        return "<h3>$name</h3> is the pick of the list.";
    }

    // btnActionCountClients on dibexEventBasics
    public function prank($containerName, $itemEventId) {
        return $this->invalidResult("Apologies.... this code was written on April fools day.");
    }


    // btnGreeting on dibexPlain
    // Note, for an item event the $containerName, $itemEventId parameters ensure permissions are checked to run this function.
    public function greetingColor($containerName, $itemEventId, $clientData=array(), $itemAlias='') {
        list($name, $color) = DibApp::clientData('alias_self', array('name', 'color'));

        // Since we send this info back to the browser, 
        // we strip any potentially malicious characters
        $name = preg_replace("/[^A-Za-z0-9\- ]/", '', $name);
        $color = preg_replace("/[^A-Fa-f0-9# ]/", '', $color);

        if(empty($name) || empty($color))
            return $this->invalidResult("First provide both a 'name' <i>(containing alpha-numeric characters and spaces only)</i>,<br>and a selected <span style=\"font-weight:700;font-size:15px;color:purple\">color</span> and try again");

        $msg = '<h2 style="color:' . $color . '">Hello ' . $name . ', I like colors<h2>';

        return $this->validResult(null, $msg, 'dialog');

    }

    // btnCalc on dibexOverview
    public function calcFormula($containerName, $itemEventId, $clientData=array(), $itemAlias='') {
        $formula = DibApp::clientData('a_s', 'formula'); // note, 'a_s' is short-hand for alias_self

        // strip any other characters
        $formula = trim(preg_replace("/[^0-9\-\+]/", '', $formula));

        $answer = 0;

        if(!empty($formula)) {
            // break the formula up in parts, using + as delimiter
            $parts = explode('+', $formula);

            foreach($parts as $p) {
                // only do addition if this part is an integer number
                if($p == (string)(int)$p)
                    $answer += (int)$p;
            }
        }

        $msg = 'The answer is <b style="color:blue">' . $answer . '</b>';

        return $this->validResult(null, $msg, 'dialog');
    }

    // btnGetActions on dibexOverview
    public function getActions($containerName, $itemEventId, $clientData=array(), $itemAlias='') {

        $sql = "SELECT count(*) as counter FROM test_client";
        $rst = Database::fetch($sql, null, DIB::DBINDEX);
        if($rst === false)
            return $this->invalidResult("Could not query database table 'test_client' that should be in the Dropinbase database.<br>Please check error log for details");
                
        $msg = 'There are <span style="color: blue; font-weight: 700">' . $rst['counter'] . ' records</span> in the <b>test_client</b> table';

        ClientFunctions::addAction($actionList, 'set-span-html', array('spanTarget' => $msg));
        ClientFunctions::addAction($actionList, 'run', array('action' => 'btnNow'));

        return $this->validResult($actionList);
    }

	public function eventTrigger($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        // Since we return the name of the event (never trust text passed from the client), we treat it as unsafe and sanitize it before returning the name... 
        // Note, Angular will also sanitize any value passed to it; but security is about layers...
        $triggerType = preg_replace("/[^A-Za-z0-9 ]/", '', $triggerType);
        return $this->validResult(NULL, "'$triggerType' fired!", 'dialog');
    }

    public function textfield_changed($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        // Note, both textfields call this function...
        // We cleverly use the alias of the textfields to get the value of the one which triggered the action...
        
        // Since hackers can change the automated $itemAlias value, and we're returning it to the client, let's validate it with a whitelist
        if(!in_array($itemAlias, array('Textfield1', 'Textfield2')))
            return $this->invalidResult("Invalid request");
    
        // We use the DibApp::getSubmitVal() function to return NULL if nothing was submitted
        // Note the abbreviation 'sIA.s' means 'submitItemAlias.self', 
        //    while 'sIA.p' means 'submitItemAlias.parent' and using only 'sIA.' will translate to 'submitItemAlias.'
        // Alternatively we could use: 
        //	  $value = isset($clientData['submitItemAlias.self'][$itemAlias]) ? $clientData['submitItemAlias.self'][$itemAlias] : NULL;
        // but this is nicer:
        
        $value = DibApp::getSubmitVal($clientData, 'sIA.s', $itemAlias);
        
        if(empty($value))
        	return $this->validResult(null, "Server-side function was called by '$itemAlias'. No value was supplied.", 'dialog');
        	
        // Angular comes with excellent built-in prevention of XSS attacks
        //    but should we want to allow only certain characters, we can validate or santize the string
        
        if(!DValidate::_string($value, ' '))
        	return $this->invalidResult("Invalid value supplied. The textbox may only contain alpha-numeric characters, underscore and spaces");
        	
        // Note, using invalidResult above serves no other purpose but to display a dialog message.
        // In other cases (such as crud events on trees) invalidResult instructs the client to 
        //    perform an alternative action than it normally would with a validResult.
        
        // Return an empty actionlist, and a message using style 'dialog' (the default style for validResult is 'notice' which displays for 3000 miliseconds)
        return $this->validResult(null, "Server-side function was called by '$itemAlias' with a value of '$value'", 'dialog'); 
    }   
    
    /***
      The following four functions demonstrate the setting of disabled, visible, style and class configurations
    */
     
	public function btnDisable_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {        
         
        ClientFunctions::addMethod($actionList, 
            array('view.Textfield1.disabled'=>TRUE,
                  'view.btnDisable.disabled'=>TRUE
            )
        );
        /***
            NOTES:
            - Targeted items must have Nav Item Alias checked! Any item (including layout components) can be targeted.
            - The last parameter, $containerName, is optional and is omitted above. It indicates where searching starts to find the item referenced by its name/alias. 
            - If $containerName is not provided, the current container is used. 
              This will only work if $containerName is a parameter in the controller function above (see declaration of btnDisable_click above in this case).
            - If the target container is loaded in a port with an alias (not the default port), then $containerName below should use the following format:
              CONTAINERNAME/PORTALIAS
        ***/

        return $this->validResult($actionList);
        
        // Note the following would also work, though invalidResult is intended for reporting errors in a popup dialog:
        // return $this->invalidResult(null, null, null, $actionList);
    }
    
    public function btnHide_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {        
        // Multiple calls to addMethod can also be used, but using a single call is more efficient ...

        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList, array('view.Textfield2.visible'=>FALSE));
        ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.visible'=>FALSE)); 
        
        return $this->validResult($actionList);
    }
    
    public function btnShowEnable_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList,
            array('view.Textfield1.disabled'=>FALSE,
                  'view.Textfield2.visible'=>TRUE,
                  'view.btnHelloWorld.visible'=>TRUE,
                  'view.btnDisable.disabled'=>FALSE
            )
        );
        
        return $this->validResult($actionList);
    }
    
    public function btnSetStyleClass_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        // Get random values to use 
        $height = rand(15, 50);
        $width = rand(100,200);
        $rnd = rand(0,5);
        $textAlign = array('top','right','bottom','left',''); // Note the last option: ''
        $classes = array('md-primary','md-custom-primary','md-accent','md-warn','md-cornered');
        $color = sprintf("#%02x%02x%02x", rand(0,255), rand(0,255), rand(0,255));
        
        if($rnd === 5) {
        	// Reset to default
        	$style = array();
        	$class = array(''=>false);	
        } else {
        	$style = array('height'=>$height.'px', 'width'=>$width.'px', 'text-align'=>$textAlign[$rnd]);
        	$class = array($classes[$rnd]=>true);
        }
        
        // See NOTES in btnDisable_click for more info
        ClientFunctions::addMethod($actionList,
            array('view.btnHelloWorld.style'=>$style,
                  'view.btnHelloWorld.class'=>$class,
                  'view.btnSetStyleClass.style'=>array('background-color'=>$color)
            )
        );
        
        // More options

        // ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.style'=>array() ));
       	// ClientFunctions::addMethod($actionList, array('view.btnHelloWorld.class'=>array('myClass' => false))); 
        
        return $this->validResult($actionList);
    }

    
    public function btnPopupYN($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        // The Yes and No responses can implement one of three action types:
        //   'itemAlias' - point to another (optionally hidden) item by its name/alias, and always execute the 'click' event
        //   'submitUrl' - execute a server- or client-side action, eg /dropins/dibExamples/Events/helloWorld
        //   'action' -  or specify a client-side action with parameters to execute (see $actionArray below)
        // The Yes action is required. If the No action is omitted, the button will show but simply close the dialog.

        $actionArray = [
            'submitUrl'=>'shared.action.set-value', 
            'params'=> ['setMyValue' => "You clicked Yes on the popup which set this value"]
        ];

        $params = ClientFunctions::addMsgPopup($actionList, 
            'A popup question', "Do you want to set values?<br>Click <b>No</b> to say 'Hello World' instead", 
            'action', $actionArray,       // Yes
            'itemAlias', 'btnHelloWorldHidden', // No
            array('yes'=>'Yes', 'no'=>'No') // Defaults are 'Yes' and 'Cancel'
        );

        return $this->validResult($actionList);

        /**
         * ClientFunctions::addMsgPopup
         * 
         * Add a Yes/No/Cancel message action to $actionList
         * @param array $actionList array passed by reference which will have new action added to it
         * @param string $title title of the message
         * @param string $text main text to display
         * @param string $yesActionType = submitUrl/action/itemAlias
         * @param mixed $yesAction = For 'submitUrl' - a server-side or client-side uri eg '/peff/DibTasks/DeleteCont'
         *         For 'action' - an array, eg array('submitUrl' => 'shared.action.set-value', 'params'=>array('first_name' => 'Tom'))
         *         For 'itemAlias' - the name of another item (normally an invisible button) of which the click event will be fired                   
         * @param string $noActionType = same as for yes. If omitted, the button will show and simply close the dialog.
         * @param int $noAction = same as for yes
         * @param bool $buttonText = lables for yes and no buttons. Default is array('yes'=>'Yes', 'no'=>'Cancel')
         *
         */
        
    } 

    public function promptQuestion($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
       	// Note, the prompt action is set to call this same event when the user provides a value. 
       	// The value is submitted in $clientData['prompt'] - so if this is empty, we know it's the first call
       	// Note prompts also handle the three action types mentioned for Yes/No popups above... 
       	
       	if(empty($clientData['prompt'])) {
        	ClientFunctions::addPrompt($actionList, 
                'Math test', "What is 1 + 2?", 
                'itemAlias', 'btnPrompt', 
                true // Use true to force an answer
            ); 
            
        	return $this->validResult($actionList);
        }
        
        $answer = $clientData['prompt'];
        
        If(trim($answer) !== '3' && trim($answer) !== 'three')
            return $this->invalidResult("I'm afraid you need some tuition!", 'warning', 4000);
        
        // Note, instead of using itemAlias (as in promptQuestion above), we specify an event url to execute helloWorld.
        // But since the helloWorld function has $itemEventId as a parameter, we are forced to send the dibuid value (if specified), or the pef_item_event.id value for it. 
        // This is for extra security in a scenario where certain item events on a specific container may not be executed by certain permgroups
		// If all permgroups that have access to the specific container's events, may execute any event on it, 
		//   then remove the $itemEventId from the function paramater list, and the coresponding dibuid value below.
		//   To avoid the hasssle, using itemAlias pointing to a hidden button is a simpler solution

        ClientFunctions::addPrompt($actionList, 
            'Math Test', "Well done! And what is 2 + 3? If you cancel you will start the quiz again, else the world will be greeted :)",
            'submitUrl', "/dropins/dibExamples/Events/helloWorld/$containerName/dib*kyjzvldx2", 
            false, // don't force an answer
            'itemAlias',
            'btnPrompt'
        );
        
        return $this->validResult($actionList);

        /**
         * ClientFunctions::addPrompt
         * 
         * Add a prompt action to $actionList, requesting an answer from the user 
         * @param array $actionList array passed by reference which will have new action added to it
         * @param string $title title of the message
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
    

    public function btnNextAction($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        // The addSubmitUrl action is used to set the next action to be called by the client
        // The optional $containerName parameter can be used to indicate where clientData is collected from
        // The optional $itemId parameter identifies an item with additional clientData configs (eg submitCheckedItem : 'myTree') to include
        
        // Note the use of the container name and dibuid in the url below that provides values required for permission checking
        ClientFunctions::addSubmitUrl($actionList, "/dropins/dibExamples/Events/helloWorld/$containerName/david*i_ev2995?itemAlias=exampleParameter");
        return $this->validResult($actionList, "First server-side call message. 'Hello World' will will follow in the next call to the server", 'success', 6000);
    }

    public function showSelected($containerName, $itemEventId, $clientData=null, $itemAlias=null) {
        if(empty($clientData['selected_self']))
            return $this->invalidResult('First select one or more records and try again');

        $selected = json_encode($clientData['selected_self'], JSON_PRETTY_PRINT);
        
        $clickedLast = json_encode($clientData['clickedLast_self'], JSON_PRETTY_PRINT);
        
        $msg = '<b>selected_self</b>: ' . $selected . '<br><br><b>clickedLast_self</b>: ' . $clickedLast;

        return $this->validResult(null, 'Selection Client Data=>'.$msg, 'dialog');
    }

        // ----------------------------------
    
    
    public function btnOpenUrl_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        // Open the dibexTestClientForm container, wait until it is open and data is loaded (the default behaviour of OpenUrl), 
        //   and append 'xxx' to the item with Alias 'varchar10'
        ClientFunctions::addAction($actionList, 'open-url', array('url'=>'/nav/dibexTestClientForm/popup'));
// DOES NOT WORK:
		ClientFunctions::addAction($actionList, 'set-value', array('notes'=>'xXx'));
        return $this->validResult($actionList);
    }
   

    public function containerEvents($containerName, $containerEventId, $clientData=null, $triggerType=null, $containerId = null) {
        // Note, all the container events on dibtestCompanyGrid call this function...
        // We use the $triggerType to determine which event called it... and merely append it to the eventNotices field contents.
      
        // Important to sanitize $triggerType before returning its value (since it comes from the client and may contain something malicious...)
        if(!in_array($triggerType, array('rowClick', 'postLink', 'onInit', 'reloadContainer', 'load')))
        	$triggerType = 'unknown!';
        	
        ClientFunctions::addAction($actionList, 'AppendValue', array('dibexEvents.containerEvents'=>"$containerName: $triggerType; "));

        return $this->validResult($actionList); 
    }

    //
    public function mdButton_click($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        list($client_pk) = DibApp::getSubmitVal($clientData, 'sIA.s', array('client_pk'));

        return $this->validResult(null, 'hello world', 'dialog');
    }

    // Location: Test Consulant Grid 
    public function btnHot_click($containerName, $itemEventId, $rowId=null, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        // Handle the 2nd button quickly...
        if($itemAlias === 'btnHotTheSecond')
            return $this->validResult(null, "I'm still here", 'dialog');

        // Handle the first button

        // Important validation check to prevent SQL injection ... 
        if(empty($rowId) || $rowId != (string)(int)$rowId)
            return $this->invalidResult('Are you hacking?');

        // IMPORTANT: 
        // if the user selected a row in the grid, 
        //    the last selected row's items with aliases are available in $clientData -> clickedLast
        //    This is NOT necessarily the values from the row of the button that was clicked!

        //    So when we work with buttons in rows, we normally rely on values configured in the URL:
        //    /dropins/dibExamples/Events/btnHot_click?rowId=@{actionData.row.id}

        // Get the name from the database
        $rst = Database::fetch("SELECT name FROM test_consultant WHERE id = :id", array(':id'=>$rowId));
        if(empty($rst))
            return $this->invalidResult("Eish... seems this record was deleted.");

        $name = $rst['name'];
        $msg = 'ROW VALUES';

        // Purge the name from any potentially malicious client code using a very safe whitelist
        $name = preg_replace("/[^A-Za-z0-9 ]/", '', $name);

        // Return the primary key value of the record in the grid that was clicked
        return $this->validResult(null, "$msg:<br><br>Hello $name<br>Your id is '$rowId'", 'dialog');
    }



}