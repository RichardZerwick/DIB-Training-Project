<?php

/* 
 * Copyright (C) Dropinbase - All Rights Reserved
 * This code, along with all other code under the root /dropinbase folder, is provided "As Is" and is proprietary and confidential
 * Unauthorized copying or use, of this or any related file, is strictly prohibited
 * Please see the License Agreement at www.dropinbase.com/license for more info
*/

/**
 * Table events (eg au,bu) are set in Designer / pef_table.table_events and caught in CrudController.php and Crud.php
 */
class TableEvents {

    /**
     * Manages/relays any table event directed to it to a class stored in /tableEvents/TABLENAME.php
     * Note the parameters sent by reference that can be updated
     * 
     * @param string $containerName name of the containerName involved
     * @param string $trigger one of: before create, after create, before update, after update, before delete, after delete
     * @param string $source the record source of the container: table name, or id of pef_sql record involved
     * @param array $attributes record field names and values
     * @param array $primaryKeyData primary key names and values
     * @param int $databaseId id of database involved - useful for calls to Database class
     * @param object $crudClass object pointing to container's crud class
     * @param mixed $crudResult result of crud operation (note, could be array('error', $msg) in case crud operation failed)
     * @param array $clientData Client Data associated with the container
     * 
     * @return mixed TRUE on success, or array('error', $msg) if operation must be cancelled ($msg is returned to user)
     */
    public static function trigger($containerName, $trigger, $source, &$attributes, &$primaryKeyData, $databaseId, &$crudClass, &$crudResult, $clientData) {
        
        // If an error occured, do nothing - the CrudController takes care of it
        if (isset($primaryKeyData[0]) && $primaryKeyData[0] === 'error')
            return TRUE;
            
        $permGroup = DIB::$USER['perm_group'];
        
        // *** Add any any code meant to be executed for all table events here ***
			
        // Pull in files which have the actual table event code in them for the current table
        
        $path = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'tableEvents'.DIRECTORY_SEPARATOR;

        if (file_exists($path.$source.'.php')) {
            include_once $path.$source.'.php';

            // convert eg after update -> afterUpdate 
            $trigger = str_replace(' ', '', ucwords($trigger)); 
            $trigger[0] = strtolower($trigger[0]);

            if (class_exists($source)) {
                $sourceObject = new $source();

                if (method_exists($sourceObject, $trigger))
                    return $sourceObject->$trigger($containerName, $attributes, $clientData, $primaryKeyData);
                    
                else {
                    Log::err("Table Event '$trigger' found the correct file '$path/$source.php' but no function named '$trigger' in it");
                    return array('error', 'Configuraiton error. Please contact a System Administrator');
                }

            } else {
                Log::err("Table Event '$trigger' found the correct file '$path/$source.php' but no class named '$source' in it");
                return array('error', 'Configuraiton error. Please contact a System Administrator');
            }
        }

        return true;
    }
	
}


