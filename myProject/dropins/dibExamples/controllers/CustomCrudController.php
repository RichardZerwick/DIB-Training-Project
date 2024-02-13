<?php

class CustomCrudController extends Controller {

    private $filterCriteria = ''; // global used to filter records by provided query string

    // Recursive function to get all the parent items up the tree until a root node is found
    private function getParents($parentId) {
        $sql = "SELECT id, manager_id FROM test_staff WHERE id = :id";
        $record = Database::fetch($sql, array(':id'=>$parentId), DIB::DBINDEX);

        if ($record['manager_id'] == $record['id'] || empty($record['manager_id']))
            return array($record['id']);
        else
            return array_merge(array($record['id']), $this->getParents($record['manager_id']));
    }

    // nav/dibexComponentsSelects -> autoCompleteTree
    // Returns the child nodes for a given node
    public function getTree($containerName, $containerItemId, $clientData, $node='root', $query=null, $calledRecursively=false) {
    
        $query = trim((string)$query);

        if(empty($node))
            return $this->records(array());

        $node = urldecode($node); // Incase $node is text with eg space characters. In our example it won't happen.

        if($node === 'root') {

            if (!empty($query)) {

                // We need to get a list of id's whose children somewhere down the line have a name like $query...
                // Loop through records that are like query
                $critList = array();
                $sql = "SELECT id, manager_id
                        FROM test_staff
                        WHERE CONCAT(first_name, ' ', last_name) LIKE :query";
                $treeItems = Database::execute($sql, array(':query' => '%'.$query.'%'), DIB::DBINDEX);

                // Now use a recursive function to find a list of each of these records' ancestors up the tree
                // Remember all these id values in $critList
                foreach ($treeItems as $key => $record) {
                    $critList[] = $record['id'];

                    if ($record['manager_id'] !== $record['id'] && !empty($record['manager_id'])) {
                        $critList = array_merge($critList, $this->getParents($record['manager_id']));
                    }
                }

                // Get unique values
                $critList = array_unique($critList);

                // Got the list - use sql IN operator...
                $this->filterCriteria = (!empty($critList)) ? ' AND ts.id IN (' . implode(',', $critList) . ')' : ' AND ts.id IS NULL';
            }
            
            $crit = "ts.manager_id IS NULL"; // defines our root nodes

            /* How to force the use of a certain node's children for a given container:
            if($containerName === 'xxx' && $node === 'root')
                $crit = "ts.manager_id = 123";
            */

        } elseif ($node != (string)(int)$node) { // Invalid in our example - probable hacking
            return $this->records(array());

        } else {
            // Get child nodes
            $crit = "ts.manager_id = $node";
        }

        $crit .= $this->filterCriteria; // Add criteria (if any) that allows only matched records and their ancestors

        // Get records and count each records' children
        $sql = "SELECT ts.id, CONCAT(ts.first_name, ' ', ts.last_name) as `text`, COUNT(children.id) as childCount
                FROM test_staff ts
                    LEFT JOIN test_staff children ON children.manager_id = ts.id
                WHERE $crit
                GROUP BY ts.id, ts.first_name, ts.last_name";
        $treeItems = Database::execute($sql, [], DIB::DBINDEX);

        // We can either return just this nodes' children, or the whole tree
        // So we decide that if the user enters filter criteria we should return the whole tree

        // Loop through records and build array
        foreach ($treeItems as $key => $record) {
            $childCount = (int)$record['childCount'];

            unset($treeItems[$key]['childCount']);

            $treeItems[$key]['leaf'] = ($childCount > 0) ? FALSE : TRUE;

            if($childCount > 0 && !empty($this->filterCriteria)) {
                // Get child records recursively
                $treeItems[$key]['expanded'] = TRUE;
                $treeItems[$key]['children'] = $this->getTree($containerName, $clientData, $record['id'], null, TRUE);
            } else {
                // We're not getting child records yet, so we can't expand
                $treeItems[$key]['expanded'] = FALSE;
            }

        }

        if($calledRecursively)
            return $treeItems;
        else
            return $this->records($treeItems);
    }
}