<?php
/* Obtain dropdown list data from pef_sql statements */
class LSql_dibexqsClientWithContact_x1x {    
    public $displayErrorsInBrowser = FALSE;
	public $count = 0;
    protected $dbh = null;
    protected $dbIndex = null;
    function __construct() {
        // Specify database index in Conn.php, and require the relevant Database class
        $this->dbIndex = DIB::$ITEMLISTDATA['sql_db_id'];
        $dbClassPath = (DIB::$DATABASES[$this->dbIndex]['systemDropin']) ? DIB::$SYSTEMPATH.'dropins'.DIRECTORY_SEPARATOR : DIB::$DROPINPATHDEV;
        require_once $dbClassPath.'setData/dibMySqlPdo'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'dibMySqlPdo.php';
    }
    /**
	* Returns an array of (id, display_value) pairs generated by the SQL query linked to a specific $containerItemId
	* @param int $containerItemId 
	* @param int $page
	* @param int $page_size
	* @param string $query used with SQL LIKE to filter values. If of the form p__x it is assumed that x is the primary key value of a specific record's value to return. If d__x then x is the display value.
	* @param string $activeFilter name of the filter to apply
	* @param array $filterParams associative array of possible SQL parameter values. It is normally the flattened clientData array.
	* @param string $phpFilter SQL Where clause that may contain PDO params - if present it overrides where_clause and activeFilter
	* @param array $phpFilterParams associative array of PDO parameter values for $phpFilter
	* @param string $pageNoFromValue - NOT ACTIVE
	* @param boolean $showUsedOnly return only values used in the database
	* 
	* @return array of (id, display_value) pairs
	*/
    public function getList($containerItemId, $page=1, $page_size=0, $query='', $activeFilter=null, $filterParams=null, $phpFilter='', $phpFilterParams=array(), $pageNoFromValue=null, $showUsedOnly = false) {
    	if(empty($page) || $page<=0) $page = 1;
        if(empty($page_size) || $page_size<0) $page_size = 40;
        $order_by = '';
        $criteria = '';
        $params = array();
        $group_by = '';
        $having = '';
        $permCrit = '';
        ;
        // Set SQL parts 
        $totalSql = " SELECT Count(*) AS totalcount FROM test_client c INNER JOIN test_client_contact cc on cc.client_id = c.id ";
        $sql = "    DISTINCT c.id AS id,  c.name AS id_display_value ";
        $from_clause = "test_client c INNER JOIN test_client_contact cc on cc.client_id = c.id";
        $display_field = "c.name";
        $pkey = "c.id";
        $order_by = ($phpFilter==='') ? " ORDER BY c.name" : ''; 
        // Process user filter
        if(!empty($query)) {
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
                // If where_clause had any PDO parameters that could be for permission purposes, include the criteria 
                if($permCrit !== '') {
					$criteria .= ' AND (' . $permCrit . ')';
					$params = array_merge($params, $permParams); 
                }
            } else {
                $params[":f1"] = '%'.$query.'%';
                if($criteria === '')
                    $criteria = $display_field . ' LIKE :f1';
                else
                    $criteria = "($criteria) AND ($display_field LIKE :f1)";
            }
        }
        // *** NOTE: if phpFilter present it overrides where_clause and activeFilter
        if(!empty($phpFilter)) {
        	$criteria = str_replace(array('dib__Pkey', 'dib__DisplayField'), array($pkey, $display_field), $phpFilter);
        	$params = $phpFilterParams;
        }
        if($criteria) $criteria = ' WHERE ' . $criteria;
        if($having) $having = ' HAVING ' . $having;
            // Template: MySql - Get SQL for paging purposes for database engines that support the LIMIT keyword. Used in eg Table.php.
    if($page === 1)
        $limit = ' LIMIT ' . $page_size;
    else
        $limit = ' LIMIT ' . ($page_size * ($page - 1)) .  ', ' . $page_size;    
$sql = " SELECT $sql FROM $from_clause $criteria $group_by $having $order_by $limit";
        // Get the data
        $records = dibMySqlPdo::execute($sql, $this->dbIndex, $params, FALSE, PDO::FETCH_ASSOC);
        // Get $filteredCount
        if(substr($query, 0, 3) === 'p__' || substr($query, 0, 3) === 'd__')
            $filteredCount = 1;
        else {
            if($group_by === '') 
                $filterCountRst = dibMySqlPdo::execute($totalSql . $criteria, $this->dbIndex, $params, TRUE, PDO::FETCH_ASSOC);
            else 
                $filterCountRst = dibMySqlPdo::execute("SELECT FOUND_ROWS() AS totalcount", $this->dbIndex, null, TRUE, PDO::FETCH_ASSOC);
            if($filterCountRst === FALSE)
                return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
            $filteredCount = intval($filterCountRst["totalcount"]);			
        }
        if($records === FALSE)
            return array('error', "Error! Could not read dropdown data information. Please contact the System Administrator");
        return array($records, $filteredCount);
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
                $var = (!array_key_exists(1, $pathArr)) ? null : trim($pathArr[1]);
                $result = DibApp::loadPath(trim($pathArr[0]), TRUE, TRUE); // note, functionName must be included, and function must be in /components folder.
                if($result[0]==='error') {
                    Log::err("A PHP path was used to substitute SQL, but could not be resolved. Error returned:\r\n" . $result[1]);
                    return FALSE;
                }
                list($filePath, $className, $functionName) = $result;
                // Note the first parameter contains both containerName and itemId:
                $callResult = $className::$functionName(DIB::$ITEMLISTDATA['container_name'].'/'.DIB::$ITEMLISTDATA['pef_item_id'], $params, $filterParams, $sql, $var);
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
}
