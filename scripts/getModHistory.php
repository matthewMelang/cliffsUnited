<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT * FROM changelog WHERE moderatorID != 0";
	$result = $dbController->runQuery($query);
	
	$modList = [];
	if (isset($result)) {
		foreach($result as $key => $val) {
			$acctID = $val['userID'];
			$modID = $val['moderatorID'];
			$dateChanged = $val['dateChanged'];
			
			// Get Moderator Username
			$query = "SELECT username FROM accounts WHERE id=$modID";
			$username = $dbController->runQuery($query);
			if (isset($username)) {
				$username = $username[0]['username'];
			} else {
				$username = "";
			}
			
			// Get Account Username
			$query = "SELECT username FROM accounts WHERE id=$acctID";
			$acctUsername = $dbController->runQuery($query);
			if (isset($acctUsername)) {
				$acctUsername = $acctUsername[0]['username'];
			} else {
				$acctUsername = "";
			}
			
			$val['acctUsername'] = $acctUsername;
			$val['modUsername'] = $username;
			
			
			$key = $modID . $dateChanged;
			
			//array_push($modList[$key], $val);
			
			if (array_key_exists($key, $modList)) {
				array_push($modList[$key], $val);
			} else {
				$modList[$key] = [];
				array_push($modList[$key], $val);
			}
		}
		echo json_encode($modList);
		
	} else {
		echo json_encode("");
	}
?>