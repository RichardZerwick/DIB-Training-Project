<?php
class LTable_test_client_5343_x1x {
    protected $dbIndex = null; 
    function __construct() {
        // Specify database index in Conn.php, and require the relevant Database class
        $this->dbIndex = DIB::$ITEMLISTDATA['tb_db_id'];
        $dbClassPath = (DIB::$DATABASES[$this->dbIndex]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
    }
    /**
	* Returns the specified $page of data from a list consisting of primary key - display value pairs
	* @param undefined $containerItemId - the id of the pef_item record for which the list is returned
	* @param undefined $page - the page number
	* @param undefined $page_size - the page size
	* @param undefined $query - optional string used for filtering records: display values must contain this string. If of the form p__x it is assumed that x is the primary key value of a specific record's value to return. If d__x then x is the display value.
	* @param undefined $activeFilter - optional named filter to be applied
	* @param undefined $filterParams - associative array of possible SQL parameter values. It is normally the flattened clientData array.
	* @param undefined $pageNoFromValue - if specified, then the page no is returned where this value can be found in the list
	* @param boolean $showUsedOnly whether only distinct used data is showed in filter list.
	* 
	* @return
	*/
    public function getList($containerItemId, $page=1, $page_size=0, $query=null, $activeFilter=null, $filterParams=null, $phpFilter='', $phpFilterParams=array(), $pageNoFromValue=null, $showUsedOnly = false) {
        try {
            if(empty($page) || $page<=0) $page = 1;
            if(empty($page_size) || $page_size<0) $page_size = 40; // This ensures that Item Setting 'selectLimit' takes precedence, followed by global server-side setting in pef_setting.
            // * Note, a bound dropdown can only store one value... this value will reference a single primary key field, or a single field of a composite primary key 
            $criteria = ''; // ***TODO (minor) - note that activeFilter (if present) overrides phpFilter
            $params = array();
            $sql = "`test_client`.`id` AS id, `test_client`.`name` AS `id_display_value`";
            $from_clause = "`test_client`";
            $display_field = "`test_client`.`name`";
            $pkey = "`test_client`.`id`";
            $order_by = (empty($phpFilter)) ? " ORDER BY `test_client`.`name`" : '';
            $totalSql = "SELECT count(*) AS totalcount FROM `test_client` ";
            // Add sql to show only used values (especially for filter dropdowns) - does not work for mssql...
            if($showUsedOnly) { 
            	$itemTables = array(278845=>"`test_client_contact`.`client_id`", 278952=>"`test_client_contact`.`client_id`", 279323=>"`test_client_contact`.`client_id`", 282639=>"`test_client_contact`.`client_id`", 282642=>"`test_client_contact`.`client_id`", 288937=>"`test_client_contact`.`client_id`", 288958=>"`test_client_contact`.`client_id`", 289079=>"`test_client_contact`.`client_id`", 289105=>"`test_client_contact`.`client_id`", 289129=>"`test_client_contact`.`client_id`", 289186=>"`test_client_contact`.`client_id`", 289202=>"`test_client_contact`.`client_id`", 289951=>"`test_client_contact`.`client_id`", 275608=>"`test_project`.`client_id`", 275615=>"`test_project`.`client_id`", 284907=>"`test_project`.`client_id`");
            	if(isset($itemTables[$containerItemId])) {
            		$foreignParts = explode('.', $itemTables[$containerItemId]);
					$addSql = " INNER JOIN " . $foreignParts[0] . " dib___F ON dib___F." . $foreignParts[1] . " = `test_client`.`id` ";
					if(stripos(substr($sql, 0, strpos($sql, '`')), 'DISTINCT') === FALSE) $sql = 'DISTINCT ' . $sql;
					$from_clause .= $addSql;
					$totalSql = str_replace('SELECT count(*)', "SELECT count(DISTINCT $pkey)", $totalSql) .  $addSql;
				}
			}
            //***TODO - add a check for when no query could be found matching parameters - display helpful error to Admin user 
            if($query) {
                $query = urldecode($query);
                $pkeyOrDisplayPrefix = substr($query, 0, 3);
                if($pkeyOrDisplayPrefix === 'p__' || $pkeyOrDisplayPrefix === 'd__') {
                    // handle lookup of a single record for a specific primary key or display value
                    // used when eg dropdown value is set programmatically or a form record loads
                    $sField = ($pkeyOrDisplayPrefix === 'p__') ? $pkey : $display_field;
                    $params = array(":f1" => substr($query, 3));
                    if(in_array($params[':f1'], array('null', 'undefined')))
                        $params = array(":f1" => NULL);
                    $criteria = " $sField = :f1";
                } else { 
                    $params[":f1"] = $query.'%';
                    if($criteria === '')
                        $criteria = $display_field . ' LIKE :f1';
                    else
                        $criteria = "($criteria) AND ($display_field LIKE :f1)";
                }
            }
            if($pageNoFromValue) {
            	// Determine page no of pkey value. First need to find corresponding display value
            	$rst = dibMySqlPdo::execute("SELECT `test_client`.`name` AS dib__Display FROM `test_client` WHERE `test_client`.`id` = :pkey", $this->dbIndex, array(':pkey'=>$pageNoFromValue), TRUE, PDO::FETCH_ASSOC);
	            if($rst === FALSE)
	                return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
				if($criteria) 
					$criteria = " WHERE $criteria AND `test_client`.`name` <= :dib__Display $order_by";
				else 
					$criteria = " WHERE `test_client`.`name` <= :dib__Display $order_by";
				$params[':dib__Display'] = $rst['dib__Display'];
			} 
			// *** NOTE: if phpFilter present it overrides where_clause and activeFilter
	        if(!empty($phpFilter)) {
	        	$criteria = str_replace(array('dib__Pkey', 'dib__DisplayField'), array($pkey, $display_field), $phpFilter); 
	        	$params = $phpFilterParams;
	        }
			if($criteria) $criteria = ' WHERE ' . $criteria;
            $databaseId = $this->dbIndex;
            // Get Count
            if(substr($query, 0, 3) === 'p__' || substr($query, 0, 3) === 'd__')
                $filteredCount = 1;
            else {
                $filterCountRst = dibMySqlPdo::execute($totalSql . $criteria, $databaseId, $params, TRUE, PDO::FETCH_ASSOC);
                if($filterCountRst === FALSE)
                    return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
                $filteredCount = intval($filterCountRst["totalcount"]);
            }
            if($pageNoFromValue) return array(array(), ceil($filteredCount / $page_size)); 
            $group_by = '';
            $having = '';
                // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg Table.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    
$sql = " SELECT $sql FROM $from_clause $criteria $group_by $having $order_by $limit";
            $records = dibMySqlPdo::execute($sql, $databaseId, $params, FALSE, PDO::FETCH_ASSOC);
            if($records === FALSE)
                return array('error', "Error! Could not obtain data for the list. Please contact the System Administrator");
            return array($records, $filteredCount);
        } catch (Exception $e) {        	
			return array('error', "Error! Could not obtain data for the list. Please contact the System Administrator");
        }
    }
}