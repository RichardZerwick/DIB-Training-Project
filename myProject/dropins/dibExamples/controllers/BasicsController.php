<?php

/**
 * Basic Dropinbase server-side functionality
 *   
 */

/* NAMING RULES: 
  - Controller files must always end in the word 'Controller'
  - URLs in DIB that reference controller functions are of the form: 
		/dropins/DROPINNAME/CONTROLLER/FUNCTIONNAME
		eg /dropins/dibExamples/Basics/helloWorld
	or
		/dropins/SET/DROPINNAME/CONTROLLER/FUNCTIONNAME
		eg /dropins/setTest/Demo/Colors/showRed

    where CONTROLLER does not contain the word 'Controller' 
	
  -	The controller class must have the same name as the file (minus the .php extension)
*/
class BasicsController extends Controller {
    // NOTE, Controllers normally extend the basic Controller class in order to
    //   construct a appropriate JSON result using the validResult or invalidResult functions
	//   which can then be returned to the client.
    //   See below for more details...

	// btnGreeting on dibexPhpBasics
    public function greeting($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemAlias=null, $itemId=null) {
        // NOTES:
        // Adding $containerName to the function parameters causes DIB to check that the user's permGroup has rights to the container,
        //   by checking for the existence of the container name in pef_perm_active for the user's permGroup (eg x1x).
        // Excluding containerName or giving it a default value in the function parameters will invalidate permission checking on container level.
        // The same applies for $itemEventId which causes DIB to check the pef_perm_active.item_event_detail field against the permGroup for this specific event.
		// $clientData - values of items specified to be submitted to the server via the item_alias or configs fields.
		// $triggerType - event type, eg click/change/focus/etc
		// $itemAlias - the name of the item
		// $itemId - the id of the item
		// All the parameters are optional though $containerName and $itemEventId are normally specified to prevent access to the general public.
        
		// Extract values from $clientData array
		list($name) = DibApp::clientData('alias_self', array('name'));

		// Do some validation since we'll inject the name in HTML (see below)
		if(empty($name)) {
			// Events where the response_type is 'actions' must return JSON. 
			// The validResult and invalidResult functions are used to package the response.
			// Use the => operator to separate the title (if any)
			return $this->invalidResult('Error=>First provide a name in the input above, and try again.');
		}

		// HTML encode the $name to prevent hacking since we'll include it in HTML below
		$name = htmlspecialchars($name, ENT_NOQUOTES | ENT_HTML5, 'UTF-8', TRUE);
		
        // Return an empty actionlist (NULL below), and a message using style 'dialog' (available styles = success/notice/warning/dialog)
		// Messages can contain HTML tags
		// The encoding above has ensured that $name contains no possible malicious characters in HTML.
        return $this->validResult(NULL, "Greeting=>Hello <b>$name</b>", 'dialog');
    }

    // afterViewInit container event on dibexPhpBasics
    // Note, for a container event, the $containerName, $containerEventId parameters ensure permissions are checked to run this function.
    public function setHtml($containerName, $containerEventId) {
        $params = [
            'spanContEvent' => '<h3 style="color:crimson">The afterViewInit container event was triggered and set this message</h3>'
        ];

        ClientFunctions::addAction($actionList, 'set-span-html', $params, 'dibexPhpBasics');
        return $this->validResult($actionList);
    }


    
    // About Loading classes

	// btnLoadClasses on dibexPhpBasics
    public function loadClasses($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {

		// Classes in the /components folder (and its subfolders) of the current dropin are loaded automatically
		// using the framework's autoloader' as needed (note, require_once is used)
		// In other words, if the event url is /dropins/dibExamples/ANYCONTROLLERHERE then
		// the classes in /dropins/dibExamples/components and /dropins/dibExamples/components/xxx need not be required explicitly.

		// The Tests.php class in the /dropins/dibExamples/components folder will be loaded automatically:
		$msgToSendToClient = Tests::testMsg('To infinity and beyond');

		// The testMsg function will return an array with an error message if the string sent fails validation.
		// Note, style 'dialog' is used by default for messages returned with invalidResult.
		if(is_array($msgToSendToClient))
			return $this->invalidResult($msgToSendToClient[1]);
		
        // Classes in the Dropinbase core component and controller folers, eg the Database class, 
		// are also loaded automatically using the autoloader.
        // The file paths to these core files are stored in the /runtime/Components.php file, 
		// which is created automatically when not found.


		$rst = Database::execute("SELECT id, name FROM test_client LIMIT 20", null, DIB::DBINDEX);
		
		// Classes in other dropins can be loaded with the DibApp::load function
		// definition: DibApp::load($dropinName, $fileName, $subfolder='', $require=TRUE, $dontReportError=FALSE)
		DibApp::load('dibExcel', 'DibExcelExport.php', 'components');
		$e = New DibExcelExport();
		
		// DibApp::load can be used to merely get the physical path to a file in a dropin folder (without loading it)
		//   by setting the 4th parameter to FALSE
		$templateFile = DibApp::load('dibExcel', 'ExcelExportTemplate.xlsx', 'templates', FALSE);
		if($templateFile === FALSE)
			return $this->invalidResult("Could not load the default ExcelExportTemplate.xlsx template file. Please restore it and try again.");
        Log::w("The default Excel template file is stored in: " . $templateFile);

		// Using Log::w, you can print the values of multiple variables to the /runtime/logs/debug.log file (displayed in the Designer) - useful for debugging
		// eg Log::w($myObject, $myArray, $myInt, $etc);

		// Note, any PHP file, including controller files, can be loaded with DibApp::load ...
		// As mentioned, component files can be nested in folders. Nested component files in the current dropin don't need to be required explicitly, 
		// while nested component files in other dropins can be loaded using the following syntax:
		// DibApp::load('myOtherDropin', 'myOtherFile.php', 'components/subfolder1/subfolder2')


		// Since we've loaded this class, let's create a .xlsx file
		$filters = array('submitHeaderFilter');
		$data = array('name'=>'>b', 'id'=>'<=40');
		
		$excelFilePath = $e->exportContainer('dibtestCompanyGrid', $templateFile, $data, $filters, 'A1', 5000, true, false);
		// To export the file, use change the response_type to 'redirect' or 'new-tab' and use DibFunctions::exportFileToClient() function. See (nav/dibexPhpFilesDPdf)
		// Or set the last parameter to TRUE above, change the response_type of the event to eg. 1500 to start a queue, and implement other queue handling methods in this code (see /nav/dibexQueue).
		
		// The DibApp::loadPath function can be used when a Dropinbase path/url is present
		// defintion: DibApp::loadPath($dibUri, $require=TRUE, $functionNameRequired=FALSE, $fileExtension='.php', $subfolder='components')
		
		// The following example's 2nd parameter avoids loading the file, 
		// and the 3d parameter indicates there is no function name in the url
		$result = DibApp::loadPath("/dropins/dibCsv/CsvProcessor", FALSE, FALSE);
		Log::w('Result of DibApp::loadPath function:', $result);

		if($result[0]==='error')
			return $this->invalidResult("Could not find the /dropins/dibCsv/CsvProcessor class. Please restore it and try again.");

		$msgToSendToClient .= '<br><br>The class name is: <b>' . basename($result[0]) . '</b>';

		// And off course, functions in the same class are called as normal
		$msgToSendToClient = $this->makeRed($msgToSendToClient);
		
		// All good, send 'success' JSON object with no actions (null), and a message for the user
		return $this->validResult(null, $msgToSendToClient);
    }

	// Note, is is VERY important to make the following function 'private'!
	// Since it does not contain the $containerName function parameter, any URL that references this function is not checked against user permissions. 
	// If it were 'public' any un-authenticated (public) user could call this function as follows: /dropins/dibExamples/Php/makeRed?str=helloworld
	// AS A RULE: let all functions in controller classes contain the $containerName parameter, or make them private.
	// Even for public users, since they too would access this function using a button on a (named) container, 
	// and permissions can then be allocated to the Public Users permission group for that container.
	private function makeRed($str) {
		return '<span style="color:red">' . $str . '</span>';
	}

}