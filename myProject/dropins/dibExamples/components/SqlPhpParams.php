<?php

class SqlPhpParams {
    /**
     * Replace parts of SQL scripts, and return new SQL and associated $params array by reference
     * @param string $containerName name of affected container
     * @param array $params (passed by reference) SQL PDO parameters - adjust them as necessary
     * @param array $flatClientData values (array of flattened clientData values), eg alias_self_clientId
     * @param string $sql (passed by reference) the SQL statement that contains one or more references of the form {:/dropins/DROPIN/COMPONENTNAME/FUNCTIONNAME[, var]}
     * @param string $var any remaining string after the first comma in the reference to this function
     * @return mixed string value to replace {:/dropins...} reference with (eg '') OR array('error', $userErrorMessage)  
     */
    public static function replaceParams($containerName, &$params=array(), $flatClientData=array(), &$sql='', $var=null) {

        // This function will be executed just before a grid's main SQL function is run,  
        // where the grid's underlying SQL statement contains {:/dropins/dibExamples/SqlPhpParams/replaceParams}

        // This functionality is often used to add SQL to the FROM and WHERE clauses to specify permissions
        // Permissions can be based on data in tables, DIB::$USER['xxx'] values, specific container names, etc.
        // The advantage then is that permissions is managed in one place

        // Plan, and use this intelligently to manage your code

        // Log::w($containerName, $params, $flatClientData, $sql, $var); // <-- uncomment this line to see what comes in (/logs/debug.log)
        if(Date('Y-m-d') > '2100-01-01') {
            Log::err('An attempt to query data after the deadline has been made');
            return array('error', 'This data is no longer available');
        }

        if(isset($flatClientData['alias_self_clientId']) && (int)$flatClientData['alias_self_clientId'] < 7)
            return array('error', "This client's data is no longer available");

        if($containerName === 'dibexSqlParamsPhp') {
            $adminUser = 'AND :admin_user = 1';
            $params['admin_user'] = DIB::$USER['admin_user'];
            $mainBranch = "CONCAT('main-', c.name)";

        } else {
            $adminUser = 'AND 1=0';
            $mainBranch = "'none'";
        }

        // replace variables with specified values
        $find = ['__adminUser__', '__mainBranch__'];
        $replace = [$adminUser, $mainBranch];

        $sql = str_replace($find, $replace, $sql);
        
        return '';
    }

}