<?php
/*
Demonstrates Container Events

NOTES: 
1. Function parameters vary according to trigger (event) as follows:
	before readone,  before readmany, before readoneinit, before readmanyinit:
    	$containerName, &$criteriaParams, $trigger, $gridFilter, &$criteria, $sql
    
    after readone,  after readmany: 
    	$containerName, $attributes, $trigger, $filterParams
    	
    before update: 
		$containerName, &$attributes, $trigger, $recordOld, $clientData
	
	after update: 
		$containerName, $attributes, $trigger, $recordOld, $validAttributes, $clientData
		
   	get defaults, before create, after create, before delete, after delete:
   		$containerName, &$attributes, $trigger, $clientData

2. Note that parameters such as $attributes and $validAttributes can be received by reference (eg &$attributes) which is useful on the 'before' and 'get defaults' events. 
   Values can be manipulated before storing/returning them.

3. To cancel execution of any further code in the calling Crud class: 
   return array('cancel', $message);
   where $message will be displayed to the user.
   Consequently, to change audit trail info being captured for a specific container, set $attributes on 'after create' or $validAttributes on 'after update'

4. Note, delete the corresponding Table Crud class with any change in pef_container_event, pef_container, pef_item etc. that may affect it. This will cause the
   Crud class code to be regenerated.
*/

class ContainerEvents extends Controller {
   
    public function afterUpdate($containerName, &$attributes, $trigger, $recordOld, &$validAttributes, $clientData) {  
    	// NOTE, data has already been altered.
        // If this were a beforeUpdate event, we could (conditionally) modify the contents of $attributes (passed by reference),
    	//       which would then change the values of the record being saved 	

        // Note, cancelling this event has no effect other than preventing audit trail info to be stored,
        // but it provides a way of displaying a message to the user.
        return array('cancel', "Server-side '$trigger' container event triggered.<br>Changes to the record has already been stored.");
    }

    public function beforeDelete($containerName, $attributes, $trigger, $clientData) {
        // We cancel the event by sending an array of the form array('cancel', $userMessage)
        return array('cancel', "'$trigger' event triggered. For demo purposes this event is being cancelled.");
    }
    
    public function afterReadMany($containerName, &$attributes, $trigger, $filterParams) {
        // NOTE, since we configured $attributes to be sent by reference,
        // we can (conditionally) modify the contents of $attributes, which would then change the values that are sent to the client      
        if(isset($attributes[3]))
        	$attributes[0]['name'] = "Container Events classified this info, if >3 records ;-)";
        
        // We'll include a notice for the user. 
        // By setting DibApp::clientMsg we set (and override) any other message that may have been configured using validResult(...)
        DibApp::setClientMsg("Container Events triggered to change values in the 'name' field", 'notice', 4000);
        
        // We can also send client actions by adding them to the DibApp::$clientActions array...
        ClientFunctions::addAction(DibApp::$clientActions, 'AppendValue', array('dibexEvents.containerEvents'=>"$containerName: $trigger (fired from Container Crud Events); "));
 
    } 
    
    public function getDefaults($containerName, &$attributes, $trigger, $clientData) {
        
        if($containerName === 'dibexDefaultValuesServer') {
            // Generate a  name
            $names = ['CoolBeans', 'Paupau', 'Miori', 'Smiles', 'Hot', 'UpNorth', 'Red', 'Greese'];
            $name = $names[rand(0, count($names)-1)] . 'Shop';

            // Find max number suffix used - provided no-one has tinkered with names...
            // No SQL injection possible since parameters orginate from here
            $sql = "SELECT REPLACE(name, '$name', '') as rest FROM test_client WHERE name LIKE '$name%' ORDER BY Length(rest) Desc, rest Desc LIMIT 1";
            $rst = Database::fetch($sql, null, DIB::LOGINDBINDEX);
            $name = (empty($rst['rest'])) ? $name : $name . ((int)$rst['rest']) + 1;
            $attributes['name'] = $name ;

        } 
        
        /*
        else {
            return array('cancel', "'$trigger' container event triggered. Cancelling Crud Defaults ...");
        }
        */
    }

    // after readmany event on /nav/dibexGridDynamicColumns
    public function dynamicColumnsData($containerName, &$attributes, $trigger, $filterParams) {

      //  Log::w($attributes, $trigger, $filterParams);

        // Get data for each of the clients, and each of the dynamic columns returned by the dibexDynamicColumnsList query
        $sql = "SELECT ts.id as staffId, REPLACE(CONCAT(ts.first_name, ts.last_name), ' ', '') AS `staffName`, tp.client_id, max(tss.yrs_of_exp) as maxYrsExp
                        FROM test_staff_skill tss
                        INNER JOIN test_staff ts ON tss.staff_id = ts.id 
                        INNER JOIN test_staff_project tsp ON tss.staff_id = tsp.staff_id 
                        INNER JOIN test_project tp ON tsp.project_id = tp.id
                        GROUP BY ts.id, tp.client_id, ts.first_name, ts.last_name";

        $rst = Database::execute($sql, null, DIB::LOGINDBINDEX);
        if($rst === false)
            return array('cancel', "Could not resolve values for dynamic columns. Please see error logs for more details");

        // Build array of staff names for each client
        $staffValues = [];
        $uniqueStaff = [];

        foreach ($rst as $record) {
            $clientId = $record['client_id'];
            $column = $record['staffName'];
            $staffValues[$clientId][$column] = (int)$record['maxYrsExp'];

            // Also get list of unique staff names since each row must have values for all staff, even if not linked to the client
            // We'll merge this array with attributs later
            if(!array_key_exists($column, $uniqueStaff))
                $uniqueStaff[$column] = 0;
        }

        // Loop through grid's records and add values for each of the dynamic columns for each client (row)
        foreach($attributes as $key => $record) {
            $clientId = $record['id'];
            $addStaffValues = (isset($staffValues[$clientId])) ? $staffValues[$clientId] : [];
            $attributes[$key] = array_merge($record, $uniqueStaff, $addStaffValues);
        }

        // Note, $attributes is returned by reference
    }

}