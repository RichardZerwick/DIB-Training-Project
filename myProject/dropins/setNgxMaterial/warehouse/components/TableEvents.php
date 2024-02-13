<?php

/**
 * Processes events caught by table-level crud triggers in CrudController.php and Crud.php
 * Table-level crud events are specified in the pef_table.crud_events field. The following options are available:
 * 	bc(before create), ac(after create), bu(before update), au(after update), bd(before delete), ad(after delete)
 * Eg au,ad,bc
 * 
 * All containers linked to these tables are affected. 
 * Make sure that permfiles are regenerated after changes to the pef_table.crud_events field. The perm files store the trigger to activate crud events.
 * Note, sql queries that update tables without going through the Crud or the CrudController classes will not trigger this class.
 * This class must be positioned in the relevant containers' module dropin's components folder, or the parent folder of the components folder.
 * If a container is not linked to a module dropin, then the /runtime/crud or /runtime folders.
 */
class TableEvents {

    /**
     * Processes any table-level crud event directed to it
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
     * 
     * @return mixed TRUE on success, or array('error', $msg) if operation must be cancelled (message is returned to user)
     */
    public static function trigger($containerName, $trigger, $source, &$attributes, &$primaryKeyData, $databaseId, &$crudClass, &$crudResult) {
        /**
        * Ensure the primary Id is the one returned from the crud result
        */
        if (isset($crudResult['Id'])) {
            $attributes['Id'] = $crudResult['Id'];
        }
        if (file_exists(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR."events" . DIRECTORY_SEPARATOR . "tables" . DIRECTORY_SEPARATOR .$source.".php")) {
            include_once "events" . DIRECTORY_SEPARATOR . "tables" . DIRECTORY_SEPARATOR . $source.".php";
            $trigger = str_replace(" ",'',ucwords($trigger)); //converts after update -> afterUpdate 
            if (class_exists($source)) {
                
                $sourceObject = new $source();
                if (method_exists($sourceObject,$trigger)) {
                    $data = file_get_contents('php://input');
                    $data = PeffApp::jsonDecode($data);
                    return $sourceObject->$trigger($containerName,$attributes,isset($data['clientData'])? $data['clientData'] : null,$primaryKeyData );
                }
            }
        }
        return true;
    }
}