<?php

/**
 * Tutorial examples of common database server-side functions
 *   
 */

class DatabaseController extends Controller {
    
    public function databaseFunctions($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		
		// SEE /nav/dibexPhpDatabase for more information

		// *** Fetch one record from a table in the Dropinbase database
		
		$rst = Database::fetch("SELECT 1 + 2 AS answer, min(name) as minName, max(id) as MaxId
								FROM test_client");
		if($rst === FALSE)
			// It is a good idea to point the user to the System Administrator, as Dropinbase will store further details in the system error log
			return $this->invalidResult("System error! Please contact the System Administrator for more info.");
        elseif(count($rst)<1)
			return $this->invalidResult("Could not find any records in the test_client table. Please populate it and try again.");
        
        // Get a value
		$str = "1 + 2 = " . $rst['answer'] . "\r\n";
		$str .= "Min name : " . $rst['minName'] . "\r\n";
		
		// *** Fetch multiple records from the dropinbase database, use parameters, and loop through the records
		
		// WARNING! To avoid SQL injection, always use PDO parameters (eg :minName below) instead of 
		//   inserting $variables containing text that originates from users.
		// NEVER trust values originating from users or other parties
		
		$params = [
			'minName'=>$rst['minName']
		];
		$rst = Database::execute('SELECT id, name FROM test_client WHERE name > :minName LIMIT 5', $params);

		// *** NOTE: NEVER wrap PDO parameters in quotes in the SQL statement, which could cause errors and security issues.
		//  PDO will add quotes but itself where necessary. 
		
		$str .= 'Id values: ';
		foreach($rst as $key=>$record) {
			if(strlen($record['name']) > 4) {
				$str .= $record['id'];

				if(isset($rst[$key + 1])) $str .= ', ';
			}
		}
		
		// *** Another example of using parameters
		
		$params = [
			'newNotes' => 'testing', 
			'newEmail' => 'abc@example.com', 
			'id' => 100
		];
		$result = Database::execute("UPDATE test_client SET notes = :newNotes, email = :newEmail WHERE id = :id", $params);
		
		// *** Using another database

		// The third parameter can be the id value of a database in pef_database (which must also equal the index in /config/Conn.php)
		//  or the named of a container that is linked to a table (via pef_table_id) or SQL query (via pef_sql_id).
		// If no value is provided, DIB::DBINDEX (declared in Dib.php) is used.
		// DIB developers either create their own constant/variable in /configs/Dib.php (and /configs/DibTmpl.php), 
		// or use the DIB::LOGINDBINDEX constant which references the database containing the pef_login table.

		// If you are developing DIB modules that you which to distribute, it is advisable to either 
		// declare your own constants like CRMDB = 5 in Dib.php (and DibTmpl.php) and then use that throughout the code, or to use container names.

		// The use of a container name adds additonal permission checking (although its rarely necessary)
		//      Only users with permissions to open the container will be allowed to run the query. 
		//      Since users should never be allowed access to code they don't have permissions to, this safety measure is normally superfluous.
		//      If the container name changes, the related PHP code where the container name is used must also be updated.

		// NOTE the use of DIB::DBINDEX below is unnecessary since the default is DIB::DBINDEX... its only for demo purposes.
		
        $rst = Database::fetch("SELECT min(chinese_name) as minName FROM test_client", null, DIB::DBINDEX);
        if($rst === FALSE)
			return $this->invalidResult(Database::lastErrorUserMsg());

		if(empty($rst['minName']))
			return $this->invalidResult("Ooops there are no records with Chinese names :(");

        $str .= "\r\nChinese name 1: " . $rst['minName'];
        

		// *** Using Database::create, Database::update and Database::delete

		// Note, table field names containing spaces or other non-alphanumeric characters (except underscore(_)) could cause bugs. 

		$newName = uniqid('NewCo ', true);
		$params = array('name'=>$newName, 'chinese_name'=>'欣妍', 'notes'=>'xxx');

		// Again, the third parameter can be a database index or a container name
		$pkeyValue = Database::create('test_client', $params, DIB::DBINDEX); 


		$params = array('chinese_name'=>'123 欣妍', 'notes'=>'yyy');
		// The 4th parameter is a SQL WHERE clause
		$result = Database::update('test_client', $params, DIB::DBINDEX, "name = '$newName'");

		// To avoid SQL injection, it is best to use PDO parameters in criteria, ie :name, but then we must include `name` in $params too
		$params = array('name'=>$newName, 'chinese_name'=>'123 欣妍', 'notes'=>'yyy');
		$result = Database::update('test_client', $params, DIB::DBINDEX, 'name = :name');

		// Prefixing the key with # indicates that the value is not a constant, but a SQL expression (which can contain PDO parameters)
		// Prefixing the key with ! indicates that no attempt must be made to update this field, ie the parameter must not be included in the update. 
		//    The parameter will be used for criteria or expressions and is probably not a field name. The value in this case will always be treated as a constant.

		$suffix = 'some user value';
		$newName2 = uniqid('NewCo2 ', true);
		$params = [
			'name' => $newName2,
			'#notes' => "CONCAT(notes, ' *--', name, '--*')", // note the # prefix to indicate the value is not a constant
			'!suffix' => $suffix, // note the ! prefix, to exclude this parameter from any update operation
			'#chinese_name' => 'CONCAT(chinese_name, :suffix)', // note the # prefix and the use of :suffix
			'id' => $pkeyValue
		];

		// AUDIT TRAILS

		// The (optional) last parameter is used to specify the level of AUDIT TRAILING to apply (by default no audit trailing is done)
		//    detail - individual records for old and new values of each field that has changed is stored in the audit_trail for the given container
		//    summary - one record is used to store old and new values of each field that has changed
		//    basic - only the primary key value(s) is stored
		 
	    $result = Database::update('test_client', $params, DIB::DBINDEX, 'id = :id', 'summary');

		// NOTE, multiple records can be affected by Database::update and Database::delete actions - values changed in all will be captured in the audit trail

		// Let's delete that record (or any range of records defined by criteria)
		// Note we opt to store a single record in the audit trail for demo purposes
		// Also note that we can use the a database index OR a container name as 3d parameter (same as the Database::execute or Database::fetch commands)
		$result = Database::delete('test_client', array(':name' => $newName2), DIB::DBINDEX, 'name=:name', 'summary');

		// Since we have the primary key value we can also simply do this:
		$result = Database::delete('test_client', ['id' => $pkeyValue], DIB::DBINDEX);

        // *** Fetching records in a different PDO Style (default is PDO::FETCH_ASSOC)
        // See http://php.net/manual/en/pdostatement.fetch.php
        $rst = Database::execute("SELECT id FROM test_client LIMIT 5", null, DIB::DBINDEX, PDO::FETCH_OBJ);
        $str .= "\r\nMore Id's: ";

        foreach($rst as $key=>$record)
			$str .= $record->id . (isset($rst[$key+1]) ? ', ' : '');
		// Notes:
		// - the records are objects
		// - if there are no records, $rst contains an empty array and the foreach would merely be skipped.
		
		// *** Executing other SQL statements that do not start with the word 'SELECT'
		
		/* 
		  Note, if the first 6 characters of the SQL statement is 'SELECT' (case-insensitive), OR a $style is specified, then 
          records are returned using the $style format (by default it is PDO::FETCH_ASSOC - a 2-dim associative array).
          If these characters are 'INSERT' (case-insensitive) then the new primary key value is returned.
          Else the query is merely executed and TRUE or FALSE is returned (normally UPDATE or DELETE statements)
		  Consequently, to execute a stored procedure that returns records, specify a $style.
		*/
		
		// Use these statements to experiment with Database::count() (see below)
		$result = Database::execute("UPDATE test_client SET Notes = 'testing' WHERE id < 4");
		$str .= "\r\nRecords affected by the last statement: " . Database::count();

		// In many database engines, eg. MySQL, the Database::count() method will return the number of 
		// affected records of the last SELECT, INSERT, UPDATE and DELETE statement
		// WARNING. According to the PDO documentation, this function does however not work reliably for SELECT statements of all database engines.
		// See http://php.net/manual/en/pdostatement.rowcount.php
		// (DIB uses "SELECT @@Rowcount" for SQL Server SELECT statements to set Database::count() correctly).
		
		// It is therefore recommended to use one of the following validation tests where necessary when fetching records
		// if(empty($rst)) ... (perhaps return an error message here)
		// if(count($rst)<1) ... (perhaps return an error message here)
		
		
		
		// *** Executing data dictionary queries

		// Since these type of queries do not start with the word 'select', but do return records, 
		// we specify a $style to instruct the Database class to return the records.
		// Note, the DIB::$DATABASES array contains the array specified in Conn.php 
		
		if(DIB::$DATABASES[DIB::DBINDEX]['dbType'] === 'mysql')
			$result = Database::fetch("SHOW TABLE STATUS LIKE 'test_client'", array(), DIB::DBINDEX, PDO::FETCH_ASSOC);
		
		$str .= "\r\ntest_client meta data: ";
		foreach($result as $key=>$value)
			$str .= $key . '=>' . $value . ', ';
		
		$str = rtrim($str, ', ');


		// *** Working with transactions 

		// Transactions provide a convenient way to ensure that a batch of SQL statements all succeed 
		// before any changes are committed to the database, otherwise they are all rolled back.
		// The Dropinbase functions that import tables to create containers use transactions to ensure 
		// all records that constitute containers are successfully inserted before committing them.
		
		// Begin a transaction
		Database::transactionBegin(DIB::DBINDEX); // Note, this function accepts a database id or container name as parameter.
		
		// Here we can execute multiple SQL statements - if any of them return FALSE we rollback the transactions ...
		$result = Database::execute("UPDATE test_client SET Notes = 'testing' WHERE id=2", null, DIB::DBINDEX);
		
		if($result === FALSE) {
			Database::transactionRollback(DIB::DBINDEX);
			return $this->invalidResult("Could not update the test_client table.");
		}
		
		// etc... 
		// etc...
		// Note, at this point no changes were made in the database tables

		// If all is well, commit the transactions
		Database::transactionCommit(DIB::DBINDEX);

		// IMPORTANT NOTES:
		// - Using transactions could cause table/record locking issues, especially for long running SQL scripts, or high volumes of concurrent users.
		//   You may therefore need to write code to ensure only one user runs the queries at any given moment, or use temporary tables
		// - If any Database:: function returns FALSE while a transaction is active on the database (ie an error occurred), 
		//   the error will be logged in /runtime/logs/error.log and not in pef_error_log.
		

		// *** Working with PDO features of prepared statements

		// In a scenario where we transfer potentially thousands of records from one database to another,
		//   we would be executing the same queries multiple times, but with different parameter values.
		// In such cases the Database::stmt() function can be used to return a prepared statement 
		// Note, by default the Database class always uses prepared statements (even for a query executed only once)
		
		$params = array(':id'=>5);
		// The last parameter below is set to FALSE in order to prevent the return of records (ie merely prepare a statement).
		$rst = Database::execute("SELECT id, name FROM test_client WHERE id < :id", $params, DIB::DBINDEX, null, TRUE, FALSE);
		$str .= "\r\nRecords affected by the last statement: " . Database::count(); // Note, for some database engines, Database::count() does not work...
		
		// Get the statement
		$fetchStmt = Database::stmt(DIB::DBINDEX); // Note, this function accepts a database id or container name as parameter. DIB::DBINDEX is used as default.
		
		// Loop through the records 
		while ($row = $fetchStmt->fetch(PDO::FETCH_NUM)) {
			// Here we could use another prepared statement to INSERT records one by one, using the ->bindValue method of the statement.
			// As an example, see the TransferRecords function in /dropinbase/components/database/PDatabaseTools.php (still in Beta)
		}
		
		// Write the data collected in $str to a file 
		$path = DIB::$RUNTIMEPATH . 'tmp' . DIRECTORY_SEPARATOR . 'Testing Database Functions.txt';
		file_put_contents($path, $str);
		
		// If the event's reponse_type is 'actions' (which is the default), we must always send a response to the client API 
		//   that has been waiting (note only the first parameter is required below);
		// See the function comments for validResult and invalidResult in /dropinbase/system/Controller.php for more details
		return $this->validResult(NULL, "The result file, 'Testing Database Functions.txt', has been created in the /runtime/tmp folder.", 'dialog');
    }
    
    // Generated CRUD class functions
    public function crudFunctions($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {

		// SEE /nav/dibexPhpDatabase for more information

		// select ($containerName, $phpFilter=null, $phpFilterParams=array(), $sort=null, $pageSize=1000000, $page=1, $readType='gridlist', 
		//         $gridFilter=null, $activeFilter=null, $activeFilterParams=array(), $node=null, $countMode='all')
		
		// Get all records from dibexPhpGridCrud where id < 10, and sort results on name descending, and then on start_date ascending (just for demo purposes)
		// Note we use table name aliases in criteria (eg test_client.xxx) since other tables in the FROM clause may have fields with the same names, since
		// DIB includes eg joins to other tables referenced by foreign keys in the main table (to obtain display values)
		list($records, $count) = Crud::select('dibexPhpGridCrud', 'test_client.id < 10', array(), 'name=>DESC;start_date=>ASC');
		
		if($records === 'error')
			return $this->invalidResult ("Crud::select error: $count");
			
		// Loop through the records
		$str = "There are $count records:\r\n";
		foreach ($records as $key=>$record) {
			$str .= $key . ":\r\n";	

			// loop through fields in record
			foreach($record as $field=>$value)
				$str .= "'$field' = $value\r\n";
		}
		
		// Filter records using parameters, sort results on name, fetch only the first 5 records (of the 1st page), and use item captions as field names ('exportlist' format)
		$params = array(':id' => 20, ':name' => '%e%');
		$result = Crud::select('dibexPhpGridCrud', 'test_client.id < :id AND test_client.name LIKE :name', $params, 'name=>ASC', 5, 1, 'exportlist');
		
		// Note using $page and $pageSize, a loop can be constructed to process batches of records
		// This is especially useful when potentially thousands of records can cause a server to run out of memory 
		
		// Retrieve the first 1000 records where the id is between 5 and 100, and the name field contains 'e', using the same syntax that users use when filtering grid data
		// Note, table name prefixes must not be added to fields
		$params = array('id' => '>=5&<=100', 'name' => "*e*");
		$result = Crud::select('dibexPhpGridCrud', null, null, null, 1000, 1, 'gridlist', $params);
		
			
		// fetch ($containerName, $pkValues)
		
		// Fetch a specific record by primary key value
		$result = Crud::fetch('dibexPhpGridCrud', array('id'=>5));
		
		
		// fetchDefaults($containerName, $createParams = null, $clientData=array()
		
		// Fetch the crud defaults applicable to fields in a specific container, set the name field to 'new company name'
		$result = Crud::fetchDefaults('dibexPhpGridCrud', array('name'=>'new company name'));
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::fetchDefaults error: " . $result[1]);
		
		// create ($containerName, $newValues)

		// Create a new record - $result will contain an array of primary key values, eg array('id'=>200)
		// Note you must include values for all required fields that do not have defaults (except the primary key since it is auto-increment in this case)
		$newRecord = array('name'=>uniqid('test_'), 'chinese_name'=>'hmmm...');
		$newPk = Crud::create('dibexPhpGridCrud', $newRecord);
		if(isset($newPk[0]) && $newPk[0]==='error')
			return $this->invalidResult ("Crud::create error: " .$newPk[1]);
		
		// update($containerName, $pkValues, $values)
		
		// Update values in a record identified by primary key values 
		// Note you must include values for all required fields, including the primary key(s)

		$id = $newPk['id'];
		$params = [
			'id'=>$id, // required
			'name' => 'new client name', // required
			'notes'=>'hello', 
			'chinese_name'=>'new name'
		];

		$result = Crud::update('dibexPhpGridCrud', array('id'=>$id), $params);
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::update error: " . $result[1]);

		// duplicate($containerName, $pkValues, $setValues=array(), $targetDatabaseId=null) 
	    
	    // Creates a duplicate of a record - will only work where primary key is auto-increment or supplied in $setValues
	    // Duplicate record with specified primary key value, and set the name to a random string. 
	    $result = Crud::duplicate('dibexPhpGridCrud', array('id'=>$id), array('name'=> uniqid('test2'), 'notes'=>'duplicating id=' . $id));

    	if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::duplicate error: " .$result[1]);

		// delete($containerName, $pkValues)
		
		// Delete a record identified by primarykey values in $pkValues, eg array('id'=>200)
		$pkValues = $newPk;
		$result = Crud::delete('dibexPhpGridCrud', $pkValues);
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult ("Crud::delete error: " .$result[1]);


		// The following functions all have only one parameter: $containerName
			
		// Returns an array of primary key names used in the table the container is based on
		$result = Crud::getPrimaryKeys('dibexPhpGridCrud');
		
		// Returns an array of the container item's field names and types
		$result = Crud::getFieldTypes('dibexPhpGridCrud');
		
		// Returns an array of the container item's field captions and types
		$result = Crud::getCaptions('dibexPhpGridCrud');
		
		// Returns an array of the container sql field names and expressions
		$result = Crud::getSqlFields('dibexPhpGridCrud');
		
		// Returns an array of SQL clauses (eg select, from, where, group by) used to query the database. Also returns the crud class name.
	    $result = Crud::getSqlParts('dibexPhpGridCrud');
	    
		
		// Return a response to the client
		return $this->validResult(NULL, "Testing of CRUD function completed.<br><br><b>Sort the grid on 'id' DESC to view new record</b>", 'dialog');
		
    }
    
    // DibFunctions::getGridCritAndParams
    public function gridCriteriaAndParams($containerName, $itemEventId, $clientData = null, $triggerType = null, $itemId = null, $itemAlias = null) {
		
		// SEE /nav/dibexPhpDatabase for more information
		
		// Specify $filters to look at:
		$filters = array(
			'selected_self', // Records the user selected/checked in the grid
			'gridFilter_self', // Records the user filtered using the grid's header filters
			'activeFilter_self' // The subcontainer's filter (applicable only if the container has a filter specified -> value visible in the read request's payload in the browser's Console->Network)
		);
        
        // Note, the $criteria, $params, $table, and $fieldTypes variables are returned by reference - no need to initialize them first
        $result = DibFunctions::getGridCritAndParams('dibexPhpGridCrud', $clientData, $filters, $criteria, $params, $table, $fieldTypes);
       
        if($result !== true) {
			Log::err('Could not get criteria for grid. Error details: ' . $result);
        	return $this->invalidResult('Could not determine criteria. Please contact the System Administrator.');
		}
		
        // Get records  
        list($records, $filteredCount) = Crud::select('dibexPhpGridCrud', $criteria, $params);
        
        // Process records... 
        // Just post variables to the debug log (view in the Designer, or here: /runtime/Debug.txt)
        Log::w($records, 'Filtered Count: ' . $filteredCount, 'SQL Criteria generated: ' .$criteria, 'Parameters for SQL Criteria: ', $params);
        
        // Return a response to the client
        $msg = ($filteredCount == 1) ?  '1 record was retrieved' : $filteredCount . ' records were retrieved';
        return $this->validResult(NULL, 'Records and other details printed to the debug log. <br>' . $msg);
    }

}