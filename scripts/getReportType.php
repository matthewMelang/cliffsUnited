<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Check a location was specified
	$reportType = "";
	if (isset($_GET["rType"])) {
		$reportType = $_GET["rType"];
		
		$query = "SELECT * FROM reporttype WHERE rType='$reportType'";
		$result = $dbController->runQuery($query);
		
		echo json_encode($result);
	} else {
		echo json_encode("rType Not Set.");
	}
?>