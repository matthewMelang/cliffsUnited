<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Get Location ID by Name
	if (isset($_POST['location'])) {
		$locationName = $_POST['location'];
		$query = "SELECT id, createDate, lastUpdate, updatedBy FROM location WHERE description='".$locationName."'";
		$result = $dbController->runQuery($query);
		
		
		if (isset($result)) {
			// Get Username of Last Updated By
			$updatedBy = $result[0]['updatedBy'];
			$query = "SELECT username FROM accounts WHERE id=$updatedBy";
			$updatedByUser = $dbController->runQuery($query);
			if (isset($updatedByUser)) {
				$updatedByUser = $updatedByUser[0]['username'];
			} else {
				$updatedByUser = "System";
			}
			$result[0]['updatedByUser'] = $updatedByUser;
			
			// Get Total number of Versions
			$locID = $result[0]['id'];
			$query = "SELECT DISTINCT dateChanged FROM changelog WHERE locationID=$locID ORDER BY dateChanged ASC";
			$versionCount = $dbController->contQuery($query);
			$versionList = $dbController->runQuery($query);
			
			$result[0]['versionCount'] = 0;
			if (isset($versionCount)) {
				$result[0]['versionCount'] = $versionCount;
			}
			
			$result[0]['versionList'] = "";
			if (isset($versionList)) {
				$result[0]['versionList'] = $versionList;
			}
			
			// Get Total number of Contributors
			$query = "SELECT DISTINCT userID FROM changelog WHERE locationID=$locID";
			$contCount = $dbController->contQuery($query);
			$result[0]['contCount'] = $contCount;
			
			echo json_encode($result);
		} else {
			echo json_encode("");
		}
	} else {
		echo json_encode("");
	}
?>