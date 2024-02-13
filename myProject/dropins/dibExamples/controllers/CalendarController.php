<?php

class CalendarController extends Controller {
	
	public function read($containerName, $clientData=null) {

		$staff = (isset($clientData['selectStaff'])) ? $clientData['selectStaff'] : [];
		$crit = '';

		if(!empty($staff)) {
			foreach($staff as $s) {
				$crit .= $s['id'] . ',';
			}
			$crit = rtrim($crit, ',');
			$crit = "WHERE s.id IN ($crit)";
		}

		$sql = "SELECT sp.id, sp.date_from, sp.date_to, s.color,
					CONCAT(s.first_name, ' ', s.last_name, ' - ', sp.notes) as caption
		        FROM test_staff_project sp
					LEFT JOIN test_staff s ON sp.staff_id = s.id
				$crit
		        ";
		
		$rst = Database::execute($sql);
		
		if ($rst === FALSE)
			return $this->invalidResult('Could not read calendar events. Please contact the System Administrator.');

		$total = count($rst);
		$filteredTotal = $total;

		return $this->validResult(null, null, null, null, null, $rst, $total, $filteredTotal);
		
	}
	
	
	
}