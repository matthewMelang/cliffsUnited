<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$reportedID = $_POST['reportedID'];
	$changelogID = $_POST['changelogID'];
	$reportTypeID = $_POST['reportTypeID'];
	
	// Get User ID of commentID
	if ($reportedID != $userID) {
		if (isset($reportTypeID)) {
			if (isset($changelogID)) {
				$createDate = date('Y-m-d H:i:s');
			
				$query = "INSERT INTO report(userID,creatorID,reportTypeID,tbl,tableID,createDate) VALUES('$reportedID','$userID','$reportTypeID','changelog','$changelogID','$createDate')";
				$dbController->runQueryNoReturn($query);
			
				echo "Report Submitted.";
			} else {
				echo "Cannot find Changelog Item.";
			}
		} else {
			echo "Report Type not set.";
		}
	} else {
		echo "Cannot report yourself.";
	}
?>