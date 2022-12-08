<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$threadID = $_POST['threadID'];
	
	$query = "SELECT creatorID,receiverID FROM thread WHERE id=".$threadID;
	$result = $dbController->runQuery($query);
	
	$creatorID = $result[0]['creatorID'];
	$recID = $result[0]['receiverID'];
	
	if ($userID == $creatorID) {
		$query = "UPDATE thread SET showCreator=0 WHERE id=".$threadID;
		$dbController->runQueryNoReturn($query);
	} else if ($userID == $recID) {
		$query = "UPDATE thread SET showReciever=0 WHERE id=".$threadID;
		$dbController->runQueryNoReturn($query);
	}
?>