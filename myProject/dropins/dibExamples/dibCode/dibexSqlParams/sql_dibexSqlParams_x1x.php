<?php
class sql_dibexSqlParams_x1x {
    public $page = 1;
	public $page_size = 50;
    public $fieldType = array();
    // format: $itemName => array($expression, $contentType, $columnAlias)
    public $sqlFields = array(
            'client_id'=>array("c.id", 'text', "client_id"),
			'name'=>array("c.name", 'text', "name"),
			'client_contact'=>array("CONCAT(mc.first_name, ' ', mc.last_name)", 'text', "client_contact"),
			'email'=>array("mc.email", 'text', "email"),
			'position'=>array("mc.position", 'other', "position"),
    );
    public $pkeys = 'cc_id';
    protected $dbIndex = null;
    function __construct() {
        $this->dbIndex = DIB::$CONTAINERDATA['db_id'];
        $dbClassPath = (DIB::$DATABASES[$this->dbIndex]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
        set_time_limit(180);
    }
    /**
     * parses json $gridFilter string and returns a SQL WHERE clause string, and PDO parameters (passed by reference)
     */
    public function parseGridFilter($gridFilter, &$params=array(), &$i = 0) {
        //Eg [{"property":"name","value":"g & >e"},{"property":"notes","value":"< z &  w"}]
        $allCrit = "";
        $mainConjunction = '';
        $conjunction = ' AND ';
        foreach($gridFilter as $row) {
            // check if $row is sequential array, if so parse recursively and encapsulate in brackets (Usage example: calendarComponent)
            if (array_keys($row) == range(0, count($row) - 1)) {
                $parsed = $this->parseGridFilter($row, $params, $i);
                if(is_array($parsed))
                    return $parsed; // error is thrown. Result should be string
                $allCrit .= $mainConjunction . '(' . $parsed. ')';
                continue;
            }
        	if(!array_key_exists('property', $row) || !array_key_exists('value', $row))
        		return array('error',"Filter criteria was not submitted in correct format. Please contact the System Administrator.");
        	$field=$row['property'];
            $value=trim($row['value']);
            if($value === null || $value === '') continue;
            if(!array_key_exists($field, $this->sqlFields)){
                Log::err("When `expression_type`='sql', the `expression` field of an Item must be the alias of the column specified in the SQL query.\r\nThe browser request submitted item name '$field', but this item's expression field does not match the alias of a column in the SQL query result.");
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            }
            if(array_key_exists($field.'_display_value', $this->sqlFields))
                $field = $field.'_display_value';
            elseif(isset($this->sqlFields[$field][2]))
                $field = $this->sqlFields[$field][2];
            else{
                Log::err("An unknown fieldname '$field' was used in filter criteria. Regenerate the code for this container to fix the problem, or ensure the expression field is set correctly in the Designer.");
                return array('error',"An unknown fieldname was used in filter criteria. Please contact the System Administrator.");
            }
            $subparts = explode ('&', str_replace('|', '|&', $value));
            $fieldCrit = "";
            $allCrit .= $mainConjunction;
            $mainConjunction = ' AND ';
            foreach($subparts as $stringValue) {
                $stringValue = trim($stringValue);
                 if($stringValue === ''){
                    if($conjunction === ' OR ') $mainConjunction = $conjunction;
                	continue; // return array('error', "The filter criteria syntax is incorrect. Please try again.");
                }	
                if ($fieldCrit !== '')
                    $fieldCrit .= $conjunction; // $conjunction is found in prev. loop
                if (substr($stringValue, -1) === "|") {
                    $conjunction = ' OR ';
                    $stringValue = trim(substr($stringValue, 0, strlen($stringValue) - 1));
                } else
                    $conjunction = ' AND ';
                $intValue = trim($stringValue, "=!>< ");
                if ($intValue === '')
                	continue; // return array('error', "The filter criteria syntax is incorrect. Please try again.");
                //is null
                if (strtolower(substr($stringValue, 0, 4)) === "null") {
                    $fieldCrit .= "$field IS NULL";
                }
                //is not null
                elseif (strtolower(substr($stringValue, 0, 6)) === "<>null") {
                    $fieldCrit .= "$field IS NOT NULL";
                }
                //is empty
                elseif (strtolower(substr($stringValue, 0, 5)) === "empty") {
                    $fieldCrit .= "$field = ''";
                }
                //is not empty
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>empty") {
                    $fieldCrit .= "$field <> ''";
                }
                //not like
                elseif (strtolower(substr($stringValue, 0, 7)) === "<>like ") {
                    $fieldCrit .= "$field NOT LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 7)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                }
                //equal to
                elseif (substr($stringValue, 0, 1) === "=") {
                    $fieldCrit .= "$field = :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //not equal to
                elseif (substr($stringValue, 0, 2) === "!=" || substr($stringValue, 0, 2) === "<>") {
                    $fieldCrit .= "$field != :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //greater and equal
                elseif (substr($stringValue, 0, 2) === ">=") {
                    $fieldCrit .= "$field >= :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //greater than
                elseif (substr($stringValue, 0, 1) === ">") {
                    $fieldCrit .= "$field > :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //smaller and equal
                elseif (substr($stringValue, 0, 2) === "<=") {
                    $fieldCrit .= "$field <= :f" . $i;
                    $params[":f".$i] = $intValue;
                }
                //smaller than
                elseif (substr($stringValue, 0, 1) === "<") {
                    $fieldCrit .= "$field < :f" . $i;
                    $params[":f".$i] = $intValue;
                }                
                //like
                elseif (strtolower(substr($stringValue, 0, 5)) === "like ") {
                    $fieldCrit .= "$field LIKE :f" . $i;
                    $params[':f'.$i] = str_replace('*', '%', substr($stringValue, 5)); //note, this allows user to put * or _ inside $stringValue... which is okay...
                }                
                //anything else
                else {
                    $fieldCrit .= "$field LIKE :f" . $i;
                    $params[":f".$i] = str_replace('*', '%', '%'.$stringValue).'%'; //note, this allows user to put * or _ inside $stringValue... which is okay...
                }
                $i++;
            }              
            if ($fieldCrit !== "")
                $allCrit .= "(" . $fieldCrit . ") ";
        }
        return $allCrit;
    }  
    public function parseFilter($activeFilter, &$params, &$filterParams) {
        $criteria = ''; 
        if($criteria==='') return  array('error',"Error! The active filter could not be found.");
        return substr($criteria, 4);
	}
    public function read($page, $page_size=20, $order='', $gridFilter=null, $filterParams=array(), $activeFilter=null, $phpFilter=null, $phpFilterParams=array(), $group=null, $readType='gridlist', $node=null, $action=null, $actionData=null, $countMode='all') {
        try {
            if(empty($page) || $page<=0) $page = 1;        	
            if(empty($page_size) || $page_size<0) $page_size = 20;
            /// Initialize values
            $display_field='';
            $sql = '';
            $params = array();
            $whereCrit = '';
            $havingCrit = '';
            $userCrit = '';
            $criteria = "";
             $criteria .= " AND (c.id = :alias_self_clientId 
AND :dibUser_admin_user = 1 
AND mc.updated < :dibDateTime)";
            if (array_key_exists('alias_self_clientId',$filterParams)) {
                $params[':alias_self_clientId'] = $filterParams['alias_self_clientId'];
            } else {
                $value = EvalCriteria::evalParam(':alias_self_clientId', $filterParams);
                if(is_array($value))
                    $criteria = '1 = 2'; // We're returning no records since if eg selected is used and there are no selected records then this error will occur.
                else 
                    $params[':alias_self_clientId'] = $value;
            }
            if(array_key_exists('admin_user', DIB::$USER)) {
                $params[':dibUser_admin_user']=DIB::$USER['admin_user'];
                if(array_key_exists('dibUser_admin_user', $filterParams)) unset($filterParams['dibUser_admin_user']);
            }
            if (array_key_exists('dibDateTime',$filterParams)) {
                $params[':dibDateTime'] = $filterParams['dibDateTime'];
            } else {
                $value = EvalCriteria::evalParam(':dibDateTime', $filterParams);
                if(is_array($value))
                    $criteria = '1 = 2'; // We're returning no records since if eg selected is used and there are no selected records then this error will occur.
                else 
                    $params[':dibDateTime'] = $value;
            }
            $this->page = $page;
            $this->page_size = $page_size;
            $this->filterParams = $filterParams;
            if($page === 1 || $countMode==='all'){
            	// Template: MySQL - Get totalcount of records for pef_sql query where Group By is used. Used in eg SqlPdoTemplate.php.
            $totalCrit = ($criteria) ? ' WHERE ' . ltrim($criteria, ' AND') : ''; 
            $totalHavingCrit = ($havingCrit) ? ' HAVING ' . ltrim($havingCrit, ' AND') : '';
            $sqlTotal = " SELECT SQL_CALC_FOUND_ROWS 
                         1 as temp
            			 FROM test_client c  
INNER JOIN test_client_contact mc ON mc.client_id = c.id 
            			 $totalCrit
            			 GROUP BY  c.id, c.name, c.start_date, c.email,  mc.first_name, mc.last_name
            			 $totalHavingCrit LIMIT 1";
            $totalRst = dibMySqlPdo::execute($sqlTotal, DIB::$CONTAINERDATA['db_id'], $params, true);
            if($totalRst === FALSE)
                return array('error', "Error! Could not read list information. Please contact the System Administrator");
            $sqlTotal = "SELECT FOUND_ROWS() AS counter";
            $totalRst = dibMySqlPdo::execute($sqlTotal, DIB::$CONTAINERDATA['db_id'], array(), true);
            $totalCount = (empty($totalRst['counter'])) ? 0 : $totalRst['counter'];
            $totalRst = null;
            } else
            	$totalCount = null;
            // user grid filters
            $filteredCount = $totalCount;
            // PHP generated / developer filter - this needs to be here (after total count calc), else filtered excel export on group by queries breaks.
            if($phpFilter) { 
                $params = array_merge($params, $phpFilterParams);
                $havingCrit .= " AND ($phpFilter)";
            }
            if ($gridFilter) {
                $crit = $this->parseGridFilter($gridFilter, $params);
                if(is_array($crit))
                    return $crit; // error occured, $userCrit contains error message.
                elseif(!empty($crit))
                    $havingCrit .= " AND ($crit)";
            }
            //Build ORDER BY clause - Order of priority:  order by in user's request, pef_container, pef_sql 
            $orderStr = " ORDER BY c.name";
            if($order) {
                $orderStr = " ORDER BY ";
                foreach($order as $key => $record) {
                    if(!isset($record['property']) || !array_key_exists($record['property'], $this->sqlFields)) {
                        Log::err("The following unknown field name was used to sort on: " . $record['property'] . 
                                 ". The list of known fields are:\r\n" . json_encode(array_keys($this->sqlFields)));
                        return array('error', "An invalid fieldname was used in order criteria. Please contact the System Administrator.");
                    }
                    if(isset($record['direction'])) {
                        if (strtoupper($record['direction']) !== 'ASC' && strtoupper($record['direction']) !== 'DESC')
                            return array('error', "An invalid sort direction was used in order criteria. Please contact the System Administrator.");
                        else
                            $direction = $record['direction'];
                    } else
                        $direction = '';
                    $orderStr .= $this->sqlFields[$record['property']][2] . ' ' . $direction . ', ';
                }
                $orderStr = substr($orderStr, 0, strlen($orderStr) - 2);
            }
            if ($criteria) $whereCrit = ' WHERE ' . substr($criteria, 4);
            if ($havingCrit) $havingCrit = ' HAVING ' . substr($havingCrit, 4);
            // Template: MySql - Handle the pef_sql limit clause for paging purposes for database engines that support the LIMIT keyword. Used in eg SqlPdoTemplate.php.
        if($this->page === 1)
            $limit = ' LIMIT ' .  (($this->page_size > 10) ? 10 : $this->page_size);
        elseif($page_size * $page > 10)
            $limit = ' LIMIT ' . floor(10 / $this->page_size) * $this->page_size . ', ' . fmod(10, $this->page_size);
        else
            $limit = ' LIMIT ' . ($this->page_size * ($this->page - 1)) .  ', ' . $this->page_size;
            // Template: MySQL - Main Sql statement to fetch many records of a pef_sql query with a Group By clause for database engines that recognise LIMIT. Used in eg SqlPdoTemplate.php. 
		$sql = " SELECT SQL_CALC_FOUND_ROWS 
				mc.id as cc_id, c.id as client_id, c.name, 
CONCAT(mc.first_name, ' ', mc.last_name) as client_contact, 
mc.email, mc.position
		        FROM test_client c  
INNER JOIN test_client_contact mc ON mc.client_id = c.id 
		        $whereCrit
		        GROUP BY  cc_id, c.id, c.name, c.start_date, c.email,  mc.first_name, mc.last_name
		        $havingCrit 
		        $orderStr $limit"; 
            $records = dibMySqlPdo::execute($sql, $this->dbIndex, $params, FALSE, PDO::FETCH_ASSOC);
            if($records === FALSE)
                return array('error', "Error! Could not obtain data. Please contact the System Administrator");
            if (($gridFilter || $phpFilter) && ($this->page===1 || $countMode==='all')) {
            	// Template: MySQL - Get totalcount of records for pef_sql query where Group By is used. Used in eg SqlPdoTemplate.php.
	            $totalRst = dibMySqlPdo::execute("SELECT FOUND_ROWS() AS Counter", DIB::$CONTAINERDATA['db_id'], array(), true);
	            $filteredCount = $totalRst['Counter'];
	            $totalRst = null;
            }
            return array($records, $filteredCount, $totalCount, array());
        } catch (Exception $e) {
			return array('error',"Error! Could not obtain data. Please contact the System Administrator");
        }
    }    
    public function getCaptions() {
    	return array("Client", "Name", "Client Contact", "Email", "Position" );
    }
    public function getSqlParts() {
		return array('model' => 'Sql',
				     'containerName' => "Sqlxx1xxdibexSqlParams",
					 'primary_field' => "cc_id",
                     'display_field' => "",                     
				     'select' => "mc.id as cc_id, c.id as client_id, c.name, 
CONCAT(mc.first_name, ' ', mc.last_name) as client_contact, 
mc.email, mc.position
",
				     'from' =>   "test_client c  
INNER JOIN test_client_contact mc ON mc.client_id = c.id",
				     'where' =>  "c.id = :alias_self_clientId 
AND :dibUser_admin_user = 1 
AND mc.updated < :dibDateTime", 
				     'group_by' =>"c.id, c.name, c.start_date, c.email,  mc.first_name, mc.last_name",
				     'having' =>  "",
                     'order_by' => "c.name",
                     'header'=> "",
                     'limit' =>  "10",
                     'order_by_container' => "",
                     'filter' => ""
        );
    }
}
    