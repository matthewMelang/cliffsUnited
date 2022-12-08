<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	$createDate = date('Y-m-d H:i:s');
	if (isset($_GET["threadID"])) {
		$threadID = $_GET["threadID"];
		$query = "SELECT * FROM message WHERE threadID=".$threadID." ORDER BY STR_TO_DATE(createDate, '%m/%d/%Y') DESC";
		$result = $dbController->runQuery($query);
		if (isset($result)) {
			foreach($result as $key => $val) {
				// Set Message to SEEN
				$query = "UPDATE message SET seen=1, seenOnDate='$createDate' WHERE id=".$val['id']." AND senderID !=".$userID." AND seen=0";
				$dbController->runQueryNoReturn($query);
				
				// Get Username
				$query = "SELECT username FROM accounts WHERE id=".$val['senderID'];
				$username = $dbController->runQuery($query);
				if (isset($username)) {
					$username = $username[0]['username'];
				} else {
					$username = "";
				}
				
				// Get Other Thread User
				$query = "SELECT creatorID,receiverID,subject FROM thread WHERE id=".$threadID;
				$creatorID = $dbController->runQuery($query);
				if (isset($creatorID)) {
					$subject = $creatorID[0]["subject"];
					$recID = $creatorID[0]['receiverID'];
					$creatorID = $creatorID[0]['creatorID'];
					$otherID = ($creatorID == $userID ? $recID : $creatorID);
					
					$query = "SELECT username FROM accounts WHERE id=".$otherID;
					$creatorUsername = $dbController->runQuery($query);
					if (isset($creatorUsername)) {
						$creatorUsername = $creatorUsername[0]['username'];
					} else {
						$creatorUsername = "";
					}
				}
				
				// Get User Profile Picture
				$query = "SELECT url FROM media WHERE forAccountID=".$val['senderID']." && isActive='1'";
				$mainImg = $dbController->runQueryBasic($query);
				if (!isset($mainImg)) {
					$mainImg = "images/Media/defaultProfile.jpg";
				} else {
					$mainImg = $mainImg['url'];
				}
				
				$result[$key]['subject'] = $subject;
				$result[$key]['creatorUsername'] = $creatorUsername;
				$result[$key]['senderUsername'] = $username;
				$result[$key]['senderImg'] = $mainImg;
			}
			echo json_encode($result);
		}
	}
?>