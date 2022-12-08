<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$threadID = $_POST['threadID'];
	$message = $_POST['message'];
	$createDate = date('Y-m-d H:i:s');
	
	// Insert Message
	$query = "INSERT INTO message(threadID,senderID,description,createDate,seen) VALUES('$threadID','$userID','$message','$createDate','0')";
	$dbController->insertQuery($query);
	
	// Update Thread to Show
	$query = "UPDATE thread SET showCreator=1, showReciever=1 WHERE id=".$threadID;
	$dbController->runQueryNoReturn($query);
?>