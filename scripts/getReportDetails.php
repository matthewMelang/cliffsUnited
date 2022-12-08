<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$reportedID = $_POST['reportedID'];
	
	if (isset($reportedID)) {
		$query = "SELECT * FROM report WHERE userID=$reportedID";
		$result = $dbController->runQuery($query);
		foreach ($result as $key => $value) {
			
			// Get Reported username
			$query = "SELECT username FROM accounts WHERE id=".$value['userID'];
			$reportedUsername = $dbController->runQuery($query);
			if (isset($reportedUsername)) {
				$reportedUsername = $reportedUsername[0]['username'];
			} else {
				$reportedUsername = "";
			}
			
			// Get Creator Username
			$query = "SELECT username FROM accounts WHERE id=".$value['creatorID'];
			$creatorUsername = $dbController->runQuery($query);
			if (isset($creatorUsername)) {
				$creatorUsername = $creatorUsername[0]['username'];
			} else {
				$creatorUsername = "";
			}
			
			// Get Moderator Username
			$query = "SELECT username FROM accounts WHERE id=".$value['moderatorID'];
			$modUsername = $dbController->runQuery($query);
			if (isset($modUsername)) {
				$modUsername = $modUsername[0]['username'];
			} else {
				$modUsername = "";
			}
			
			// Get Report Type
			$reportTypeID = $value['reportTypeID'];
			$query = "SELECT description FROM reporttype WHERE id=".$reportTypeID;
			$reportType = $dbController->runQuery($query);
			if (isset($reportType)) {
				$reportType = $reportType[0]['description'];
			} else {
				$reportType = "";
			}
			
			// Get Report Content
			$tblName = $value['tbl'];
			$tblID = $value['tableID'];
			$reportContent = null;
			
			if ($reportTypeID == 1) {			// Inappropriate PFP
				$query = "SELECT url, createDate, isActive FROM media WHERE forAccountID=$reportedID AND isActive=1";
				$reportContent = $dbController->runQuery($query);
				
			} else if ($reportTypeID == 2) {	// Inappropriate profile Desc
				$query = "SELECT tableField, changedFrom, changedTo, dateChanged FROM changelog WHERE userID=$reportedID AND tableChanged='accounts' AND tableField='bio'";
				$reportContent = $dbController->runQuery($query);
				
			} else if ($reportTypeID == 3 || $reportTypeID == 4) {	// Spam or Hate Speech
				if ($tblName == "thread") {
					$query = "SELECT senderID, description, createDate FROM message WHERE threadID=$tblID";
					$reportContent = $dbController->runQuery($query);
					
				} else if ($tblName == "comments") {
					$query = "SELECT locationID, description FROM comments WHERE userID=$reportedID";
					$reportContent = $dbController->runQuery($query);
					
					$locationID = $reportContent[0]['locationID'];
					$locationDesc = "";
					if ($locationID != null && $locationID != 0) {
						$query = "SELECT description FROM location WHERE id=$locationID";
						$locationDesc = $dbController->runQuery($query);
						if (isset($locationDesc)) {
							$locationDesc = $locationDesc[0]['description'];
						}
					}
					$reportContent['locationName'] = $locationDesc;
					
				}
			} else if ($reportTypeID == 5) {	// Vandalism
				$query = "SELECT dateChanged FROM changelog WHERE id=$tblID";
				$dateChanged = $dbController->runQuery($query);
				$dateChanged = $dateChanged[0]['dateChanged'];
				
				$query = "SELECT * from changelog WHERE userID=$reportedID AND dateChanged='$dateChanged' AND (tableChanged='location' OR tableChanged='takeoff')";
				$reportContent = $dbController->runQuery($query);
				
				$locationID = $reportContent[0]['locationID'];
				$locationDesc = "";
				if ($locationID != null && $locationID != 0) {
					$query = "SELECT description FROM location WHERE id=$locationID";
					$locationDesc = $dbController->runQuery($query);
					if (isset($locationDesc)) {
						$locationDesc = $locationDesc[0]['description'];
					}
				}
				
				$takeoffID = $reportContent[0]['takeoffID'];
				$takeoffDesc = "";
				if ($takeoffID != null && $takeoffID != 0) {
					$query = "SELECT description FROM takeoff WHERE id=$takeoffID";
					$takeoffDesc = $dbController->runQuery($query);
					if (isset($takeoffDesc)) {
						$takeoffDesc = $takeoffDesc[0]['description'];
					}
				}
				$reportContent['locationName'] = $locationDesc;
				$reportContent['takeoffName'] = $takeoffDesc;
				
			}
			
			$result[$key]['modUsername'] = $modUsername;
			$result[$key]['reportedUsername'] = $reportedUsername;
			$result[$key]['creatorUsername'] = $creatorUsername;
			$result[$key]['reportType'] = $reportType;
			$result[$key]['reportContent'] = $reportContent;
		}
		
		
		echo json_encode($result);
	} else {
		echo json_encode("");
	}
?>