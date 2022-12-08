<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Check a location was specified
	$commentID = "";
	if (isset($_GET["commentID"])) {
		$commentID = $_GET["commentID"];
		
		$query = "SELECT * FROM comments WHERE id=$commentID AND userID=$userID";
		$result = $dbController->runQuery($query);
		if (isset($result)) {
			
			$createDate = date('Y-m-d H:i:s');
			$query = "UPDATE comments SET isActive=0, removeDate='$createDate' WHERE id=$commentID";
			$dbController->runQueryNoReturn($query);
			
			echo json_encode("");
		} else {
			echo json_encode("Cannot find comment");
		}
	} else {
		echo json_encode("Comment ID not defined");
	}
?>