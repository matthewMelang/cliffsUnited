<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	$username = $_GET['username'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Make sure username isnt take
	$query = "SELECT * FROM accounts WHERE username='$username' AND id !='$userID'";
	$rows = $dbController->contQuery($query);
	
	echo $rows;
?>