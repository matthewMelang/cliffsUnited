<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$profileID = $_POST['profileID'];
	$reportTypeID = $_POST['reportTypeID'];
	
	// Get User ID of commentID
	if ($profileID != $userID) {
		if (isset($reportTypeID)) {
			$createDate = date('Y-m-d H:i:s');
		
			$query = "INSERT INTO report(userID,creatorID,reportTypeID,tbl,tableID,createDate) VALUES('$profileID','$userID','$reportTypeID','accounts','$profileID','$createDate')";
			$dbController->runQueryNoReturn($query);
		
			echo "Report Submitted.";
		} else {
			echo "Report Type not set.";
		}
	} else {
		echo "Cannot report yourself.";
	}
?>