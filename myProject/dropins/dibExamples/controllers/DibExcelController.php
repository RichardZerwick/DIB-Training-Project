<?php

/**
 * Tutorial examples of common server-side File functions
 *   
 */

class DibExcelController extends Controller {

	// Private function used to display error / validation messages to user for 'redirect'/'new-tab' actions
	private function displayErrMsg($title, $msg) {
		$msg .= "<br>Closing this window/tab will return you to the application...";
		return "<h2>$title</h2>$msg";
	}
	
    // btnWriteTable on dibexPhpFilesDibExcel
	// NOTE the $REQUEST_TYPE parameter that is necessary to allow the redirect GET request, and ignore the absense of the Authentication Token Header
    public function writeTable($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {

		// Since 'redirect' / 'new-tab' expects a file or HTML in return, we need to handle the display of validation messages.
		if(empty($clientData['alias_self']['clientId']))
			return $this->displayErrMsg('Validation Error', 'Please first select a Client and try again.');
	
		$clientId = $clientData['alias_self']['clientId'];

		if($clientId != (string)(int)$clientId)
			return $this->displayErrMsg('Validation Error', 'Invalid request. Please refresh the browser and try again.');

		// Get some records
		$sql = "SELECT first_name, last_name, position, email, updated
				FROM test_client_contact
				WHERE client_id = $clientId";
		$records = Database::execute($sql);

		if($records === false)
			return $this->displayErrMsg('Database Error', 'There is a database error. Please check the error logs for details.');

		if(empty($records))
			return $this->invalidResult("There are no records to export. Please try again");
		
		$outputFolder = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR;
		$tmplFile = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'DibExcelTmpl.xlsx';
		$sheetName = 'Sheet1';

		DibApp::load('dibExcel', 'DibExcel.php', 'dibExcel');

		$xlsx = New DibExcel();

		// Make a copy of the template file and prepare it for editing
		$xlsxFile = $xlsx->prepareFile($tmplFile, $outputFolder);

		// Set $sheet as an handle to an existing sheet named $sheetName (in the above file)
		$sheet = $xlsx->getSheet($xlsxFile, $sheetName);

		// Move the "cursor" to cell A3 (default is A1)
		$xlsx->setPosition($sheet, 'A', 3);

		// Add the $records. Inherit styling for rows from row 3 in the template.
		$xlsx->addTable($sheet, $records, null, false, null, -3);

		/**
		 * public function addTable($sheetHandle, $records, $formulaFields=array(), $commitToDisk=FALSE, $nameFields=array(), $style=0, $styleColumns=FALSE)
		 * 
		 * @param int $sheetHandle reference to sheet to add array of values to (handle returned by getSheet/addSheet - note, first sheet is numbered 0)
		 * @param array $records 3-dimensional array (a list of records) containing data to insert
		 * @param array $formulaFields empty (no formulas), OR single character (eg '=') that indicates contents is a formula (without the character), OR an array of indexes/names of columns that must be written as formulas.
		 * @param boolean $commitToDisk whether the data is written directly to disk at the end of the function and released from memory.
		 * @param array $nameFields array of names/indexes of columns that must be named for referencing purposes. Names assigned as 'C_'.name/index.'_'.$recordCounter unless the array has non-integer keys in which case $key.'_'.$recordNumber is used.
		 * @param mixed $style mixed style-id (positive int) to apply to all cells being added, OR existing row no (negative int) that specifies the style for each added row, OR array of style-ids where 1st is aplied to 1st row, 2nd to 2nd row, etc. and last to the rest of the rows
		 * @param boolean $styleColumns whether $style is an array that applies to columns instead of rows
		 * @return mixed bool TRUE if success, string containing error on failure
		 */
		
		$xlsx->commitSheet($sheet);

		$fileName = uniqid('xlsxTest_') . '.xlsx';

		$xlsx->packageFile($xlsxFile, $fileName);

		// The exportFileToClient function sends the appropriate headers for us
		DibFunctions::exportFileToClient($outputFolder . $fileName, $containerName.'.xlsx'); 
	
		// Delete the temp file
		unlink($outputFolder . $fileName);
    }
	
	public function variousTasks($containerName, $itemEventId, $clientData=null, $REQUEST_TYPE='GET,ignoretoken') {

		// Since 'redirect' / 'new-tab' expects a file or HTML in return, we need to handle the display of validation messages.
		if(empty($clientData['alias_self']['clientId']))
			return $this->displayErrMsg('Validation Error', 'Please first select a Client and try again.');
	
		$clientId = $clientData['alias_self']['clientId'];

		if($clientId != (string)(int)$clientId)
			return $this->displayErrMsg('Validation Error', 'Invalid request. Please refresh the browser and try again.');

		// Get some records
		$sql = "SELECT first_name, last_name, position, email, updated
				FROM test_client_contact
				WHERE client_id = $clientId";
		$records = Database::execute($sql);

		if($records === false)
			return $this->displayErrMsg('Database Error', 'There is a database error. Please check the error logs for details.');

		if(empty($records))
			return $this->invalidResult("There are no records to export. Please try again");
		
		$outputFolder = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR;
		$tmplFile = DIB::$SYSTEMPATH . 'dropins' . DIRECTORY_SEPARATOR . 'dibExamples' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'DibExcelTmplVariousTasks.xlsx';
		$sheetName = 'Sheet1';

		DibApp::load('dibExcel', 'DibExcel.php', 'dibExcel');

		
		// Data arrays to be written in different positions
		$a = [
			['Product', 'Weight', 'Length', 'Description'],
			['Wrench', 1, 5, 'aaa'],
			['Pole', 100, 70, 'https://www.dropinbase.com'],
			['Sum:', '=Sum(B2:B3)', '=Sum(C2:C3)']
		];

		$b = [
			['Fruit', 'Qty1', 'Qty2'],
			['oranges', 10, 20],
			['apples' , 100, 200],
			['grapes' , 10, 500],
			['GRAND TOTAL', '=C_1_2 + C_2_2 + C_1_3 + C_2_3 + C_1_4 + C_2_4','']
		];

		$e = New DibExcel();

		// Make a copy of the template file and prepare it for editing
		$f = $e->prepareFile($tmplFile, $outputFolder);

		// Set $s to the existing sheet (in the above file) named $sheetName
		$s = $e->getSheet($f, $sheetName);

		// If an error occurs, a string will be returned instead of the integer file handle - echo the error and die 
		// Note, all functions return a either TRUE or a integer handle on success, and a string error message on failure
		// If a fatal error occurs, ANY subsequent function calls will return the error message instead of performing the function
		if($s != (string)(int)$s) {
			echo $s;
			die();
		}

		// Note the 5 functions below return either true on success or string error message on failure...

		// Add the $a data array in the default position A1. Specify that "=" indicates a formula. Provide styling for rows.
		$e->addTable($s, $a, '=', false, null, array(3,1,1,8));
		// Add another row following directly below the above table. Specify that columns 1 and 2 contain only formulas. Use style from row 10 on the dibStyleSheet (hidden) sheet
		$e->addTable($s, array(array('Average:', 'Average(B2:B3)', 'Average(C2:C3)')), array(1,2), false, null, 10);
		// Move the "cursor" to cell B8
		$e->setPosition($s, 'B', 8);
		// Add table $b. Specify that columns 1 and 2 must be named. Provide styling for rows.
		$e->addTable($s, $b, '=', false, array(1, 2), array(6,1,1,1,10));
		// Add a formula to cell E9 and use style 2
		$e->setContent($s, 'E', 9, 'sum(C9:D9)', true, false, 2);
		// Write 'hello there' in style 4 in C6
		$e->setContent($s, 'C', 6, 'hello there', false, false, 4);

		// Add new sheet with caption 'Sheetx'
		$s2 = $e->addSheet($f, 'Sheetx');
		$e->setPosition($s2, 'D', 4);
		$b[1][2] = 1000;
		// Add table and also flush sheet's contents to disk to release memory (note, flushed rows can't be modified)
		$e->addTable($s2, $b, '=', true, array(1, 2));

		// Create a named range on sheet $s2 (note name CAN refer to contents already flushed to disk)
		$e->addName($s2, 'maxRange', '$E$5:$E$6', FALSE);

		// Reference new name on $s2 on sheet $s
		$e->setContent($s, 'G', 2, 'sum(maxRange)', true, false, 5);
		$e->setContent($s, 'F', 2, 'Range Sum', false, false, 5);

		// Use addTableFast on sheet $s2 (optimzed for speed. Limitations: can only write to new rows and the naming feature is removed)
		$e->setPosition($s2, 'B', 10);
		$e->addTableFast($s2, $b, '=', false, array(3,1,1,1,10));

		// Add table in horizontal fashion to sheet $s 
		$e->setPosition($s, 'A', 15);
		$e->addTableHor($s, $b, '=', false, array(1, 2), array(6,1,1,1,10));

		// Commit data to disk and package the file
		$e->commitSheet($s2);

		$e->commitSheet($s);

		$fileName = uniqid('xlsxTest_') . '.xlsx';
		$e->packageFile($f, $fileName);

		// The exportFileToClient function sends the appropriate headers for us
		DibFunctions::exportFileToClient($outputFolder . $fileName, $containerName.'.xlsx'); 
	
		// Delete the temp file
		unlink($outputFolder . $fileName);
    }

	public function uploadFile($containerName, $itemEventId, $clientData=null) {
		$fileUpload = DibApp::clientData('alias_self', 'fileUpload');
		if(empty($fileUpload))
            return $this->invalidResult('First select a .xlsx file and try again.', 'dialog', 4000, null, 'stop');
        
		// Path defined in pef_file_setting (see Pg3 in Designer of item 'fileUpload' -> 'File Setting')
        $filePath   = DIB::$USERSPATH . 'uploads' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'Xlsx_' . DIB::$USER['unique_id'] . '.xlsx';
        if(!file_exists($filePath))
            return $this->invalidResult('First select a .xlsx file to import and try again.', 'dialog', 4000, null, 'stop');

		// We're using queues since large files can potentially take a long time to import if you are validating rows and writing the results to a table
		// NOTE: It is recommended to first write all the results to a temporary table with no limiting fields, 
		// and then perform validation on the records in the temporary table using queries with WHERE clauses

		// *** Note that we have limited files to a 1Mb size in this demo (see File Setting record),
		// so queues are unnecessary here, but we include the code as a demo for other applications.

		// Stop client from waiting for a result from the server (note, Queue polling will still continue to obtain client actions that update progress bar)
        $this->flushResult(null);
        
        // Avoid PHP from timing out
        set_time_limit(60 * 10); // (seconds)

		// Excel file reader
		DibApp::load('dibExcel', 'DibExcelReader.php', 'dibExcel');

		// Read the Excel file
		Queue::addMsg('Notice', "Reading xlsx file, please wait...", 'notice', 5000);

		$reader = new DibExcelReader($filePath, 1000);

		// The fetchRows function reads the next $countToRead rows from the Excel file (only until a non-existant row is found),
		// and then returns the rows in an array, or a string error message on failure

		// Read only the first 10 rows of 5 columns each.
		$result = $reader->fetchRows('Sheet1', 10, 'column', 5, FALSE);

		/**
		* public function fetchRows($sheet='Sheet1', $countToRead=1, $namingStyle='column', $columnCount=0, $useExcelRowNums=FALSE)
		*
		* @param mixed $sheet sheet name or number
		* @param int $countToRead count of rows to read (will terminate on non-existant row)
		* @param string $namingStyle cell naming style: 'cell' uses cell reference (eg A1), or 'column' uses column name (eg A, slightly better performance).
		* @param int $columnCount if 0 then empty columns will be omitted, else $columnCount columns will be returned for each row with NULL for empty cells
		* @param boolean $useExcelRowNums whether Excel row numbers are used, or rows are numbered sequentially as encountered.
		* @return array of records on success, string $errMsg on failure
		*/

		if(!is_array($result)) {
			// Error occurred
			Queue::addMsg('Import Result', $result, 'dialog', 4000, true, 'stop');
			return null;
		}

		if(empty($result)){
			// Error occurred
			Queue::addMsg('Import Result', 'Found nothing to import in the first 10 rows. Please try again.', 'dialog', 4000, true, 'stop');
			return null;
		}

		// Process rows, eg add them to a temporary table
		// We're just going to construct a HTML table for display

		$msg = 'Here are the first (up to) 10 rows x 5 columns of data (values &gt; 30 chars abbreviated):
				<br><br><table border="1" style="border: 1px solid lightgray; border-collapse: collapse;">';

		foreach($result as $no=>$row) {
			// Here you may send actions to the client to update a progress bar... 
			foreach($row as $key=>$value) {
				$row[$key] = substr((string)$value, 0, 30);
			}

			$msg .= '<tr><td>' . ($no + 1) . '</td><td>' . implode('</td><td>', $row) . '</td></tr>';
		}

		$msg .= '</table>';

		Queue::addMsg('Import Result', $msg, 'dialog', 4000, true, 'stop');

		return null;
	}


}