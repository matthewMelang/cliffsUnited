<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT * FROM thread WHERE (receiverID=".$userID." OR creatorID=".$userID.")";
	$result = $dbController->runQuery($query);
	
	// Get Creator Name
	foreach($result as $key => $val) {
		
		// Count Messages in thread
		$query = "SELECT * FROM message WHERE threadID=".$val['id'];
		$messageList = $dbController->contQuery($query);
		$delFlag = $val['deleteFlag'];
		if ($messageList > 0) {
		
			$creatorID = $val['creatorID'];
			$recID = $val['receiverID'];
			$showCreator = $val['showCreator'];
			$showRec = $val['showReciever'];
			$otherID = ($creatorID == $userID ? $recID : $creatorID);
			
			if (($userID == $creatorID && $showCreator == 1) || ($userID == $recID && $showRec == 1)) {
				// Get Username
				$query = "SELECT username FROM accounts WHERE id=".$otherID;
				$username = $dbController->runQuery($query);
				if (isset($username)) {
					$username = $username[0]['username'];
				} else {
					$username = "";
				}
				
				// Get User Profile Picture
				$query = "SELECT url FROM media WHERE forAccountID=".$otherID." && isActive='1'";
				$mainImg = $dbController->runQueryBasic($query);
				if (!isset($mainImg)) {
					$mainImg = "images/Media/defaultProfile.jpg";
				} else {
					$mainImg = $mainImg['url'];
				}
				
				// Get Last Message Date
				$query = "SELECT createDate FROM message WHERE threadID=".$val['id'];
				$msgList = $dbController->runQuery($query);
				$lastMsg = null;
				if (isset($msgList)) {
					foreach ($msgList as $k => $v) {
						$cDate = strtotime($v['createDate']);
						if ($lastMsg == null) {
							$lastMsg = $cDate;
						} else {
							if ($lastMsg < $cDate) {
								$lastMsg = $cDate;
							}
						}
					}
					$dt = new DateTime("@$lastMsg");
					$lastMsg = $dt->format('Y-m-d H:i:s');
				} else {
					$lastMsg = "";
				}
				
				$result[$key]['lastMessage'] = $lastMsg;
				$result[$key]['messageCount'] = $messageList;
				$result[$key]['creatorUsername'] = $username;
				$result[$key]['creatorImg'] = $mainImg;
			} else {
				$result[$key] = null;
			}
		} else {
			if ($delFlag == 1) {
				$query = "DELETE FROM thread WHERE id=".$val['id'];
				$dbController->runQueryNoReturn($query);
			} else {
				$query = "UPDATE thread set deleteFlag=1 WHERE id=".$val['id'];
				$dbController->runQueryNoReturn($query);
			}
			
			// Remove Key from Dict
			$result[$key] = "";
		}
	}
	echo json_encode($result);
?>