<?php

/**
 * Tutorial examples of common server-side File functions
 *   
 */

class FilesController extends Controller {
  
	/*
	// This particular event's response_type = 1000, which set client Queue requests in motion to handle to above DibExcelExport Queue actions.
		// The client is not waiting for a response from the server, but we do need to stop the client from polling the server for Queue actions.
		// Note: The various event response types are:
		//      'actions' - the client waits for a (in)validResult (see the databaseFunctions function below as an example)
		//      'redirect' - the client will accept headers to redirect to another url, or receive a file
		//      an integer value or 'stop' - initializes or stops the Queue - more about this later (see below)
		
		// Add the message to the Queue, and stop the client Queue from issuing further requests
		return Queue::addMsg('Message', $msgToSendToClient, 'dialog', 0, false, 'stop');

	*/

	// Private function used to display error / validation messages to user for 'redirect'/'new-tab' actions
	private function displayErrMsg($title, $msg) {
		$msg .= "<br>Closing this window/tab will return you to the application...";
		return "<h2>$title</h2>$msg";
	}

    // Convert user text to a PDF and return to client
	// NOTE the $REQUEST_TYPE parameter that is necessary to allow the redirect GET request, and ignore the absense of the Authentication Token Header
    public function textToPdf($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {
		/*	
		---	Returning files:
			The default response type for item and container events is 'actions' which causes the client to always
			wait for a JSON response. When the response type is 'redirect' the client will not expect a response,
			and headers can be sent to the client for other purposes, such as returning files for download.
			
		--- clientData
			With 'redirect', clientData cannot be posted... data needs to be sent in the URL.
			Ensure therefore that values that are included in clientData do not contain data that 
			would cause a client-side exception due to the length of the URL. 
			Use the "dibIgnore_" prefix to item names to exclude items from being included where needed.
		*/

		// Since 'redirect' / 'new-tab' expects a file or HTML in return, we need to handle the display of validation messages.
		if(empty($clientData['alias_self']['sendText']))
			return $this->displayErrMsg('Validation Error', 'Please first provide a value and try again.');
	
		$sendText = $clientData['alias_self']['sendText'];
		
		// Put $sendText in a PDF file and return it to the user
		
		// Since online users can add links to very large images etc, we strip html tags and use only the first 1000 characters.
		$sendText = strip_tags($sendText);
		$sendText = substr($sendText, 0, 1000);
		
		if(trim($sendText)==='') $sendText = '<h2>We may have stripped all that...</h2>';
		
		// Replace line feeds with <br>
		$sendText = str_replace(array("\r\n", "\r", "\n"), '<br>', $sendText);
		
		// Add HTML headers etc
		$sendText = '
		<html>
		<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>
		<body>' .
		$sendText
		. '</body>
		</html>
		';

		// load DPdf
		DibApp::load('dibPdf', 'DPdf.php', 'components');

		// Convert $sendTest to a PDF file, and post file to client
		DPdf::convertHtml($sendText, true, 'myPdf.pdf');
		
		/*
		// ALTERNATIVE ... store the HTML in a file and export it to the client...

		$fileName = uniqid('test_', TRUE) . '_' . Date("YmdHisu") . 'txt';
		$filePath = DIB::$RUNTIMEPATH . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $fileName;
	    
		file_put_contents($filePath, $sendText);
		
		// The exportFileToClient function sends the appropriate headers for us
		DibFunctions::exportFileToClient($filePath, $containerName.'.txt'); 
		
		// Delete the temp file
	    unlink($filePath);
	    */
    }

	public function simpleDocx($containerName, $itemEventId, $clientData = null) {
		$el = new Eleutheria();
		$data = [
			"aaa"=>'Some data', 
			'bbb'=>'... a little bit more'
		];
		$tmplFile = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR .'SimpleDocxTmpl.docx';
		$resultFile = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid('simpleDocx_', TRUE) . '.docx';

		$result = $el->mergeTmpl('docx', $tmplFile, $resultFile, FALSE, FALSE, $data);
		if($result !== TRUE)
			return $this->invalidResult('Unexpected Error. Please refresh the page and try again or view the error log for details.');
		
		return $this->validResult(null, 'The file was successfully created in the /runtime/tmp folder', 'dialog');
    }
	
	 //
	 public function buildDocxXslx($containerName, $itemEventId, $clientData=null, $itemAlias=null, $REQUEST_TYPE='GET,ignoretoken') {
        
		// Note $itemAlias contains the type of template being merged : docx / xlsx

		// Make sure no-one is hacking
		if($itemAlias !== 'docx' && $itemAlias !== 'xlsx')
			return $this->displayErrMsg('Unexpected Error', 'Please refresh the page and try again.');

		$tmplFile = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $itemAlias . 'Tmpl.' . $itemAlias;
		$resultFile = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . uniqid($itemAlias, TRUE) . DIRECTORY_SEPARATOR . 'test.' . $itemAlias;

		$params = array('profile_id'=>2, 'name'=>'John', 'path'=>DIB::$FILESPATH . 'icons' . DIRECTORY_SEPARATOR);

		$el = new Eleutheria();
		
		$result = $el->mergeTmpl($itemAlias, $tmplFile, $resultFile, TRUE, FALSE, $params);

		if($result !== TRUE)
			return $this->displayErrMsg('Unexpected Error', 'Please refresh the page and try again.');
		
		/**
		 * mergeTmpl($InputFormat, $TemplateFile, $OutputFile='', $OutputToBrowser=false, $OutputToMemory=false, $Parameters=array(), $cacheKey='', $configs='')	
		 *
		 * @params $InputFormat - One of 'html','text','htmlbody','textbody','docx','xlsx'
		 * @params $TemplateFile - If 'htmlbody' or 'textbody', then template content, else physical path to template file
		 * @params $OutputFile = '' - (Optional) Physical path to where result must be stored
		 * @params $OutputToBrowser = FALSE - Whether result must be sent to browser
		 * @params $OutputToMemory = FALSE - Whether result is kept in public class variables docHeader, docBody and docFooter after function exits
		 * @params $Parameters = array() - (Optional) Global merge parameters
		 * @params $cacheKey = '' - (Optional) Stores result in cache using $cacheKey
		 * @params $configs = '' - (Optional) Comma delimited string eg 'cj,bj,js'. Options: 'ca' (compress javaccript) 'cj' (compress json), 
		 *							'ph' (purify html with HtmlPurifier), 'bh' (beautify html), 'bj' beautify js with JsBeautifier
		*/
		
		// NOTE: We could set $OutputToBrowser to FALSE above, and then use the function below to specify a specific name for our download
		//DibFunctions::exportFileToClient($resultFile, 'testFile.' . $itemAlias);
	}
	
	//
    public function buildPdf($containerName, $itemEventId, $clientData=null, $itemAlias=null, $REQUEST_TYPE='GET,ignoretoken') {
        
		$tmplFile = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'pdfTmpl.html';

		$params = array('profile_id'=>2, 'name'=>'John');
		$el = new Eleutheria();

		// Export to memory so that we can get hold of the content
		$el->mergeTmpl('html', $tmplFile, '', FALSE, TRUE, $params);
		
		$content = $el->docBody[0];

		// Load DPdf 
		DibApp::load('dibPdf', 'DPdf.php', 'components');

		// Convert $content to a PDF file, and post file to client
		$result = DPdf::convertHtml($content, true, 'myPdf.pdf');
		/**
		 * public static function convertHtml($html, $exportToClient, $file)
		 * 
		 * Converts HTML to a PDF and saves the file to the supplied filename location, or sends the file to the client.
		 * 
		 * Input:
		 *    $html - The html to convert
		 *    $exportToclient - whether resulting pdf file must be streamed to the client or not 
		 *    $file - The physical location to save the PDF, OR (if $exportToClient=True) the name of the file to send to the client
		 * 
		 * Output:
		 *    true/false - If conversion was successful or not
		 */

		if($result === FALSE)
			return $this->displayErrMsg("Systerm Error", "Conversion to PDF failed, please contact the System Administrator for more info.");

	}   

}