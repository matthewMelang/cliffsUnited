<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$threadID = $_POST['threadID'];
	$reportTypeID = $_POST['reportTypeID'];
	
	// Get Creator & Recieiver from Thread
	$query = "SELECT creatorID, receiverID FROM thread WHERE id=$threadID";
	$result = $dbController->runQuery($query);
	$creatorID = $result[0]['creatorID'];
	$receiverID = $result[0]['receiverID'];
	
	$otherID = ($userID == $creatorID ? $receiverID : $creatorID);
	
	// Get User ID of commentID
	if (isset($reportTypeID)) {
		$createDate = date('Y-m-d H:i:s');
	
		$query = "INSERT INTO report(userID,creatorID,reportTypeID,tbl,tableID,createDate) VALUES('$otherID','$userID','$reportTypeID','thread','$threadID','$createDate')";
		$dbController->runQueryNoReturn($query);
	
		echo "Report Submitted.";
	} else {
		echo "Report Type not set.";
	}
?>