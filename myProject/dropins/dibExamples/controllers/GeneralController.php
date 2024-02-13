<?php 

class GeneralController extends Controller {

    // Used by Examples to view Designer records
    public function gotoDesigner($containerName, $container, $itemAlias='', $itemEventId=1) {
        if(empty($container))
            return $this->invalidResult('Invalid container. Please refresh the browser and try again.');
        
        if($container == (string)(int)$container)
            $containerId = $container;

        else {
            $rst = Database::fetch("SELECT id FROM pef_container WHERE name = :name", array(':name'=>$container));
            if(empty($rst))
                return $this->invalidResult('Could not find container. Please check configuration');

            $containerId = $rst['id'];
        }

        ClientFunctions::addAction($actionList, 'open-url', array('url'=>'/nav/dibDesigner/?dibDesigner.containerId=' . $containerId));
        return $this->validResult($actionList);
    }

    // Returns randam values for chart on dibexOverview -> Reports
    public function addDataToChart($containerName, $itemEventId, $clientData=array(), $itemAlias='') {
        return $this->records(
            array(
                'newValue' => rand(0, 9),
                'newLabel' => Date('H:i:s')
            )
        );
    }

    // Used on eg dibexClientData to display clientData
    public function showClientData($containerName, $itemEventId, $clientData=null, $triggerType=null, $itemId=null, $itemAlias=null) {
        
        $str = json_encode($clientData, JSON_PRETTY_PRINT);
        if(empty($str))
            return $this->invalidResult('No values, or error in clientData');

        return $this->validResult(null, '<pre>' . $str . '</pre>', 'dialog');
    }

    // /nav/dibexCustomComponentInGrid
    // Handles storing of custom component data
    public function startDateChange($containerName, $clientData=null, $recordData=null, $itemAlias=null) {
        // We need to do all the necessary validation and security checks

        if($recordData['column'] !== 'start_date' || empty($recordData['id']))
            return $this->invalidResult('Invalid request. Please refresh the browser and try again.');

        $id = $recordData['id'];
        if($id != (string)(int)$id)
            return $this->invalidResult('Invalid request. Please refresh the browser and try again.');

        if(empty($recordData['new_date']))
            $date = null;
        else {
            $date = $recordData['new_date'];

            if(!DValidate::_date($date, 'Y-m-d'))
                return $this->invalidResult('Invalid date. Please reload the page and try again.');
        }

        // Now that we know values don't contain malicious code that can be executed in the browser, 
        // we can include them in a message sent to the browser:
        $msg = "Update record $id, set Start Date to $date";

        $params = [
            'date' => $date,
            'id' => $id
        ];

        $rst = Database::execute("UPDATE test_client SET start_date = :date WHERE id = :id", $params);
        
        $msg = ($rst === false) ? "Failed: $msg" : "Success: $msg";
        
        return $this->validResult(null, $msg, 'dialog');
    }

    // /nav/dibexPermSegmentDataByUser
    // Demonstrates how to ensure that staff have access to only their data returned by this function
    public function viewMyReport($containerName, $itemEventId, $clientData=null) {
        $staffId = DibApp::clientData('a_s', 'id');

        // See the pef_login table and /configs/DibUserParams.php where staff_id is defined and retrieved for the PHP session

        if(empty($staffId) || DIB::$USER['staff_id'] != $staffId)
            return $this->invalidResult('Invalid request');

        // Some code retrieving report data for the current staff user identifief by $staffId
        $report = DIB::$USER['first_name'] . ' ' . DIB::$USER['last_name'] . '<br><br>yada yada yada ... ';

        return $this->validResult(null, $report, 'dialog');
    }

    

    
}