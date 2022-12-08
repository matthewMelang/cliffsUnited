<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$to = $_POST['to'];
	$subject = $_POST['subject'];
	
	// check to is a real user
	$query = "SELECT id FROM accounts WHERE username='$to' AND id!=$userID AND acctStatus='Active'";
	$result = $dbController->runQueryBasic($query);
	
	if (isset($result)) {
		$otherID = $result['id'];
		
		// Create Thread
		$createDate = date('Y-m-d H:i:s');
		$query = "INSERT INTO thread(creatorID, receiverID,showCreator,showReciever,subject,createDate) VALUES('$userID','$otherID','1','0','$subject','$createDate')";
		$threadID = $dbController->insertQuery($query);
		
		echo json_encode($threadID);
	} else {
		echo json_encode("");
	}
	
?>