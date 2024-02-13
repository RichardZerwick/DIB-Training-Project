<?php 
    Class table_dibexNgStyle_x1x {
    public $count = 0;
    public $newPkValue = null; // for INSERTS it will contain lastInsertId
    public $pkeys = 'id';
    // List of PDO field types for fields from this table, parent tables, or items based on 'sql insert' expressions
    public $fieldType = array("id"=>PDO::PARAM_INT, "color"=>PDO::PARAM_STR, "first_name"=>PDO::PARAM_STR, "last_name"=>PDO::PARAM_STR, "position"=>PDO::PARAM_STR);
    // List of component store types of fields from only this table, else declare them as dibsqli fields
    public $storeType = array("id"=>'none', "color"=>'none', "first_name"=>'none', "last_name"=>'none', "position"=>'none');
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
        $this->dbIndex = DIB::$CONTAINERDATA['db_id'];
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
            	$str .= '`test_staff`.`' . $field . '` = :pk' . $i . ' AND ';
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
            Log::err("The read request for container dibexNgStyle specified an active filter by name that could not be resolved in the generated crud code.\r\nTab or container names were probably updated but code was not regenerated. Please regenerate code and try again.");
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
            $fromClause = "`test_staff`
                ";
        } else  
            $fromClause = "`test_staff`  ";
        $criteria .= $permsCrit;
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);
        if($getFirstOnly) { // Used after deletes to navigate to first record
            $sql = "SELECT `test_staff`.`id` FROM $fromClause $criteria ORDER BY `test_staff`.`id` limit 1";
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if($rst === FALSE)
                return array('error', 'Could not get first record. Please contact the System Administrator. (#0).');
            return $rst;
        }
        // Get total, first and last
        $sql = "SELECT count(*) as dib__total, min(`test_staff`.`id`) as dib__minid, max(`test_staff`.`id`) as dib__maxid FROM $fromClause $criteria";
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
            $tempCrit = ($criteria === '') ? "WHERE `test_staff`.`id` < :pk1 $permsCrit" : "$criteria AND `test_staff`.`id` < :pk1";
            $sql = "SELECT max(`test_staff`.`id`) as dib__id, count(`test_staff`.`id`) as dib__counter FROM $fromClause $tempCrit";
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if(empty($rst))
                return array('error', 'Could not set form navigation counts. Please contact the System Administrator. (#2).');
            $prev = array('id' => $rst['dib__id']);
            $currentNo = (int)$rst['dib__counter'] + 1;
            // Get next
            $tempCrit = ($criteria === '') ? "WHERE `test_staff`.`id` > :pk1 $permsCrit" : "$criteria AND `test_staff`.`id` > :pk1";
            $sql = "SELECT min(`test_staff`.`id`) as dib__id FROM $fromClause $tempCrit";
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
            $fromClause = "`test_staff`
                ";
        } else  
            $fromClause = "`test_staff`  ";
        $criteria .= $permsCrit;
        if($criteria !== '') $criteria = 'WHERE ' . substr($criteria, 4);
        // Template: SQL statement for MySql to fetch nth record for the Toolbar on Forms. Used in eg Table.php.
$sql = "SELECT `test_staff`.`id` 
        FROM $fromClause
        $criteria
        ORDER BY `test_staff`.`id` 
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
            	$fieldExpr = "`test_staff`.`$field`";
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
                $criteria = "`test_staff`.`id` = :pk1 ";
                $sql = "SELECT `test_staff`.`id`,`test_staff`.`first_name`,`test_staff`.`last_name`,`test_staff`.`position`,`test_staff`.`color` 
                         FROM `test_staff` 
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
                    $sql = "SELECT Count(*) AS totalcount FROM `test_staff`   " . (!empty($criteria) ? 'WHERE ' . substr($criteria, 4) : '');
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
                        $sql = "SELECT Count(*) AS filteredcount FROM `test_staff` $join   $criteria";
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
                        	$orderStr .= '`test_staff`.`' . $record['property'] . '` ' . $direction . ', ';
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
                `test_staff`.`id` AS `Id`, `test_staff`.`first_name` AS `First Name`, `test_staff`.`last_name` AS `Last Name`, `test_staff`.`position` AS `Position`, `test_staff`.`color` AS `Color` 
            FROM `test_staff` 
                 ";
else
    $sql = "SELECT `test_staff`.`id`,`test_staff`.`first_name`,`test_staff`.`last_name`,`test_staff`.`position`,`test_staff`.`color` 
            FROM `test_staff` 
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
            // Check Validation
            if(empty($attributes['position'])) $attributes['position'] = null; // change '' to null for enum types
	        // position (plain text)
            if(isset($attributes['position']) && trim((string)($attributes['position'])) !== '') {
                if(strlen($attributes["position"]) > 255)
                    return array ('error',"The 'Position' field cannot contain more than 255 characters");
            }  else
                return array ('error',"The 'Position' field is required. Please provide a value.");
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
	        // color (plain text)
            if(isset($attributes['color']) && trim((string)($attributes['color'])) !== '') {
                if(strlen($attributes["color"]) > 50)
                    return array ('error',"The 'Color' field cannot contain more than 50 characters");
            }               
            //Check Unique Values for table option 462
            $criteria = '1=1';
            if(!array_key_exists('email', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `email` is in the design of container with id 297. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 482. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `email` = :fk1";
            $sql = "SELECT `test_staff`.`id` AS pkv FROM `test_staff` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["email"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 462 are submitted to the server (ie they exist as (hidden) fields in container id 297).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['email'] = SyncFunctions::cleanName($attributes['email'],'test_staff', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'email' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'email' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            //Check Unique Values for table option 464
            $criteria = '1=1';
            if(!array_key_exists('first_name', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `first_name` is in the design of container with id 297. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 484. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `first_name` = :fk1";
            if(!array_key_exists('last_name', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `last_name` is in the design of container with id 297. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 485. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `last_name` = :fk2";
            $sql = "SELECT `test_staff`.`id` AS pkv FROM `test_staff` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["first_name"], ":fk2" => $attributes["last_name"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 464 are submitted to the server (ie they exist as (hidden) fields in container id 297).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['first_namefirst_1last_name'] = SyncFunctions::cleanName($attributes['first_namefirst_1last_name'],'test_staff', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'first_name,last_name' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'first_name,last_name' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            //Check Unique Values for table option 468
            $criteria = '1=1';
            if(!array_key_exists('staff_code', $attributes)) {
                Log::err("Unique value validation failed. Ensure that field `staff_code` is in the design of container with id 297. It is required since it is involved in checking the unique index\r\n".
                         "of pef_table_option_field.id 489. Note, you may need to add it as a hidden field in the container, or set a default in pef_field.\r\n".
                         "NOTE: if one of the items linked to this field is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate the CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            $criteria .= " AND `staff_code` = :fk1";
            $sql = "SELECT `test_staff`.`id` AS pkv FROM `test_staff` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["staff_code"]);
            $rst = dibMySqlPdo::execute($sql, $targetDatabaseId, $paramsU, true);
            if ($rst === FALSE) {
                Log::err("Unique value validation failed. Ensure that values for all fields that are involved in checking unique index\r\n".
                         "of pef_table_option.id 468 are submitted to the server (ie they exist as (hidden) fields in container id 297).\r\n".
                         "NOTE: if one of these fields is a dropdown or other component that has a port that points to a container which the current permission group does NOT have access to, this component will not be included. Simply remove the link to the Port record and regenerate CRUD file.");
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            }
            if(!empty($rst)) {
                if($makeUniqueValues)
                    // Force unique values - for combinations, only enforce on first 
                    $attributes['staff_code'] = SyncFunctions::cleanName($attributes['staff_code'],'test_staff', '');
                elseif(count($paramsU) > 1)
                    return array('error',"Add record cancelled. The combination of values in 'staff_code' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains these values.");
                else
                    return array('error',"Add record cancelled. The value in 'staff_code' needs to be unique. The record identified by '" . $rst['pkv'] . "' already contains this value.");
            }
            // All clear - perform the insert...
            $sql = "INSERT INTO `test_staff` (";
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
                    $this->auditInsert("create", $fieldName, null, $newValue, 70, $recordId, $newDisplayValue, $entityId);
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
        try {    
            // Check Validation - note the DibApp::jsonDecode() function in the CrudController ensures $attributes contains no child arrays
            // id (integer)
            if(isset($attributes['id']) && trim((string)($attributes['id'])) !== '') {
                if(!is_int((int)$attributes["id"]) || !ctype_digit((string)abs((int)$attributes["id"])))
                    return array ('error',"The 'Id' field must be an integer value.");
                if($attributes["id"] < -2147483647)
                    return array ('error',"The 'Id' field value must be greater than or equal to -2147483647");
                if($attributes["id"] > 2147483647)
                    return array ('error',"The 'Id' field value must be less than or equal to 2147483647");
            }  else
                return array ('error',"The 'Id' field is required. Please provide a value.");
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
            // position (plain text)
            if(isset($attributes['position']) && trim((string)($attributes['position'])) !== '') {
                if(strlen($attributes["position"]) > 255)
                    return array ('error',"The 'Position' field cannot contain more than 255 characters");
            }  else
                return array ('error',"The 'Position' field is required. Please provide a value.");
            // color (plain text)
            if(isset($attributes['color']) && trim((string)($attributes['color'])) !== '') {
                if(strlen($attributes["color"]) > 50)
                    return array ('error',"The 'Color' field cannot contain more than 50 characters");
            }               
            // Check if values in $pkValues are indeed pk's and of the right type
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");            
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;            
            $pkCrit = "`test_staff`.`id` = :pk1";
            // Check Unique Values for table option PRIMARY(461)
            $criteria = '1=1 ';           
            if(!array_key_exists('id', $attributes)) {
                $uRst = dibMySqlPdo::execute("SELECT `id` FROM `test_staff` WHERE $pkCrit", $this->dbIndex, $params, true);
                $attributes['id'] = $uRst['id'];
            }
            $criteria .= " AND `id` = :fk1 ";
            $criteria .= "AND `id` <> :pk1";
            $sql = "SELECT `test_staff`.`id` AS pkv FROM `test_staff` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["id"]);
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $paramsU + $params, true);
            if ($rst === FALSE)
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            if(!empty($rst)) {
                if(count($paramsU) > 1)
                    return array('error',"Update record cancelled. The combination of values in 'id' needs to be unique. Another record already contains the same combination of values.");
                else
                    return array('error',"Update record cancelled. The value in 'id' needs to be unique. Another record already contains the same value.");
            }
            // Check Unique Values for table option first_name_last_name(464)
            $criteria = '1=1 ';           
            if(!array_key_exists('first_name', $attributes)) {
                $uRst = dibMySqlPdo::execute("SELECT `first_name` FROM `test_staff` WHERE $pkCrit", $this->dbIndex, $params, true);
                $attributes['first_name'] = $uRst['first_name'];
            }
            $criteria .= " AND `first_name` = :fk1 ";
            if(!array_key_exists('last_name', $attributes)) {
                $uRst = dibMySqlPdo::execute("SELECT `last_name` FROM `test_staff` WHERE $pkCrit", $this->dbIndex, $params, true);
                $attributes['last_name'] = $uRst['last_name'];
            }
            $criteria .= " AND `last_name` = :fk2 ";
            $criteria .= "AND `id` <> :pk1";
            $sql = "SELECT `test_staff`.`id` AS pkv FROM `test_staff` WHERE $criteria";
            $paramsU = array(":fk1" => $attributes["first_name"], ":fk2" => $attributes["last_name"]);
            $rst = dibMySqlPdo::execute($sql, $this->dbIndex, $paramsU + $params, true);
            if ($rst === FALSE)
                return array('error',"Could not perform unique value validation. Please contact the System Administrator.");
            if(!empty($rst)) {
                if(count($paramsU) > 1)
                    return array('error',"Update record cancelled. The combination of values in 'first_name , last_name' needs to be unique. Another record already contains the same combination of values.");
                else
                    return array('error',"Update record cancelled. The value in 'first_name,last_name' needs to be unique. Another record already contains the same value.");
            }
            $crit = $pkCrit;
            // Get record's existing (old) values
            $sql = "SELECT `test_staff`.* 
		            FROM `test_staff`
                        WHERE $crit";
            $recordOld = $this->getRecordByPk($sql, $pkValues, $clientData, TRUE);
            if (count($recordOld) === 0)
                return array('error',"Error! The record to be updated has been deleted or denied by the permission system.");
            $multiChanged = false; 
            $crit .= "";
            // Get field-level criteria if applicable
            $validAttributes = $attributes;
            $sql = "UPDATE `test_staff`  SET ";
            $i=0;
            foreach ($validAttributes AS $key => $value) {
                if(!array_key_exists($key, $recordOld) || (string)$value === (string)$recordOld[$key] || !array_key_exists($key, $this->storeType) || $this->storeType[$key] === 'dibsqli')
                    unset($validAttributes[$key]); // for auditing purposes (also so as not to store sensitive dibsqli fields like 'password')
                else {
                    // Only update the fields that have changed values
                        $sql .= "`test_staff`.`$key`=:f$i, ";
                    $params[':f'.$i] = $value;
                    $fieldType[':f'.$i] = $this->fieldType[$key];
                    $i++;
                }
            }
            $sql = rtrim($sql, ', ');
            $sql .= " WHERE $crit";
            if (count($validAttributes) === 0 && empty($multiChanged)) 
                return array('notice',"No changes were made to existing database values.");
            $mainUpdated = 0;
            if (count($validAttributes) > 0) {
                // Do the actual update for the main table fields
                dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
                $success = dibMySqlPdo::execute($sql, $this->dbIndex, $params);
                $mainUpdated = dibMySqlPdo::count();
                if($success === FALSE)
                    return array('error', Database::lastErrorUserMsg());
                elseif($mainUpdated === 0 ) {
                    $ind = strpos($crit, " AND (");
                    if($ind === FALSE) $ind = -5;
                    // Nothing was changed. Seems Dropinbase removed fields that were blocked by permissions on existing/old values.
                    return array('error',"Update failed. Note, changes are only permitted for records where existing(old) values comply with set conditions.<br>Update permitted records only, or contact a System Administrator."); //  . str_replace($pkCrit . ' AND', '', substr($crit, $ind + 5))
                }
            }
            // NOTE: when multiple foreign keys point to the same primary table, 
            //       then pef_item.pef_parent_table_link_field_id must be specified to identify the correct link
            $parentFieldChanged = false;
            if ($mainUpdated === 0 && !$multiChanged && !$parentFieldChanged) // Nothing updated
                return array('notice', "No changes were made to existing database values.");
            else {
                if($mainUpdated > 0) {
                    $crit = TRUE;           
                    if ($crit===TRUE) {
                        // Insert audit trail record - first set unique_record
                        $this->unique_record = 1;
                        $this->now = date('Y-m-d H:i:s', time());
                        $this->ipAddress = DibApp::getRealIpAddr();
                        if (count($pkValues) > 1) {
                            $recordId = '';
                            foreach ($pkValues as $k => $v)
                                $recordId .= "$k=$v, ";
                            $recordId = substr($recordId, 0, -2);
                        } else
                            $recordId = current($pkValues);
                        $entityId = $recordOld['id'];
                        foreach ($validAttributes AS $fieldName => $newValue) {
                            if ($newValue !== $recordOld[$fieldName]) {
                                $newDisplayValue = (array_key_exists($fieldName.'_display_value', $attributes)) ? $attributes[$fieldName.'_display_value'] : null;
                                $oldDisplayValue = (array_key_exists($fieldName.'_display_value', $recordOld)) ? $recordOld[$fieldName.'_display_value'] : null;                        
                                $this->auditInsert("update", $fieldName, $recordOld[$fieldName], $newValue, 70, $recordId, $newDisplayValue, $entityId, $oldDisplayValue);
                            }
                        }
                    } elseif (is_array($crit)) return $crit;
                }
            }
        } catch (Exception $e) {            
            return array ('error', "Error! The update request is not valid. Please contact the System Administator.");
        }
        return $pkValues;
    }
     /**
     * Deletes one record.
     * 
     * @param array $pkValues
     * @return boolean success of delete
     */
    public function delete($pkValues, $clientData=array()) {
        try {
            // Check if values in $pkValues are indeed pk's and of the right type.
            $params = array();
            $fieldType = array();
            if (!array_key_exists("id", $pkValues))
                return array ('error',"The primary key fields specified in the request are invalid.");
             if ($pkValues["id"] != (string)(float)$pkValues["id"])
                return array ('error',"Error! The primary key field values specified in the request are invalid.");
            $params[":pk1"] = $pkValues["id"];
            $fieldType[":pk1"] = PDO::PARAM_INT;
            $pkCrit = "`test_staff`.`id` = :pk1";
            $attributes = 'Not yet loaded';
            // Get criteria for old values
             $crit = $pkCrit;
            // Get old values before we delete the record...
            $sql = "SELECT `test_staff`.*  
                    FROM `test_staff` 
                    WHERE $pkCrit";
            $attributes = $this->getRecordByPk($sql, $pkValues, array(), TRUE);
            if(count($attributes) === 0)
                return TRUE; // Other user deleted this record
            $sql = "DELETE `test_staff` FROM `test_staff`   WHERE $crit";
            dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
            $result = dibMySqlPdo::execute($sql, $this->dbIndex, $params);
            if ($result === FALSE || dibMySqlPdo::count() === 0) {
                if($result === FALSE && Database::lastErrorUserMsg())
                    return array('error',Database::lastErrorUserMsg());
                else
                    return array('error',"Permissions failure on existing(old) values. Only those records that meet the outlined condition(s) can be deleted."); // . substr($crit, strpos($crit, " AND (") + 5)
            }
            if (dibMySqlPdo::count() > 0) {
                $crit = TRUE;
                if ($crit===TRUE) {
                    // Insert audit trail record - first set unique_record
                    $this->unique_record = 1;
                    $this->now = date('Y-m-d H:i:s', time());
                    $this->ipAddress = DibApp::getRealIpAddr();
                    if (count($pkValues) > 1) {
                        $recordId = '';
                        foreach ($pkValues as $k => $v)
                            $recordId .= "$k=$v, ";
                        $recordId = substr($recordId, 0, strlen($recordId) - 2);
                    } else {
                        foreach ($pkValues as $k => $v)
                            $recordId = $v;
                    }
                    $entityId = $attributes['id'];
                    foreach ($attributes AS $fieldName => $oldValue) {
                        if(substr($fieldName, -14) == '_display_value') continue;
                        $oldDisplayValue = (array_key_exists($fieldName.'_display_value', $attributes)) ? $attributes[$fieldName.'_display_value'] : null;                                                
                        $this->auditInsert("delete", $fieldName, $oldValue, NULL, 70, $recordId, null, $entityId, $oldDisplayValue);
                    }
                } elseif (is_array($crit)) return $crit;
                return true;
            }
        }  catch (Exception $e) {        
			return array('error',"A system error occured while deleting the record. Please contact the System Administrator.");
		}
        // ***TODO if user deletes many records, and he has no permissions on some of them, he shouldn't get 10x permission messages?
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
            $pkCrit = "`test_staff`.`id` = :pk1";  
            // Fields for duplication (include required fields and exclude expression, file, image & exclusion fields etc.)
            $sql = "SELECT 1 AS to_prohibit_error_if_none, `id`, `first_name`, `last_name`, `position`, `color`
                    FROM `test_staff` WHERE $pkCrit";
            //Note - create code handles unique values :-)
            dibMySqlPdo::setParamsType($fieldType, $this->dbIndex);
            $record = dibMySqlPdo::execute($sql, $this->dbIndex, $params, true);
            if($record === FALSE || count($record) < 1){
				Log::err("SQL error while fetching values to duplicate for dibexNgStyle.\r\nSQL: $sql\r\nPARAMS:" . json_encode($params) . "\r\nERROR:" . Database::lastErrorAdminMsg());
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
							Log::err("Unusual Sql error... No result returned in SOURCE db when attempting to find original values for a LOOKUP query while duplicating container dibexNgStyle records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[0] . "\r\n\PARAMS: " . json_encode($args));
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
										Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibexNgStyle' records using the 'create' directive. Note, unless the create code succeeds, the whole operation will fail:\r\nLAST SQL ERROR:" . Database::lastErrorAdminMsg() . "\r\nPARAMS: " . json_encode($value[4]));
		    							return array('error', "Configuration error found while duplicating records. Please contact the System Administrator.");
		    						}
		    						$result = array('id'=>$result);
		    					} else {
		    						Log::err("Sql error, or no result returned when attempting to run a LOOKUP query against database id $targetDatabaseId while duplicating container 'dibexNgStyle' records. Note, unless this query returns a value, the code will not work.\r\nSQL: " . $value[1] . "\r\n\PARAMS: " . json_encode($args));
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
					$sql = "UPDATE test_staff SET `" . $args[0] . "` = :value WHERE $pkCrit";					
					DibApp::$array['DuplicateRecords']['test_staff*'.$args[0].'*'.$value] = array($args[1], $args[2], $sql, $params);
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
             VALUES ('$crudType', $tableId, 297, 'test_staff', :recordId, '" . $this->now . "', '" . $this->ipAddress . "', '$fieldName', :oldValue, :newValue, :newDisplayValue, :oldDisplayValue, :entityId, :entity_field_name, " . DIB::$USER['id'] . ", :username, " . $this->unique_record . ')';
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
    	return array("Id", "Color", "First Name", "Last Name", "Position" );
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
                $callResult = $className::$functionName('dibexNgStyle', $params, $filterParams, $sql, $var);
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
					 'containerName' => "Tablexx1xxdibexNgStyle",
					 'selectFields' => "`test_staff`.`id`,`test_staff`.`first_name`,`test_staff`.`last_name`,`test_staff`.`position`,`test_staff`.`color`",
				     'selectSqlFields' => trim("
            ", ", \r\n"),
				     'selectSqlDisplay' =>  trim("
            ", ", \r\n"),
				     'selectTableDisplay' => trim("", ", \r\n"),          
                     'from' => trim("`test_staff` /*_dib DROPDOWNS - SQL DISPLAY EXPR dib_*/ 
             /*_dib DROPDOWNS - TABLE DISPLAY EXPR dib_*/  /*_dib EXTRA-TABLE-JOIN dib_*/ ", ", \r\n"),
                     'order_by' => "",
                     'filter' => ""
        );
	}
} // end Class
            