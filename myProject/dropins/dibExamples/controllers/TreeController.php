<?php

/**
 * This controller is used for CRUD operations for dibTree
 *   
 */
class TreeController extends Controller {

    private $critList = [];
    private $queryCriteria = '';
    private $childCriteria = '';
    
    /**
     * Get tree nodes
     * 
     * @param type $containerName
     * @param type $clientData
     * @param type $node
     * @param type $callRecursively
     */
    public function read($containerName, $clientData=null, $node=null, $callRecursively=false) {

        if(empty($containerName) || empty($node) || is_array($node))
            return $this->validResult(null, null, null, null, null, array());

        // ensure $node is a string since we'll use $node[0] below
        $node = (string)$node;
     
        list($filter) = DibApp::clientData('alias_self', array('filter'));

        if($node === 'root') {

            $this->queryCriteria = '';
            $criteria = '';
            $params = [];
            
            if(!empty($filter)) {
                // We need to get a list of id's whose children somewhere down the line have a name like $query...
                // Loop through records that are like query
                $this->critList = array();

                $sqlS = "SELECT id, manager_id
                        FROM test_staff 
                        WHERE CONCAT(s.first_name, ' ', s.last_name) LIKE :filter";
                $treeItems = Database::execute($sqlS, array(':filter' => '%'.$filter.'%'));
                
                // Now use a recursive function to find each of these records' ancestor nodes
                
                foreach ($treeItems as $key => $record)
                    $this->addtoCritList($record['id'], $record['manager_id'], $node);

                // Got the list - set $this->queryCriteria which will be used in all recursive calls
                $this->queryCriteria = (!empty($this->critList)) ? 'AND s.id IN (' . implode(',', $this->critList) . ')' : 'AND s.id IS NULL' ;
                $this->childCriteria = (!empty($this->critList)) ? 'AND ss.id IN (' . implode(',', $this->critList) . ')' : 'AND ss.id IS NULL' ;

                // Get criteria for root node 
                $criteria = "AND CONCAT(s.first_name, ' ', s.last_name) LIKE CONCAT('%', :filter, '%')";
                $params = [
                    'filter' => $filter,
                ];
            }

            $sql = "SELECT s.id, CONCAT(s.first_name, ' ', s.last_name) as name, s.tree_expanded as expanded, count(ss.id) as childCount
                    FROM test_staff s
                        LEFT JOIN test_staff ss ON ss.manager_id = s.id {$this->childCriteria}
                    WHERE s.manager_id IS NULL $criteria
                    GROUP BY s.id, s.first_name, s.last_name, s.tree_expanded
                    ORDER BY s.tree_order_no
                    ";

        } else {

            // Remember expanded status
            if(empty($this->queryCriteria))
                Database::execute("UPDATE test_staff SET tree_expanded=1 WHERE id = :id", [':id'=>$node]);
         
            $sql = "SELECT s.id, CONCAT(s.first_name, ' ', s.last_name) as name, s.manager_id, s.tree_expanded as expanded, count(ss.id) as childCount
                    FROM test_staff s
                        LEFT JOIN test_staff ss ON ss.manager_id = s.id {$this->childCriteria}
                    WHERE s.manager_id = :id {$this->queryCriteria}
                    GROUP BY s.id, s.first_name, s.last_name, s.manager_id, s.tree_expanded
                    ORDER BY s.tree_order_no
                    ";
            $params =[':id' => $node];
        }

        $treeItems = Database::execute($sql, $params);

        foreach ($treeItems as $key => $record) {
            
            $treeItems[$key]['has_children'] = ((int)$record['childCount'] > 0); 
            $treeItems[$key]['checked'] = false;
            $treeItems[$key]['expanded'] = ($record['expanded'] || (!empty($this->queryCriteria) && $record['child_counter'] > 0)) ? true : false;
            $treeItems[$key]['can_have_children'] = true;

            $treeItems[$key]['dragable'] = true;
            $treeItems[$key]['drop_target'] = true;
            $treeItems[$key]['has_check_boxes'] = true;
            $treeItems[$key]['node_menu'] = true;
            $treeItems[$key]['open_on_click'] = true;
            $treeItems[$key]['close_on_click'] = true;

            if($record['childCount'] > 0 && $treeItems[$key]['expanded']) {
                // recursively get child records
                $treeItems[$key]['records'] = $this->read($containerName, $clientData, $record['id'], true);
            }

            unset($treeItems[$key]['childCount']);
        }
        
        if(!empty($callRecursively)) 
            return $treeItems;
        else
            return $this->validResult(null, null, null, null, null, $treeItems);
    }

    private function addtoCritList($id, $parentId, $node) {
        $found = array_search($id, $this->critList);
        if(!$found)
            $this->critList[] = $id;

        if ($parentId === $node || empty($parentId) || $found)
            return TRUE;
          
        $sql = "SELECT id, manager_id FROM test_staff WHERE id = :id";
        $treeItems = Database::fetch($sql, array(':id'=>$parentId));

        return $this->addtoCritList($treeItems['id'], $treeItems['manager_id'], $node);
    }

    public function closeTreeNode($containerName, $clientData = null, $node='root') {
        if (empty($node) || $node != (string)(int)$node)
            return $this->validResult(NULL);
        
        Database::execute("UPDATE test_staff SET tree_expanded = 0 WHERE id = $node");
        return $this->validResult(NULL);
    }

    public function showSelected($containerName, $itemEventId, $clientData=null, $triggerType=null, $id=null, $itemAlias=null) {

        return $this->validResult(null, json_encode($clientData), 'dialog');

    }

    /**
     * Drop a specific node on-to another
     * @param string $clientData
     * @param string $sourceTreeId id of the tree where action originated
     * @param string $dropPosition after/before/append
     * @param integer $dropNodeId target node (works together with $dropPosition)
     * @param type $nodeId node being dropped
     * @param string $parentId 'root' or integer - parent node of $dropNodeId if after/before
     */
    function drop($containerName, $clientData, $sourceTreeId, $dropPosition, $dropNodeId, $nodeId, $parentId, $itemEventId=1) {

        if($dropNodeId != (string)(int)$dropNodeId || $nodeId != (string)(int)$nodeId)
            return $this->invalidResult('Invalid request');
        
        if($dropNodeId === $nodeId || $parentId === $nodeId)
            return $this->invalidResult('Nodes cannot be dropped on themselves.');
         
        $params = array();
        
        if ($dropPosition !== 'append' && is_null($parentId)) {
            // dropping onto root node
            $parentCondition = "manager_id IS NULL ";
            $parentId = NULL;

        } else {
            if ($dropPosition === 'append') $parentId = $dropNodeId;

            $parentCondition = "manager_id = :parentId ";
            $params = [':parentId' => $parentId];
        }
        
        if($dropPosition === 'append') {
            $sql = "SELECT MAX(tree_order_no) AS orderPos 
                    FROM test_staff
                    WHERE " . $parentCondition;
            
            $dropPosition = 'after';
            $rst = Database::fetch($sql, $params);
            
        } else {
            $sql = "SELECT tree_order_no AS orderPos 
                    FROM test_staff
                    WHERE id = :dropNodeId";
            $rst = Database::fetch($sql, [':dropNodeId' => $dropNodeId]);
        }
        
        if(Database::count() < 0)
            return $this->invalidResult('Invalid drop request - action cancelled.');

        $orderNo = (empty($rst['porderPos'])) ? 10 : $rst['porderPos'];
        
        if ($dropPosition === 'after')
            $orderOperator = '>';
        else
            $orderOperator = '>=';

        // ***TODO change to crud actions OR check permissions on orderNo field and record criteria and handle AuditTrail... 
        
        $sql = "UPDATE test_staff SET tree_order_no = tree_order_no + 10 
                WHERE $parentCondition AND tree_order_no $orderOperator $orderNo";
        $rst = Database::execute($sql, $params);
        if($rst === FALSE)
            return $this->invalidResult('Warning, could not update database. Database returns: ' . Database::lastErrorAdminMsg());

        
        // Dragging Item Tree node to another parent
        // Check if dropping a parent on a child
        $result = $this->checkNodePos($nodeId, $parentId);
        if($result === false)
            return $this->invalidResult('Cannot move a parent node into one of its own child nodes. Please try again.');    
        if($result === 'max level') {
            Log::err("Could not move item $nodeId to parent $parentId - If we traverse the tree going up, there is either a recursive relationship, or more than 100 levels of hierarchy involved.");
            return $this->invalidResult('There seems be to an invalid recursive relationship in the tree hierarchy. Please check error logs for details.');
        }             
        
        $uParams = array(
            '!nodeId' => $nodeId, 
            'manager_id' => $parentId,
            'tree_order_no' => ($orderNo + 5)
        );

        $rst = Database::update('test_staff', $uParams, DIB::DBINDEX, 'id = :nodeId', 'detail');
        if($rst === FALSE) 
            return $this->invalidResult('Warning, could not update database. Database returns: ' . Database::lastErrorAdminMsg());
        
        return $this->validResult(null);
    }

    private function checkNodePos($nodeId, $parentId, $level=0) {
        if(empty($parentId)) return true;
        $sql = "SELECT id, manager_id FROM test_staff WHERE id=$parentId";
        $rst = Database::fetch($sql);

        if(empty($rst['manager_id']))
            return true;
        if($level > 100)
            return 'max level';
        if((int)$rst['manager_id'] === (int)$nodeId)
            return false;

        return $this->checkNodePos($nodeId, $rst['manager_id'], ($level + 1));
    }
    
}
