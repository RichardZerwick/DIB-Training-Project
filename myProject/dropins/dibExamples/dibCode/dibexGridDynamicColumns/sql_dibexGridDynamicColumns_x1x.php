<?php
class sql_dibexGridDynamicColumns_x1x {
    public $page = 1;
	public $page_size = 50;
    public $fieldType = array();
    // format: $itemName => array($expression, $contentType, $columnAlias)
    public $sqlFields = array(
            'id'=>array("id", 'other', "id"),
			'name'=>array("name", 'other', "name"),
			'vip'=>array("vip", 'other', "vip"),
    );
    public $pkeys = '';
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
                $field =  $this->sqlFields[$field.'_display_value'][0];
            elseif(isset($this->sqlFields[$field][0]))
                $field = $this->sqlFields[$field][0];
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
                    $params[":f".$i] = str_replace('*', '%', $stringValue).'%'; //note, this allows user to put * or _ inside $stringValue... which is okay...
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
            $this->page = $page;
            $this->page_size = $page_size;
            $this->filterParams = $filterParams;
            if($page === 1 || $countMode==='all'){
                $totalCrit = ($criteria) ? ' WHERE ' . ltrim($criteria, ' AND') : '';
                $sqlTotal =  " SELECT  Count(*) AS counter FROM test_client $totalCrit";
	            $totalRst = dibMySqlPdo::execute($sqlTotal, $this->dbIndex, $params, TRUE, PDO::FETCH_ASSOC);
	            if($totalRst === FALSE)
	                return array('error', "Error! Could not read list information. Please contact the System Administrator");
	            $totalCount = $totalRst['counter']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	            $totalRst = null;
            } else
            	$totalCount = null;
            // user grid filters
            $filteredCount = $totalCount;
            // PHP generated / developer filter - this needs to be here (after total count calc), else filtered excel export on group by queries breaks.
            if($phpFilter) { 
                $params = array_merge($params, $phpFilterParams);
                $criteria .= " AND ($phpFilter)";
            }
            if ($gridFilter) {
                $crit = $this->parseGridFilter($gridFilter, $params);
                if(is_array($crit))
                    return $crit; // error occured, $userCrit contains error message.
                elseif(!empty($crit))
                    $criteria .= " AND ($crit)";
            }
            //Build ORDER BY clause - Order of priority:  order by in user's request, pef_container, pef_sql 
            $orderStr = '';
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
                    $orderStr .= $this->sqlFields[$record['property']][0] . ' ' . $direction . ', ';
                }
                $orderStr = substr($orderStr, 0, strlen($orderStr) - 2);
            }
            if ($criteria) $whereCrit = ' WHERE ' . substr($criteria, 4);
            if ($havingCrit) $havingCrit = ' HAVING ' . substr($havingCrit, 4);
            // Template: MySql - Handle the pef_sql limit clause for paging purposes for database engines that support the LIMIT keyword. Used in eg SqlPdoTemplate.php.
        if($this->page === 1)
            $limit = ' LIMIT ' . $this->page_size;
        else
            $limit = ' LIMIT ' . ($this->page_size * ($this->page - 1)) .  ', ' . $this->page_size;
            // Template: MySQL - Main Sql statement to fetch many records of a pef_sql query (with no Group By clause) for database engines that recognise LIMIT. Used in eg SqlPdoTemplate.php. 
		$sql = " SELECT  id, name, vip 
        	FROM test_client
        	$whereCrit $orderStr $limit"; 
            $records = dibMySqlPdo::execute($sql, $this->dbIndex, $params, FALSE, PDO::FETCH_ASSOC);
            if($records === FALSE)
                return array('error', "Error! Could not obtain data. Please contact the System Administrator");
    DibApp::load('dibExamples', 'ContainerEvents.php', 'components');
$t = new ContainerEvents();
$result = $t->dynamicColumnsData('dibexGridDynamicColumns', $records, 'after readmany', $gridFilter, $criteria, $sql, $this, $filteredCount, $totalCount);
    if(isset($result[0]) && $result[0]==='error') return $result;
            if (($gridFilter || $phpFilter) && ($this->page===1 || $countMode==='all')) {
                $totalCrit = ($criteria) ? ' WHERE ' . ltrim($criteria, ' AND') : '';
                $sqlTotal =  " SELECT  Count(*) AS counter FROM test_client $totalCrit";
	            $totalRst = dibMySqlPdo::execute($sqlTotal, $this->dbIndex, $params, TRUE, PDO::FETCH_ASSOC);
	            if($totalRst === FALSE)
	                return array('error', "Error! Could not read list information. Please contact the System Administrator");
	            $filteredCount = $totalRst['counter']; //NOTE postgres returns lowercase fieldnames irrespective of how you specified them!
	            $totalRst = null;
            }
            return array($records, $filteredCount, $totalCount, array());
        } catch (Exception $e) {
			return array('error',"Error! Could not obtain data. Please contact the System Administrator");
        }
    }    
    public function getCaptions() {
    	return array("Id", "Name", "Vip" );
    }
    public function getSqlParts() {
		return array('model' => 'Sql',
				     'containerName' => "Sqlxx1xxdibexGridDynamicColumns",
					 'primary_field' => "",
                     'display_field' => "",                     
				     'select' => "id, name, vip",
				     'from' =>   "test_client",
				     'where' =>  "", 
				     'group_by' =>"",
				     'having' =>  "",
                     'order_by' => "",
                     'header'=> "",
                     'limit' =>  "",
                     'order_by_container' => "",
                     'filter' => ""
        );
    }
}
    