<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$reportedID = $_POST['reportedID'];
	$reportedUsername = $_POST['reportedUsername'];
	
	if (isset($reportedID)) {
		if ($reportedID != -1) {
			$query = "SELECT * FROM accounts WHERE id=$reportedID";
			$result = $dbController->runQuery($query);
			
		} else {
			if (isset($reportedUsername)) {
				if ($reportedUsername != "") {
					$query = "SELECT * FROM accounts WHERE username='$reportedUsername'";
					$result = $dbController->runQuery($query);
					if (isset($result)) {
						$reportedID = $result[0]['id'];
					}
				}
			}
		}
		
		// Get Account Details
		if (isset($result)) {
			$query = "SELECT id FROM accounts WHERE invitedByID=$reportedID";
			$inviteCount = $dbController->contQuery($query);
			
			$query = "SELECT id FROM changelog WHERE userID=$reportedID AND (tableChanged='location' OR tableChanged='takeoff')";
			$changeCount = $dbController->contQuery($query);
			
			$query = "SELECT reportTypeID, status FROM report WHERE userID=$reportedID";
			$reportList = $dbController->runQuery($query);
			$totalInactive = 0;
			$totalActive = 0;
			$totalInactiveSeverity = 0;
			$totalActiveSeverity = 0;
			if (isset($reportList)) {
				foreach ($reportList as $key => $value) {
					$status = $value['status'];
					$reportTypeID = $value['reportTypeID'];
					$query = "SELECT severity FROM reporttype WHERE id=$reportTypeID";
					$severity = $dbController->runQuery($query);
					if (isset($severity)) {
						$severity = $severity[0]['severity'];
					} else {
						$severity = 0;
					}
					
					if ($status == 0) {
						$totalActive = $totalActive + 1;
						$totalActiveSeverity += $severity;
					} else if ($status == 1) {
						$totalInactive = $totalInactive + 1;
						$totalInactiveSeverity += $severity;
					}
				}
			}
			
			$result[0]['inviteCount'] = $inviteCount;
			$result[0]['changeCount'] = $changeCount;
			
			$result[0]['totalReports'] = $totalInactive + $totalActive;
			$result[0]['activeRep'] = $totalActive;
			$result[0]['inactiveRep'] = $totalInactive;
			
			$result[0]['totalSeverity'] = $totalInactiveSeverity + $totalActiveSeverity;
			$result[0]['activeSev'] = $totalActiveSeverity;
			$result[0]['inactiveSev'] = $totalInactiveSeverity;
			
			
			echo json_encode($result);
		} else {
			echo json_encode("");
		}
	} else {
		echo json_encode("");
	}
?>