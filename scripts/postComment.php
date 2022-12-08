<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Check a location was specified
	$locationID = $_GET["locationID"];
	
	$comment = $_GET['comment'];
	$createDate = date('Y-m-d H:i:s');

	$query = "INSERT INTO comments(locationID, userID, description, isActive, createDate) VALUES('$locationID','$userID','$comment','1','$createDate')";
	$dbController->runQueryNoReturn($query);
	
	// Return all comments for the location
	// Get Location Comments
	$query = "SELECT * FROM comments WHERE locationID=".$locationID." AND isActive='1' ORDER BY createDate DESC";
	$commentList = $dbController->runQuery($query);
	if (isset($commentList)) {
		foreach($commentList as $key => $val) {
			$userID = $val['userID'];
			$defaultFile = "images/Media/defaultProfile.jpg";
			
			// Get User Profile Picture
			$query = "SELECT url FROM media WHERE forAccountID=".$userID." && isActive='1'";
			$imgFile = $dbController->runQueryBasic($query);
			if (!isset($imgFile)) {
				$imgFile = "images/media/defaultLocation.png";
			} else {
				$imgFile = $imgFile['url'];
			}
			$commentList[$key]["image"] = $imgFile;
			
			// Get Username
			$query = "SELECT username FROM accounts WHERE id=".$userID;
			$username = $dbController->runQueryBasic($query);
			$commentList[$key]["username"] = $username[0];
		}
	}
	
	echo json_encode($commentList);
	
?>