<?php 
    Class table_register_x2x {
    public $count = 0;
    public $newPkValue = null; // for INSERTS it will contain lastInsertId
    public $pkeys = 'id';
    // List of PDO field types for fields from this table, parent tables, or items based on 'sql insert' expressions
    public $fieldType = array("id"=>PDO::PARAM_INT, "login_group_expiry"=>PDO::PARAM_STR, "default_url"=>PDO::PARAM_STR, "username"=>PDO::PARAM_STR, "last_login_datetime"=>PDO::PARAM_STR, "pef_security_policy_id"=>PDO::PARAM_INT, "password_history"=>PDO::PARAM_STR, "login_attempts"=>PDO::PARAM_INT, "language"=>PDO::PARAM_STR, "reset_expiry"=>PDO::PARAM_STR, "first_name"=>PDO::PARAM_STR, "email"=>PDO::PARAM_STR, "login_lockout"=>PDO::PARAM_STR, "last_name"=>PDO::PARAM_STR, "mobile_number"=>PDO::PARAM_STR, "login_blocked_count"=>PDO::PARAM_BOOL, "larger_font"=>PDO::PARAM_BOOL, "login_expiry"=>PDO::PARAM_STR, "email"=>PDO::PARAM_STR, "password"=>PDO::PARAM_STR, "test_user"=>PDO::PARAM_BOOL, "password_expiry"=>PDO::PARAM_STR, "password_remember_expiry"=>PDO::PARAM_STR, "password_remember"=>PDO::PARAM_STR, "password_remember_hash"=>PDO::PARAM_STR, "dib_username"=>PDO::PARAM_STR, "supplier_code"=>PDO::PARAM_STR, "created_datetime"=>PDO::PARAM_STR, "reset_guid"=>PDO::PARAM_STR, "activation_guid"=>PDO::PARAM_STR, "notes"=>PDO::PARAM_STR, "dibuid"=>PDO::PARAM_STR);
    // List of component store types of fields from only this table, else declare them as dibsqli fields
    public $storeType = array("id"=>'none', "login_group_expiry"=>'none', "default_url"=>'none', "username"=>'none', "last_login_datetime"=>'none', "pef_security_policy_id"=>'none', "password_history"=>'none', "login_attempts"=>'none', "language"=>'none', "reset_expiry"=>'none', "first_name"=>'none', "email"=>'none', "login_lockout"=>'none', "last_name"=>'none', "mobile_number"=>'none', "login_blocked_count"=>'none', "larger_font"=>'none', "login_expiry"=>'none', "email"=>'none', "password"=>'none', "test_user"=>'none', "password_expiry"=>'none', "password_remember_expiry"=>'none', "password_remember"=>'none', "password_remember_hash"=>'none', "dib_username"=>'none', "supplier_code"=>'none', "created_datetime"=>'none', "reset_guid"=>'none', "activation_guid"=>'none', "notes"=>'none', "dibuid"=>'none');
    // List of items based on 'sql insert' expresions
    public $sqlFields = array();
    public $fkeyDisplay = array(
            );
    protected $filterArray = null;
    protected $now = null;
    protected $ipAddress = null;
    protected $entityFieldName = 'id';
    protected $dbIndex = null;

    function __construct() {
        $this->dbIndex = (DIB::LOGINDBINDEX != DIB::DBINDEX) ? DIB::LOGINDBINDEX : DIB::$CONTAINERDATA['db_id'];

        $dbClassPath = (DIB::$DATABASES[$this->dbIndex]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
        set_time_limit(180);
    }
    /**
     * parses array $filterParams and converts to a SQL WHERE clause string and PDO parameters
     */
    private function parseFilterArray($filterParams=array(), &$params=array(), &$fieldType=array()) {
        if (count($filterParams) === 0) 
            return '';

        $i = 0;
        $str = '';
        foreach($filterParams as $record) {
            if(!array_key_exists('value', $record) || !array_key_exists('property', $record) )
                return array('error',"Filter criteria was submitted in a wrong format. Please contact the System Administrator.");
            $field = $record['property'];
            $value = $record['value'];
            if(!array_key_exists($field, $this->fieldType))
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            if(array_key_exists($field, $this->sqlFields))
            	$str .= $this->sqlFields[$field] . ' = :pk' . $i . ' AND ';
			else
            	$str .= '`pef_login`.`' . $field . '` = :pk' . $i . ' AND ';
            $params[':pk' . $i] = $value;
            $fieldType[':pk' . $i] = $this->fieldType[$field];
            $i++;
        }
        return substr($str, 0, strlen($str) - 4);
    }
    /**
	 * Returns sql criteria string and related pdo parameters and parameter types, given an activeFilter name
	 * @param string $activeFilter name of filter
	 * @param array $params empty array to be populated with pdo parameter values
	 * @param array $filterParams associative array of activeFilter parameter values, e.g. array('notes'=>'%aaa%', 'id'=>5)
	 * 
	 * @return string - sql criteria string, and $params via referencing
	 */ 
    public function parseFilter($activeFilter, &$params=array(), &$filterParams=array()) {
		$criteria = ''; 
        if($criteria==='') {
            Log::err("The read request for container register specified an active filter by name that could not be resolved in the generated crud code.\r\nTab or container names were probably updated but code was not regenerated. Please regenerate code and try again.");
            return  array('error',"Error! The named active filter could not be found. Please contact the System Administrator.");
        }
        return substr($criteria, 4);
    }
    /**
     * Fetches the primary key values of the next, previous, etc. records for use with the Form's toolbar
     */
    public function getToolbarInfo($pkValues, $activeFilter, $filterParams, $getFirstOnly=FALSE) {        
        $params = array();   
        $criteria = '';
        $permsCrit = '';

        if(!empty($activeFilter)) {
            $criteria = $this->parseFilter($activeFilter, $params, $filterParams);
            if(isset($criteria[0]) && $criteria[0]==='error')
                return $criteria;
            $criteria = " AND $criteria";

            $fromClause = "`pef_login`
                ";
        } else  
            $fromClause = "`pef_login`  ";
        $criteria .= $permsCrit;
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);

        if($getFirstOnly) { // Used after deletes to navigate to first record
            $sql = "SELECT `pef_login`.`id` FROM $fromClause $criteria ORDER BY `pef_login`.`id` limit 1";
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if($rst === FALSE)
                return array('error', 'Could not get first record. Please contact the System Administrator. (#0).');

            return $rst;
        }
        // Get total, first and last
        $sql = "SELECT count(*) as dib__total, min(`pef_login`.`id`) as dib__minid, max(`pef_login`.`id`) as dib__maxid FROM $fromClause $criteria";
        $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
        if(empty($rst))
            return array('error', 'Could not set form navigation counts. Please contact the System Administrator. (#1).');
        $totalCount = $rst['dib__total'];
        $first = array('id' => $rst['dib__minid']);
        $last = array('id' => $rst['dib__maxid']);

        if($totalCount>0) {
            // Add pkeys to $params
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key field names specified in the request are invalid.");        
            if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;    
            // Get prev and current            
            $tempCrit = ($criteria === '') ? "WHERE `pef_login`.`id` < :pk1 $permsCrit" : "$criteria AND `pef_login`.`id` < :pk1";
            $sql = "SELECT max(`pef_login`.`id`) as dib__id, count(`pef_login`.`id`) as dib__counter FROM $fromClause $tempCrit";
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if(empty($rst))
                return array('error', 'Could not set form navigation counts. Please contact the System Administrator. (#2).');
            $prev = array('id' => $rst['dib__id']);
            $currentNo = (int)$rst['dib__counter'] + 1;

            // Get next
            $tempCrit = ($criteria === '') ? "WHERE `pef_login`.`id` > :pk1 $permsCrit" : "$criteria AND `pef_login`.`id` > :pk1";
            $sql = "SELECT min(`pef_login`.`id`) as dib__id FROM $fromClause $tempCrit";
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if(empty($rst))
                return array('error', 'Could not set form navigation counts. Please contact the System Administrator. (#3).');
            $next = array('id' => $rst['dib__id']);
        } else {
            $first = null;
            $currentNo = 1;
            $last = null;
            $prev = null;
            $next = null;
        }
        return array(
            'next' => $next,
            'prev' => $prev,
            'first' => $first,
            'last' => $last,
            'current' => array('current'=>$currentNo),
            'total' => array('total'=>$totalCount)
        );       
    }
    /**
     * Fetches the primary key values of the nth record
     */
    public function getToolbarRecord($position, $activeFilter, $filterParams) {
        $position = (int)$position;
        if(!$position || $position < 0)
            return array('error', 'Position must be a positive integer');
        if($position < 1) $position = 1;
        $params = array();   
        $criteria = '';

        $permsCrit = '';

        if(!empty($activeFilter)) {
            $criteria = $this->parseFilter($activeFilter, $params, $filterParams);
            if(isset($criteria[0]) && $criteria[0]==='error')
                return $criteria;
            $criteria = " AND $criteria";
            $fromClause = "`pef_login`
                ";
        } else  
            $fromClause = "`pef_login`  ";

        $criteria .= $permsCrit;
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);
        // Template: SQL statement for MySql to fetch nth record for the Toolbar on Forms. Used in eg Table.php.
$sql = "SELECT `pef_login`.`id` 
        FROM $fromClause
        $criteria
        ORDER BY `pef_login`.`id` 
        LIMIT " . ($position - 1) . ', 1';
        $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
        if(empty($rst))
            return null;
        return $rst ;
    }
    /**
     * parses $gridFilter and returns a SQL WHERE clause string, and PDO parameters (passed by reference)
     */
    public function parseGridFilter($gridFilter, &$params=array(), &$fieldType=array(), &$usedSqlField=FALSE, &$i = 0) {
        //Eg [{"property":"name","value":"g & >e"},{"property":"notes","value":"< z &  w"}]
        $allCrit = '';

        $mainConjunction = '';
        $conjunction = ' AND ';

        foreach($gridFilter as $row) {
            // check if $row is sequential array, if so parse recursively and encapsulate in brackets (Usage example: calendarComponent)
            if (array_keys($row) == range(0, count($row) - 1)) {
                $parsed = $this->parseGridFilter($row, $params, $fieldType, $usedSqlField, $i);
                if(is_array($parsed))
                    return $parsed; // error is thrown. Result should be string
                $allCrit .= $mainConjunction . '(' . $parsed. ')';
                continue;
            }

        	if(!array_key_exists('property', $row) || !array_key_exists('value', $row))
        		return array('error',"Filter criteria was not submitted in correct format. Please contact the System Administrator.");
        	$field=$row['property'];
            $value=trim((string)$row['value']);
            if($value === null || $value === '') continue;
            if(!array_key_exists($field, $this->fieldType))
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            // Handle sql expression fields
            if(array_key_exists($field, $this->sqlFields)) {
            	$fieldExpr = $this->sqlFields[$field];
            	$usedSqlField = TRUE; // Indication that SELECT Count(*) for filteredcount must use all table joins in FROM clause
            } else
            	$fieldExpr = "`pef_login`.`$field`";
            $subparts = explode ('&', str_replace('|', '|&', $value));
            $fieldCrit = '';

            $allCrit .= $mainConjunction;
            $mainConjunction = ' AND ';
            foreach($subparts as $stringValue)  {
                $stringValue = trim((string)$stringValue);
                if($stringValue === ''){
                    if($conjunction === ' OR ') $mainConjunction = $conjunction;
                	continue; // return array('error', "The filter criteria syntax is incorrect. Please try again.");        	
                }
                if ($fieldCrit !== '')
                    $fieldCrit .= $conjunction; //$conjunction is found in prev. loop
                if (substr($stringValue, -1) === '|') {
                    $conjunction = ' OR ';
                    $stringValue = trim(substr($stringValue, 0, strlen($stringValue) - 1));
                } else
                    $conjunction = ' AND ';
                $intValue = trim($stringValue, '=!>< ');
                if ($intValue === '') 
                	continue; // return array('error', "The filter criteria syntax is incorrect. Please try again.");
        		//dropdowns
        	    if($this->storeType[$field] === 'dropdown') {
					$fieldCrit .= "$fieldExpr = :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
				}
				//is null
				elseif (strtolower(substr($stringValue, 0, 4)) === "null") {
                    $fieldCrit .= "$fieldExpr IS NULL";                
                }
                //is not null
                elseif (strtolower(substr($stringValue, 0, 6)) === "<>null") {
                    $fieldCrit .= "$fieldExpr IS NOT NULL";                
                }
                //is empty
                elseif (strtolower(substr($stringValue, 0, 5)) === "empty") {
                    $fieldCrit .= "$fieldExpr = ''";                
                }
                //is not empty
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>empty") {
                    $fieldCrit .= "$fieldExpr <> ''";                    
                }
                //not like
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>like ") {
                    $fieldCrit .= "$fieldExpr NOT LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 7)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = PDO::PARAM_STR;
                }
                //equal to
                elseif (substr($stringValue, 0, 1) === "=" || (strtolower(substr($stringValue, 0, 5)) === "like " && $this->fieldType[$field] == PDO::PARAM_INT) ) {
                    $fieldCrit .= "$fieldExpr = :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //not equal to
                elseif (substr($stringValue, 0, 2) === "<>") {
                    $fieldCrit .= "$fieldExpr != :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //greater and equal
                elseif (substr($stringValue, 0, 2) === ">=") {
                    $fieldCrit .= "$fieldExpr >= :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //greater than
                elseif (substr($stringValue, 0, 1) === ">") {
                    $fieldCrit .= "$fieldExpr > :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //smaller and equal
                elseif (substr($stringValue, 0, 2) === "<=") {
                    $fieldCrit .= "$fieldExpr <= :f" . $i;
                    $params[':f'.$i] = $intValue;
                }
                //smaller than
                elseif (substr($stringValue, 0, 1) === "<") {
                    $fieldCrit .= "$fieldExpr < :f" . $i;
                    $params[':f'.$i] = $intValue;
                    $fieldType[':f'.$i] = $this->fieldType[$field];
                }
                //like
                elseif (strtolower(substr($stringValue, 0, 5)) === "like ") {
                    $fieldCrit .= " $fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 5)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = PDO::PARAM_STR;
                }
                //anything else - use LIKE
                else {
                    $fieldCrit .= "$fieldExpr LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', $stringValue).'%'; //note, this allows user to put % or _ inside $stringValue... which is okay...
                    $fieldType[':f'.$i] = PDO::PARAM_STR;
                }
                $i++;
            }              
            if ($fieldCrit !== '')
                $allCrit .= '(' . $fieldCrit . ') ';
        }
        return $allCrit;
    }
    /**
     * Fetch records, returning only one page at a time
     * @param int $page page number to return
     * @param int $page_size count of records on each page
     * @param array $order sorting order, eg array(array('property'=>$field, 'direction'=>'ASC'), array('property'=>$field2, 'direction'=>'DESC'));
     * @param string $readType 'gridlist' = use table field names, 'exportlist' = use captions from pef_item fields
     * @param array $gridFilter grid header filter values, eg array(array('property'=>$field, 'value'=>$value), array('property'=>$field2, 'value'=>$value2));
     * @param string $activeFilter name of the filter to apply
     * @param array $filterParams activeFilter parameter values, eg array('notes'=>'%aaa%', 'id'=>5). If $order === '*readByPk*' then primary key values eg array('id'=>5)
     * @param string $phpFilter extra criteria to add (eg 'item_id = :id'). $phpFilterParams handles parameter values
     * @param array phpFilterParams associative array with parameter values for $phpFilter
     * @param array $group TODO grouping functionality
     * @param string $node fetch tree child nodes of $node
     * @param string $action ExtJs - inline grid adding
     * @param array $actionData ExtJs - inline grid adding
     * @param string $countMode TODO - all=do total count and filtered count on all pages, first=do counts on first page only     
     * @return array array($attributes, $filteredCount, $totalCount, $summaryData)   OR   array('error', $errMsg) on failure
     */
    public function read ($page=1, $page_size=20, $order=array(), $gridFilter=array(), $filterParams=array(), $activeFilter=null, $phpFilter=null, $phpFilterParams=array(), $group=null, $readType='gridlist', $node=null, $action=null, $actionData=null, $countMode='all') {
        if(empty($page) || $page<=0) $page = 1;
        if(empty($page_size) || $page_size<0) $page_size = 20;
        try {
            if ($order === '*readByPk*') {
                //Request for record by primary key values
                $params = array();
                if (!array_key_exists("id", $filterParams))
                    return array ('error',"The primary key fields specified in the request are invalid.");
                 if ($filterParams["id"] != (string)(float)$filterParams["id"])
                    return array ('error',"Error! The primary key field values specified in the request are invalid.");
                $params[":pk1"] = $filterParams["id"];
                $fieldType[":pk1"] = PDO::PARAM_INT;
                $criteria = "`pef_login`.`id` = :pk1 ";
                $sql = "SELECT `pef_login`.`id`,`pef_login`.`username`,`pef_login`.`first_name`,`pef_login`.`last_name`,`pef_login`.`password`,`pef_login`.`login_group_expiry`,`pef_login`.`last_login_datetime`,`pef_login`.`password_history`,`pef_login`.`language`,`pef_login`.`email`,`pef_login`.`mobile_number`,`pef_login`.`larger_font`,`pef_login`.`test_user`,`pef_login`.`password_expiry`,`pef_login`.`password_remember_expiry`,`pef_login`.`password_remember`,`pef_login`.`password_remember_hash`,`pef_login`.`dib_username`,`pef_login`.`supplier_code`,`pef_login`.`created_datetime`,`pef_login`.`reset_guid`,`pef_login`.`activation_guid`,`pef_login`.`notes`,`pef_login`.`dibuid`,`pef_login`.`default_url`,`pef_login`.`pef_security_policy_id`,`pef_login`.`login_attempts`,`pef_login`.`reset_expiry`,`pef_login`.`login_lockout`,`pef_login`.`login_blocked_count`,`pef_login`.`login_expiry` 
                         FROM `pef_login` 
                        ";
                $sql .= "WHERE $criteria";

                if(strpos($sql, '{:/') !== FALSE) { 
                    // needed for forms with dropdown queries with {:/...} sections. 
                    $sql = $this->resolvePhpParam($sql, $params, $filterParams);
                    if($sql === FALSE) 
                        return array('error', "Error! Could not obtain data. Please contact the System Administrator");
                }
                dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
                $attributes = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true); // true -> return one record only - sometimes joins return multiple records...
                if($attributes === FALSE || count($attributes) < 1) 
                    return array('error',"Error! Could not find the requested record. Please refresh the record to determine if another user perhaps deleted it, otherwise contact the System Administrator.");
                $filteredCount = 1;
                $totalCount = 1; 
            } else { // ---------------------------------- Fetch many records ---------------------------------

                // Permission filter
                $fieldType = array();
                $criteria = "";
                $join = "";
                $params=array();
                // php generated / developer filter
                if($phpFilter) {
                    if (!empty($phpFilterParams) && !is_array($phpFilterParams)) {
						Log::err("The \$phpFilterParams parameter is not an array.");
						return array('error', "The data cannot be retrieved. Please contact the System Administrator.");
					} else                    	
                    	$params = $phpFilterParams;
                    $criteria .= " AND ($phpFilter)";
                }
                // Related Records Filter ***TODO see note in CrudController... this must be cleaned up!
                if (isset($filterParams[0])) {
                    $crit = $this->parseFilterArray($filterParams, $params, $fieldType); // $params passed by reference to be populated
                    if(is_array($crit))
                        return $crit; #error occured; $criteria contains error message.
                    else
                        $criteria .= " AND ($crit) ";
                }
                // Get total count of records user has access to
                if($page === 1 || $countMode==='all'){
                    $sql = "SELECT Count(*) AS totalcount FROM `pef_login`   " . (!empty($criteria) ? 'WHERE ' . substr($criteria, 4) : '');
	                $totalCountRst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
	                $totalCount = $totalCountRst["totalcount"];
	                $totalCountRst = null;
                } else
                	$totalCount = null; 
                // user grid filters
                $userFilter = '';
                if ($gridFilter) { 
                    $userFilter = $this->parseGridFilter($gridFilter, $params, $fieldType, $usedSqlField); // $params passed by reference to be populated                
                    if(is_array($userFilter))
                        return $userFilter; //error occured; $crit contains error message.
                } else
                	$usedSqlField = false;
                $filteredCount = $totalCount;
                if(!empty($userFilter)) $criteria .= " AND ($userFilter) ";
                if ($criteria !== '') {
                    $criteria = ' WHERE ' . substr($criteria, 4);
                    if($usedSqlField || !empty($activeFilter)) {
						$join = "
                                 ";
                 	} else 
                 		$join = '';
                    if($page === 1 || $countMode==='all'){
                        $sql = "SELECT Count(*) AS filteredcount FROM `pef_login` $join   $criteria";
	                    dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);                          
	                    $itemCountRst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
	                    $filteredCount = $itemCountRst['filteredcount']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	                    $itemCountRst = null;
					}
                }

                $orderStr = '';
                if($order) {
                    $orderStr = " ORDER BY ";
                    foreach($order as $key => $record) {
                        if(!isset($record['property']) || !array_key_exists($record['property'], $this->fieldType)) 
                            return array('error', "An invalid fieldname was used in order criteria. Please contact the System Administrator.");
                        if(isset($record['direction'])) {
                            if (!in_array($record['direction'], array('ASC', 'DESC', '')))
                                return array('error', "An invalid sort direction was used in order criteria. Please contact the System Administrator.");
                            else
                                $direction = $record['direction'];
                        } else
                            $direction = '';
                        if(array_key_exists($record['property'], $this->sqlFields))
                        	$orderStr .= $this->sqlFields[$record['property']] . ' ' . $direction . ', ';
                        elseif(array_key_exists($record['property'], $this->fkeyDisplay))
                        	$orderStr .= $this->fkeyDisplay[$record['property']] . ' ' . $direction . ', ';
                        else 
                        	$orderStr .= '`pef_login`.`' . $record['property'] . '` ' . $direction . ', ';

                    }
                    $orderStr = substr($orderStr, 0, strlen($orderStr) - 2);
                }                
                // Fetch records - handle only specific columns that may be viewed by this permgroup
                // Set SQL statement
                    // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg Table.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    

                // Template: main SQL statement for MySQL to fetch many records limited by paging. Used in eg Table.php.

if($readType === 'exportlist')
    $sql = "SELECT 
                `pef_login`.`id` AS `Id`, `pef_login`.`username` AS `Username`, `pef_login`.`first_name` AS `First Name`, `pef_login`.`last_name` AS `Last Name`, `pef_login`.`password` AS `Password`, `pef_login`.`login_group_expiry` AS `Login Group Expiry`, `pef_login`.`last_login_datetime` AS `Last Login Datetime`, `pef_login`.`password_history` AS `Password History`, `pef_login`.`language` AS `Language`, `pef_login`.`email` AS `Email`, `pef_login`.`mobile_number` AS `Mobile Number`, `pef_login`.`larger_font` AS `Larger Font`, `pef_login`.`test_user` AS `Test User`, `pef_login`.`password_expiry` AS `Password Expiry`, `pef_login`.`password_remember_expiry` AS `Password Remember Expiry`, `pef_login`.`password_remember` AS `Password Remember`, `pef_login`.`password_remember_hash` AS `Password Remember Hash`, `pef_login`.`dib_username` AS `Dib Username`, `pef_login`.`supplier_code` AS `Supplier Code`, `pef_login`.`created_datetime` AS `Created Datetime`, `pef_login`.`reset_guid` AS `Reset Guid`, `pef_login`.`activation_guid` AS `Activation Guid`, `pef_login`.`notes` AS `Notes`, `pef_login`.`dibuid` AS `Dibuid`, `pef_login`.`default_url` AS `Default Url`, `pef_login`.`pef_security_policy_id` AS `Pef Security Policy`, `pef_login`.`login_attempts` AS `Login Attempts`, `pef_login`.`reset_expiry` AS `Reset Expiry`, `pef_login`.`login_lockout` AS `Login Lockout`, `pef_login`.`login_blocked_count` AS `Login Blocked Count`, `pef_login`.`login_expiry` AS `Login Expiry`, `pef_login`.`email` AS `Email` 
            FROM `pef_login` 
                 ";
else
    $sql = "SELECT `pef_login`.`id`,`pef_login`.`username`,`pef_login`.`first_name`,`pef_login`.`last_name`,`pef_login`.`password`,`pef_login`.`login_group_expiry`,`pef_login`.`last_login_datetime`,`pef_login`.`password_history`,`pef_login`.`language`,`pef_login`.`email`,`pef_login`.`mobile_number`,`pef_login`.`larger_font`,`pef_login`.`test_user`,`pef_login`.`password_expiry`,`pef_login`.`password_remember_expiry`,`pef_login`.`password_remember`,`pef_login`.`password_remember_hash`,`pef_login`.`dib_username`,`pef_login`.`supplier_code`,`pef_login`.`created_datetime`,`pef_login`.`reset_guid`,`pef_login`.`activation_guid`,`pef_login`.`notes`,`pef_login`.`dibuid`,`pef_login`.`default_url`,`pef_login`.`pef_security_policy_id`,`pef_login`.`login_attempts`,`pef_login`.`reset_expiry`,`pef_login`.`login_lockout`,`pef_login`.`login_blocked_count`,`pef_login`.`login_expiry` 
            FROM `pef_login` 
                 ";

$sql .= $criteria . " " . $orderStr . $limit; 
                dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
                $attributes = dibMySqlPdo::execute($sql, $this->dbIndex, $params, false);
                if($attributes === FALSE) {
                	if($gridFilter){
                		foreach($params as $key=>$p)	
                			$criteria = str_replace($key, "'$p'", $criteria);
                		return array('error',"Error! Could not read table information. Verify the filter applied or else please contact the System Administrator.\r\n$criteria");
                    } else
                    	return array('error',"Error! Could not read table information. Please contact the System Administrator.");
                }
            // Get values where dropdowns are based on queries based on other db's...
            }
            return array($attributes, $filteredCount, $totalCount, array());
        } catch (Exception $e) {
            return array('error',"Error! Could not read table information. Please contact the System Administrator");
        }
    }    
     /**
     * Inserts a record
     * 
     * @param array $attributes
     * @param boolean $makeUniqueValues - if True, then values will be made unique (if not) using SyncFunctions::cleanName function.
     * @param int $targetDatabaseId - if specified, record is created in the target database (must have the same table structure).
     * @return type
     */
    public function create(&$attributes, $makeUniqueValues=FALSE, $targetDatabaseId=NULL, $clientData=array()) {
        // User can only provide values for fields they have rights to AND where ci.crud_include<>0. 
    	// The rest must get default values - prevent user from updating them, even if provided...
    	// So if (crud_include=0 OR user lacks permissions) AND there is a default, then set the default
    	//    else if no default AND field is required, then give an error msg 
    	//    else if field is not required, unset it.
        if(!$targetDatabaseId) $targetDatabaseId = $this->dbIndex;
        try { 
            // Add defaults where permissions restricts user to provide values, or value missing due to crud_include or not included in container 
             if(isset($attributes["id"])) unset($attributes["id"]);
             if(isset($attributes["dibuid"])) unset($attributes["dibuid"]);
            // Check Validation
	        // username (plain text)
            if(isset($attributes['username']) && trim((string)($attributes['username'])) !== '') {
                if(strlen($attributes["username"]) > 30)
                    return array ('error',"The 'Username' field cannot contain more than 30 characters");
            }  else
                return array ('error',"The 'Username' field is required. Please provide a value.");
	        // first_name (plain text)
            if(isset($attributes['first_name']) && trim((string)($attributes['first_name'])) !== '') {
                if(strlen($attributes["first_name"]) > 50)
                    return array ('error',"The 'First Name' field cannot contain more than 50 characters");
            }  else
                return array ('error',"The 'First Name' field is required. Please provide a value.");
	        // last_name (plain text)
            if(isset($attributes['last_name']) && trim((string)($attributes['last_name'])) !== '') {
                if(strlen($attributes["last_name"]) > 50)
                    return array ('error',"The 'Last Name' field cannot contain more than 50 characters");
            }  else
                return array ('error',"The 'Last Name' field is required. Please provide a value.");
	        // password (plain text)
            if(isset($attributes['password']) && trim((string)($attributes['password'])) !== '') {
                if(strlen($attributes["password"]) > 150)
                    return array ('error',"The 'Password' field cannot contain more than 150 characters");
            }  else
                return array ('error',"The 'Password' field is required. Please provide a value.");
	        // login_group_expiry (datetime)
            if(isset($attributes['login_group_expiry']) && trim((string)($attributes['login_group_expiry'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["login_group_expiry"])))
                    return array ('error',"The 'Login Group Expiry' field must be a valid date-time.");
                else
                    $attributes["login_group_expiry"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["login_group_expiry"])));
            }               
	        // last_login_datetime (datetime)
            if(isset($attributes['last_login_datetime']) && trim((string)($attributes['last_login_datetime'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["last_login_datetime"])))
                    return array ('error',"The 'Last Login Datetime' field must be a valid date-time.");
                else
                    $attributes["last_login_datetime"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["last_login_datetime"])));
            }               
	        // password_history (plain text)
            if(isset($attributes['password_history']) && trim((string)($attributes['password_history'])) !== '') {
                if(strlen($attributes["password_history"]) > 65535)
                    return array ('error',"The 'Password History' field cannot contain more than 65535 characters");
            }               
	        // language (plain text)
            if(isset($attributes['language']) && trim((string)($attributes['language'])) !== '') {
                if(strlen($attributes["language"]) > 25)
                    return array ('error',"The 'Language' field cannot contain more than 25 characters");
            }               
	        // email (email)
            if(isset($attributes['email']) && trim((string)($attributes['email'])) !== '') {
                if(!filter_var($attributes["email"], FILTER_VALIDATE_EMAIL))
                    return array ('error',"The 'Email' field must be a valid email address.");
                if(strlen($attributes["email"]) > 120)
                    return array ('error',"The 'Email' field cannot contain more than 120 characters");
            }  else
                return array ('error',"The 'Email' field is required. Please provide a value.");
	        // mobile_number (plain text)
            if(isset($attributes['mobile_number']) && trim((string)($attributes['mobile_number'])) !== '') {
                if(strlen($attributes["mobile_number"]) > 50)
                    return array ('error',"The 'Mobile Number' field cannot contain more than 50 characters");
            }               
	        // larger_font (boolean)
        	$attributes['larger_font'] = (!isset($attributes['larger_font']) || !in_array($attributes['larger_font'], array(1, True, '1', 'True'), TRUE)) ? 0 : 1;            	
	        // test_user (boolean)
        	$attributes['test_user'] = (!isset($attributes['test_user']) || !in_array($attributes['test_user'], array(1, True, '1', 'True'), TRUE)) ? 0 : 1;            	
	        // password_expiry (datetime)
            if(isset($attributes['password_expiry']) && trim((string)($attributes['password_expiry'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["password_expiry"])))
                    return array ('error',"The 'Password Expiry' field must be a valid date-time.");
                else
                    $attributes["password_expiry"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["password_expiry"])));
            }               
	        // password_remember_expiry (datetime)
            if(isset($attributes['password_remember_expiry']) && trim((string)($attributes['password_remember_expiry'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["password_remember_expiry"])))
                    return array ('error',"The 'Password Remember Expiry' field must be a valid date-time.");
                else
                    $attributes["password_remember_expiry"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["password_remember_expiry"])));
            }               
	        // password_remember (plain text)
            if(isset($attributes['password_remember']) && trim((string)($attributes['password_remember'])) !== '') {
                if(strlen($attributes["password_remember"]) > 250)
                    return array ('error',"The 'Password Remember' field cannot contain more than 250 characters");
            }               
	        // password_remember_hash (plain text)
            if(isset($attributes['password_remember_hash']) && trim((string)($attributes['password_remember_hash'])) !== '') {
                if(strlen($attributes["password_remember_hash"]) > 250)
                    return array ('error',"The 'Password Remember Hash' field cannot contain more than 250 characters");
            }               
	        // dib_username (plain text)
            if(isset($attributes['dib_username']) && trim((string)($attributes['dib_username'])) !== '') {
                if(strlen($attributes["dib_username"]) > 25)
                    return array ('error',"The 'Dib Username' field cannot contain more than 25 characters");
            }               
	        // supplier_code (plain text)
            if(isset($attributes['supplier_code']) && trim((string)($attributes['supplier_code'])) !== '') {
                if(strlen($attributes["supplier_code"]) > 6)
                    return array ('error',"The 'Supplier Code' field cannot contain more than 6 characters");
            }               
	        // created_datetime (datetime)
            if(isset($attributes['created_datetime']) && trim((string)($attributes['created_datetime'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["created_datetime"])))
                    return array ('error',"The 'Created Datetime' field must be a valid date-time.");
                else
                    $attributes["created_datetime"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["created_datetime"])));
            }               
	        // reset_guid (plain text)
            if(isset($attributes['reset_guid']) && trim((string)($attributes['reset_guid'])) !== '') {
                if(strlen($attributes["reset_guid"]) > 255)
                    return array ('error',"The 'Reset Guid' field cannot contain more than 255 characters");
            }               
	        // activation_guid (plain text)
            if(isset($attributes['activation_guid']) && trim((string)($attributes['activation_guid'])) !== '') {
                if(strlen($attributes["activation_guid"]) > 255)
                    return array ('error',"The 'Activation Guid' field cannot contain more than 255 characters");
            }               
	        // notes (plain text)
            if(isset($attributes['notes']) && trim((string)($attributes['notes'])) !== '') {
                if(strlen($attributes["notes"]) > 255)
                    return array ('error',"The 'Notes' field cannot contain more than 255 characters");
            }               
	        // dibuid (plain text)
            if(isset($attributes['dibuid']) && trim((string)($attributes['dibuid'])) !== '') {
                if(strlen($attributes["dibuid"]) > 40)
                    return array ('error',"The 'Dibuid' field cannot contain more than 40 characters");
            }               
	        // default_url (plain text)
            if(isset($attributes['default_url']) && trim((string)($attributes['default_url'])) !== '') {
                if(strlen($attributes["default_url"]) > 250)
                    return array ('error',"The 'Default Url' field cannot contain more than 250 characters");
            }  else
                return array ('error',"The 'Default Url' field is required. Please provide a value.");
	        // pef_security_policy_id (integer)
            if(isset($attributes['pef_security_policy_id']) && trim((string)($attributes['pef_security_policy_id'])) !== '') {
                if(!is_int((int)$attributes["pef_security_policy_id"]) || !ctype_digit((string)abs((int)$attributes["pef_security_policy_id"])))
                    return array ('error',"The 'Pef Security Policy' field must be an integer value.");
                if($attributes["pef_security_policy_id"] < -2147483647)
                    return array ('error',"The 'Pef Security Policy' field value must be greater than or equal to -2147483647");
                if($attributes["pef_security_policy_id"] > 2147483647)
                    return array ('error',"The 'Pef Security Policy' field value must be less than or equal to 2147483647");
            }  else
                return array ('error',"The 'Pef Security Policy' field is required. Please provide a value.");
	        // login_attempts (integer)
            if(isset($attributes['login_attempts']) && trim((string)($attributes['login_attempts'])) !== '') {
                if(!is_int((int)$attributes["login_attempts"]) || !ctype_digit((string)abs((int)$attributes["login_attempts"])))
                    return array ('error',"The 'Login Attempts' field must be an integer value.");
                if($attributes["login_attempts"] < -32767)
                    return array ('error',"The 'Login Attempts' field value must be greater than or equal to -32767");
                if($attributes["login_attempts"] > 32767)
                    return array ('error',"The 'Login Attempts' field value must be less than or equal to 32767");
            }               
	        // reset_expiry (datetime)
            if(isset($attributes['reset_expiry']) && trim((string)($attributes['reset_expiry'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["reset_expiry"])))
                    return array ('error',"The 'Reset Expiry' field must be a valid date-time.");
                else
                    $attributes["reset_expiry"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["reset_expiry"])));
            }               
	        // login_lockout (datetime)
            if(isset($attributes['login_lockout']) && trim((string)($attributes['login_lockout'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["login_lockout"])))
                    return array ('error',"The 'Login Lockout' field must be a valid date-time.");
                else
                    $attributes["login_lockout"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["login_lockout"])));
            }               
	        // login_blocked_count (boolean)
        	$attributes['login_blocked_count'] = (!isset($attributes['login_blocked_count']) || !in_array($attributes['login_blocked_count'], array(1, True, '1', 'True'), TRUE)) ? 0 : 1;            	
	        // login_expiry (datetime)
            if(isset($attributes['login_expiry']) && trim((string)($attributes['login_expiry'])) !== '') {
                if(!strtotime(str_replace('/', '-', $attributes["login_expiry"])))
                    return array ('error',"The 'Login Expiry' field must be a valid date-time.");
                else
                    $attributes["login_expiry"] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $attributes["login_expiry"])));
            }               
            //Check Unique Values for table option 556
            $criteria = '1=1';
            if(!array_key_exists('dib_username', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `dib_username` is in the design of container with id 372. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 589. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `dib_username` = :fk1";
            $sql = "SELECT `pef_login`.`id` AS pkv FROM `pef_login` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["dib_username"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 556 are submitted to the server (ie they exist as (hidden) fields in container id 372).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['dib_username'] = SyncFunctions::cleanName($attributes['dib_username'],'pef_login', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'dib_username' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'dib_username' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            //Check Unique Values for table option 557
            $criteria = '1=1';
            if(!array_key_exists('email', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `email` is in the design of container with id 372. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 590. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `email` = :fk1";
            $sql = "SELECT `pef_login`.`id` AS pkv FROM `pef_login` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["email"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 557 are submitted to the server (ie they exist as (hidden) fields in container id 372).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['email'] = SyncFunctions::cleanName($attributes['email'],'pef_login', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'email' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'email' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            //Check Unique Values for table option 561
            $criteria = '1=1';
            if(!array_key_exists('username', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `username` is in the design of container with id 372. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 595. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `username` = :fk1";
            $sql = "SELECT `pef_login`.`id` AS pkv FROM `pef_login` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["username"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 561 are submitted to the server (ie they exist as (hidden) fields in container id 372).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['username'] = SyncFunctions::cleanName($attributes['username'],'pef_login', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'username' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'username' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            // All clear - perform the insert...
            $sql = "INSERT INTO `pef_login` (";
            $fieldList = '';
            $valueList = '';
            $fieldType = array();
            $i=0;
            foreach ($attributes AS $key => $value) {
                if(!array_key_exists($key, $this->storeType) || (array_key_exists($key, $this->storeType) && $this->storeType[$key] === 'dibsqli')) {
                    unset($attributes[$key]);
                    continue;
                }
                $fieldList .= "`$key`, ";
                $valueList .= ":f$i, ";
                $params[':f'.$i] = $value;
                $fieldType[':f'.$i] = $this->fieldType[$key];
                $i++;
            }
            $fieldList = substr($fieldList, 0, strlen($fieldList) - 2);
            $valueList = substr($valueList, 0, strlen($valueList) - 2);
            $sql .= $fieldList . ") VALUES (" . $valueList . ")";
            dibMySqlPdo::setParamsType($fieldType, $targetDatabaseId);
            $value = dibMySqlPdo::execute($sql, $targetDatabaseId, $params);
            if ($value === FALSE) {
                if(!empty(Database::lastErrorUserMsg()))
                    return array('error', Database::lastErrorUserMsg());
                else
                    return array('error',"Error! The system could not create the record. Please contact the System Administrator.");
            }
            // Return array of pkvalues
            $pkValues["id"] = $value;
            // Add pkvalues to $attributes 
            $attributes = array_merge($attributes, $pkValues); 
            $crit = TRUE;
            if ($crit===TRUE) {
                // Insert audit trail record - first set unique_record
                $this->unique_record = 1;
                $this->now = date('Y-m-d H:i:s', time());
                $this->ipAddress = DibApp::getRealIpAddr();

                // Get pk values
                $recordId='';
                $recordId = $value;
                $entityId =  (isset($attributes['id'])) ? $attributes['id'] : null;
                foreach ($attributes AS $fieldName => $newValue) {
                    if(substr($fieldName, -14) == '_display_value') continue;
                    $newDisplayValue = (isset($attributes[$fieldName.'_display_value'])) ? $attributes[$fieldName.'_display_value'] : null;                    
                    $this->auditInsert("create", $fieldName, null, $newValue, 157, $recordId, $newDisplayValue, $entityId);
                }
            } elseif(is_array($crit)) return $crit;
            return $pkValues; // contains pk value of new record
        } catch (Exception $e) {
            return array ('error', "Error! The create request is not valid. Please contact the System Administator.");
        }
    }
     /**
     * Update a record
     * 
     * @param $pkValues
     * @param $attributes
     * @return array $pkValues  OR  array('error', $errMsg) on failure
     */
    public function update($pkValues, $attributes, $clientData=array()) {
        // Updates must occur only for fields that users have rights to AND where ci.crud_include<>0. The rest must retain old values - prevent user from updating them...
        return array('error', "Sorry, permissions restricts you from updating this table.");
    }
     /**
     * Deletes one record.
     * 
     * @param array $pkValues
     * @return boolean success of delete
     */
    public function delete($pkValues, $clientData=array()) {
        return array('error',"Sorry, the permission system restricts you from deleting records from this table.");
    }
    /**
     * Creates a duplicate of a record - will only work if the primary key is an auto-increment or supplied in $setValues.
     * 
     * @param array $pkValues - associative array of primary key field names & values
     * @param array $setValues - associative array of values that should be set in new record, overwriting original record's values
     * @param int $targetDatabaseId - if specified, the record is created in a foreign database table which must have exactly the same structure
     * @return type
     */
    public function duplicate($pkValues, $setValues=array(), $targetDatabaseId=null) {
        // Currently creation occurs through create function, so ALL fields and records are included where the user has CREATE rights AND
        // and audit trail is maintained. All creation errors are returned as FALSE.
        try {
            // Check if values in $id's are indeed pk's and of the right type. 
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;
            //`id` = :pk0
            $pkCrit = "`pef_login`.`id` = :pk1";  
            // Fields for duplication (include required fields and exclude expression, file, image & exclusion fields etc.)
            $sql = "SELECT 1 AS to_prohibit_error_if_none, `id`, `username`, `first_name`, `last_name`, `password`, `login_group_expiry`, `last_login_datetime`, `password_history`, `language`, `email`, `mobile_number`, `larger_font`, `test_user`, `password_expiry`, `password_remember_expiry`, `password_remember`, `password_remember_hash`, `dib_username`, `supplier_code`, `created_datetime`, `reset_guid`, `activation_guid`, `notes`, `default_url`, `pef_security_policy_id`, `login_attempts`, `reset_expiry`, `login_lockout`, `login_blocked_count`, `login_expiry`
                    FROM `pef_login` WHERE $pkCrit";
            //Note - create code handles unique values :-)
            dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
            $record = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if($record === FALSE || count($record) < 1){
				Log::err("SQL error while fetching values to duplicate for register.\r\nSQL: $sql\r\nPARAMS:" . json_encode($params) . "\r\nERROR:" . Database::lastErrorAdminMsg());
				return array('error',"Error! Could not read record to duplicate. Please contact the System Administrator.");
			}
            $delayArray = array(); // Temp store for values that must be memorised in order to update this table during DDuplicateRecords's final actions
            foreach($setValues as $field => $value) {
                if (array_key_exists($field, $record)) {
					if(!is_array($value)) // contant value
						$record[$field] = $value;
					elseif(!is_null($record[$field])) { 
						// Set values for special fields, such as foreign key references (eg pef_item.pef_field) to records that may have different pkey values
						// lookup value using SQL statements
						// eg SELECT f.name as fname, t.name FROM pef_field f INNER JOIN pef_table t ON f.pef_table_id = t.id WHERE f.id = :value   	
						$args = (strpos($value[0], ':value') !== FALSE) ? array(':value'=>$record[$field]) : array();
						$result = dibMySqlPdo::execute($value[0], $this->dbIndex, $args, true);
						if($result === FALSE || count($result) < 1) {
							Log::err("Unusual Sql error... No result returned in SOURCE db when attempting to find original values for a LOOKUP query while duplicating container register records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[0] . "\r\n\PARAMS: " . json_encode($args));
    						return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
						}
						// Convert result to params
						$args = array();
						foreach($result AS $k=>$v)
							$args[':'.$k] = $v;
						// Check if a delayed update must occur
						if($value[1]==='delay') {
							$delayArray[] = array($field, $value[2], $args);
							$record[$field] = null; // ***TODO allow SQL statement incase field value is required
						} else {
							// Run 2nd query, eg SELECT id FROM pef_field f INNER JOIN pef_table t ON f.pef_table_id = t.id WHERE f.name=:fname and t.name=:name
							$result = Database::fetch($value[1], $args, $targetDatabaseId);
							if(empty($result)) {
								// Check if 'create' is required
								if(isset($value[2]) && $value[2]==='create') {
									$result = Crud::duplicate($value[3], array('id'=>$record[$field]), $value[4], $targetDatabaseId);
									if(isset($result[0]) && $result[0]==='error') {	
										Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'register' records using the 'create' directive. Note, unless the create code succeeds, the whole operation will fail:\r\nLAST SQL ERROR:" . Database::lastErrorAdminMsg() . "\r\nPARAMS: " . json_encode($value[4]));
		    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
		    						}
		    						$result = array('id'=>$result);
		    					} else {
		    						Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'register' records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[1] . "\r\n\PARAMS: " . json_encode($args));
	    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
	    						}
							}
							$record[$field] = array_pop($result);
						}
					}
                }
            }
            $result = $this->create($record, true, $targetDatabaseId); // This will force unique values, and handle audit trail etc.
            if (isset($result[0]) && $result[0]==='error')
                return $result;
            elseif ($delayArray) {
				$value = array_pop($result);
				$params[':pk1'] = $value;
				foreach($delayArray as $args) {// store table name, field name, pkey value, sql statements x 2 and sql params x 2
					$sql = "UPDATE pef_login SET `" . $args[0] . "` = :value WHERE $pkCrit";					
					DibApp::$array['DuplicateRecords']['pef_login*'.$args[0].'*'.$value] = array($args[1], $args[2], $sql, $params);
				}
				return $value;
			} else
                return array_pop($result);  //returns the pk value of new record or FALSE on error;
        } catch (Exception $e) {
            return array('error',"Error! Could not create duplicate of record. Please contact the System Administrator");
        }
    }
     /**
     * Drop a specific node on-to another

     * @param string $dropPosition 'after'/'before'/'append'
     * @param integer $dropNodeId
     * @param type $nodeId
     * @param string $parentId 'root'/integer
     */
    function dropNode($dropPosition, $dropNodeId, $nodeId, $parentId) {
        return FALSE;
    }
    /**
     * Returns default values of a record as defined in pef_item.
     * 
     * @return array
     */
    public function getDefaults($createParams=null, $clientData=array()) {
        try {
            $attributes=array();
            $attributes['id'] = 0;
            $attributes['default_url'] = '/nav/products';
            $attributes['pef_security_policy_id'] = 1;
            // url has fields that must be populated in new record - just add to defaults...
            if (!empty($createParams) && is_array($createParams))  
                $attributes = array_merge($attributes, $createParams);
            return $attributes;
        }  catch (Exception $e) {
            return array ('error',"Error! Could not load defaults. Please contact the System Administator.");
        }
    }

    /**
     * Returns field-level criteria for field values that were changed in an Update operation.
     * 
     * @param array $validAttributes - associative array of new fields (only updatable fields)
     * @param array $oldValues - associative array of old values
     * @return type
     */
    private function getFieldChangeCriteria(&$validAttributes, &$oldValues) {     
        return FALSE;
    }
    /**
     * Returns the existing values of a certain record - accepts primary key used in parameters in sql.
     * 
     * @param string $sql
     * @return array
     */
    private function getRecordByPk($sql, $pkValues, $clientData=array(), $addSqlParams=FALSE) {

        // Note: apply plain text escaping to everything marked 'plain text' in validation_type
        if (!array_key_exists("id", $pkValues))
            return array ('error',"The primary key fields specified in the request are invalid.");
        if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
        $params[":pk1"] = $pkValues["id"];
        if(strpos($sql, '{:/') !== FALSE) { 
            // DIB TODO: A better solution would be to parse this template twice. On 2nd pass, add all $params based on constructed SQL statements.
            $sql = $this->resolvePhpParam($sql, $params, $clientData);
            if($sql === FALSE) 
                return array();
        }

        $criteria = '';
        if(!empty($criteria)) $criteria = ' WHERE ' . $criteria;

        $rst = dibMySqlPdo::execute($sql . $criteria, $this->dbIndex, $params, true);

        if ($rst === FALSE)
            return array();
        else
            return $rst;
    }
     /**
     * Adds the actual record to pef_audit_trail
     * 
     * @var string $crudType create/read/update/delete
     * @var string $fieldName name of field
     * @var array $oldValue string containing old values
     * @var array $newValue string containing new values
     * @var integer $tableId table_id
     * @var string $tableName name of table
     * @var integer $recordId primary key value
     */
    protected function auditInsert($crudType, $fieldName, $oldValue, $newValue, $tableId, $recordId, $newDisplayValue, $entityId, $oldDisplayValue=null) {
        $sql = "INSERT INTO `pef_audit_trail` 
             (`action`, pef_table_id, pef_container_id, table_name, record_id, date_time, ip_address, field_name, old_value, new_value, new_display_value, old_display_value, entity_id, entity_field_name, pef_login_id, username, unique_record) 
             VALUES ('$crudType', $tableId, 372, 'pef_login', :recordId, '" . $this->now . "', '" . $this->ipAddress . "', '$fieldName', :oldValue, :newValue, :newDisplayValue, :oldDisplayValue, :entityId, :entity_field_name, " . DIB::$USER['id'] . ", :username, " . $this->unique_record . ')';
        Database::execute($sql, array(':recordId'=>$recordId, ':oldValue'=>$oldValue, ':newValue'=>$newValue, ':newDisplayValue'=>$newDisplayValue, 
            ':oldDisplayValue'=>$oldDisplayValue, ':entityId'=>$entityId, ':entity_field_name'=>$this->entityFieldName, ':username'=>DIB::$USER['username']),
            DIB::$AUDITDBINDEX, null, TRUE, TRUE, NULL, FALSE // audit trail records are not added to pef_sqllog
        );
        $this->unique_record = 0;
    }
    /**
     * Strips the attributes from any columns the user may not update
     * @param $attributes
     * @return string
     */
    private function removeSecuredColumns(&$attributes) {
        // Define list of fields that may be updated
        $validAttributes = array();
        return array_intersect_key($attributes, $validAttributes);
    }
    public function getCaptions() {
    	return array("Id", "Login Group Expiry", "Default Url", "Username", "Last Login Datetime", "Pef Security Policy", "Password History", "Login Attempts", "Language", "Reset Expiry", "First Name", "Email", "Login Lockout", "Last Name", "Mobile Number", "Login Blocked Count", "Larger Font", "Login Expiry", "Email", "Password", "Test User", "Password Expiry", "Password Remember Expiry", "Password Remember", "Password Remember Hash", "Dib Username", "Supplier Code", "Created Datetime", "Reset Guid", "Activation Guid", "Notes", "Dibuid" );
    }

    private function resolvePhpParam($sql, &$params, &$filterParams) {
        $i = strpos($sql, '{:/');
        $counter = 0;
        while($i !== FALSE && $counter < 11) {
            $counter++;
            // get end of path 
            $j = strpos($sql, '}', $i + 1);
            if($j !== FALSE) {
                $path = substr($sql, $i + 2, $j - $i - 2);
                $pathArr = explode(',', $path); // get variable
                $var = (!array_key_exists(1, $pathArr)) ? null : trim((string)$pathArr[1]);
                $result = DibApp::loadPath(trim($pathArr[0]), TRUE, TRUE); // note, functionName must be included, and function must be in /components folder.
                if($result[0]==='error') {
                    Log::err("A PHP path was used to substitute SQL, but could not be resolved. Error returned:\r\n" . $result[1]);
                    return FALSE;
                }
                list($filePath, $className, $functionName) = $result;
                $callResult = $className::$functionName('register', $params, $filterParams, $sql, $var);
                if(is_array($callResult) && $callResult[0] === 'error') {
                    Log::err("A PHP path was used to substitute SQL, but returned an error: " . $callResult[1]);
                    return $this->invalidResult('System error. Please contact the System Administrator.');
                }
                //$this->phpParams[$path] = $callResult;  // deterministic?
                $sql = str_replace('{:'. $path . '}', $callResult, $sql);
            } else {
                Log::err("The PHP Replace SQL call found the string '{:/' identifying a candidate, but a the terminating '}' character was not found.");
                return $this->invalidResult('System error. Please contact the System Administrator.');
            }

            $i = strpos($sql, '{:/');
        }

        if($counter > 10) {
            Log::err("The PHP Replace SQL call uses the string '{:/' to find candidates. It stopped searching after finding more than 10 candidates to avoid possible recursive loops caused by replacement SQL that also contains candidates.");
            return $this->invalidResult('System error. Please contact the System Administrator.');
        }

        return $sql;
    }
    public function getSqlParts() {
		return array('model' => 'Table',
					 'containerName' => "Tablexx2xxregister",
					 'selectFields' => "`pef_login`.`id`,`pef_login`.`username`,`pef_login`.`first_name`,`pef_login`.`last_name`,`pef_login`.`password`,`pef_login`.`login_group_expiry`,`pef_login`.`last_login_datetime`,`pef_login`.`password_history`,`pef_login`.`language`,`pef_login`.`email`,`pef_login`.`mobile_number`,`pef_login`.`larger_font`,`pef_login`.`test_user`,`pef_login`.`password_expiry`,`pef_login`.`password_remember_expiry`,`pef_login`.`password_remember`,`pef_login`.`password_remember_hash`,`pef_login`.`dib_username`,`pef_login`.`supplier_code`,`pef_login`.`created_datetime`,`pef_login`.`reset_guid`,`pef_login`.`activation_guid`,`pef_login`.`notes`,`pef_login`.`dibuid`,`pef_login`.`default_url`,`pef_login`.`pef_security_policy_id`,`pef_login`.`login_attempts`,`pef_login`.`reset_expiry`,`pef_login`.`login_lockout`,`pef_login`.`login_blocked_count`,`pef_login`.`login_expiry`",
				     'selectSqlFields' => trim("
            ", ", \r\n"),
				     'selectSqlDisplay' =>  trim("
            ", ", \r\n"),
				     'selectTableDisplay' => trim("", ", \r\n"),          
                     'from' => trim("`pef_login` /*_dib DROPDOWNS - SQL DISPLAY EXPR dib_*/ 
             /*_dib DROPDOWNS - TABLE DISPLAY EXPR dib_*/  /*_dib EXTRA-TABLE-JOIN dib_*/ ", ", \r\n"),
                     'order_by' => "",
                     'filter' => ""
        );
	}
} // end Class
            