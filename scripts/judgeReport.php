<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$reportID = $_POST['reportID'];
	$isGuilty = $_POST['isGuilty'];
	$createDate = date('Y-m-d H:i:s');
	
	if (isset($reportID) && isset($isGuilty)) {
		$query = "SELECT * from report WHERE id=$reportID";
		$result = $dbController->runQuery($query);
		if (isset($result)) {
			$reportedID = $result[0]['userID'];
			
			$infoArray = array (
				array('status',$result[0]['status'],1),
				array('isGuilty',$result[0]['isGuilty'],$isGuilty),
				array('moderatorID',$result[0]['moderatorID'],$userID),
				array('prevCloseDate',$result[0]['closeDate'],$createDate),
			);
			
			if ($reportedID != $userID) {
				
				// Report Table
				$query = "UPDATE report SET status=1, isGuilty=$isGuilty, moderatorID=$userID, closeDate='$createDate' WHERE id=$reportID";
				$dbController->runQueryNoReturn($query);
				
				// Changelog
				foreach($infoArray as $row) {
					if ($row[1] != $row[2]) {
						$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, reportID) VALUES('$userID','report','$row[0]','$row[1]','$row[2]','$createDate','$reportID')";
						$dbController->runQueryNoReturn($query);
					}
				}

				echo json_encode("Report Status Updated");
			} else {
				echo json_encode("Cannot Moderate yourself");
			}
		} else {
			echo json_encode("Cannot find User");
		}
	} else {
		echo json_encode("");
	}
?>