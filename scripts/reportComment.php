<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$commentID = $_POST['commentID'];
	$reportTypeID = $_POST['reportTypeID'];
	
	// Get User ID of commentID
	$query = "SELECT userID FROM comments WHERE id=$commentID";
	$commenterID = $dbController->runQuery($query);
	if (isset($commenterID)) {
		$commenterID = $commenterID[0]['userID'];
		
		if ($commenterID != $userID) {
			if (isset($reportTypeID)) {
				$createDate = date('Y-m-d H:i:s');
				
				$query = "INSERT INTO report(userID,creatorID,reportTypeID,tbl,tableID,createDate) VALUES('$commenterID','$userID','$reportTypeID','comments','$commentID','$createDate')";
				$dbController->runQueryNoReturn($query);
				
				echo "Report Submitted.";
			} else {
				echo "Report Type not set.";
			}
		} else {
			echo "Cannot report yourself.";
		}
	} else {
		echo "Cannot find original commenter.";
	}
?>