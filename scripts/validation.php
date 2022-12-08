<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$loginEnabled = 1;
	
	require("DBController.php");
	$dbController = new DBController();
	$loginDate = date('Y-m-d H:i:s');
	
	$user = $_POST['username'];
	$_SESSION['lastUsername'] = $user;
	$pass = $_POST['password'];
	
	// Get Visitor IP Address
	if (!empty($_SERVER["HTTP_CLIENT_IP"])) { // Shared internet IP
		$ip = $_SERVER["HTTP_CLIENT_IP"];		
	} else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) { // Proxy
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	
	
	$query = "SELECT * FROM policy WHERE createDate <= (now() + INTERVAL 1 DAY) AND ((expiryDate IS NULL || expiryDate = '') || (expiryDate IS NOT NULL AND STR_TO_DATE(expiryDate, '%Y-%m-%d %h:%i:%s') >= now())) ORDER BY createDate DESC LIMIT 1";
	$policyResult = $dbController->runQuery($query);
	$maxAttemtps = 5;
	$timeout = 120;
	if (isset($policyResult)) {
		if ($loginEnabled == 1) {
			$loginEnabled = $policyResult[0]['loginEnabled'];
		}
		$maxAttemtps = $policyResult[0]['maxFailedLogins'];
		$timeout = $policyResult[0]['failedLoginTimeout'];
	}
	
	
	// Check if the Username Exists in the Database
	$query = "SELECT * FROM accounts WHERE binary username='$user' && acctStatus='Active'";
	$result = $dbController->runQueryBasic($query);
	$foundUserID = -1;
	if ($result > 0) {
		$foundUserID = $result["id"];
		
		$query = "SELECT * FROM login WHERE userID=$foundUserID AND failed=1 AND (loginDate > now() - INTERVAL ".$timeout." MINUTE)";
		$failedLogins = $dbController->contQuery($query);
		$_SESSION['failedLoginCount'] = $failedLogins;
		
		if ($failedLogins >= $maxAttemtps) {
			$loginEnabled = 0;
		}
	}
	
	$query = "SELECT * FROM accounts WHERE binary username='$user' && binary pass='$pass' && acctStatus ='Active'";
	$result = $dbController->runQueryBasic($query);
	
	// Disable Login
	if ($loginEnabled == 0) {
		$result = 0;
	}
	
	if ($result > 0) {
		// Set Session Data
		$userID = $result["id"];
		$_SESSION['nID'] = $userID;
		
		// Get Last Viewed Location
		$lastLocationID = $result["lastViewLocationID"];
		$addHeader = "";
		if ($lastLocationID != 0) {
			$addHeader = "?locationID=".$lastLocationID;
		}
		
		// Set Last Login
		$_SESSION['failedLoginCount'] = 0;
		$query = "UPDATE accounts SET lastLogin='$loginDate' WHERE id='$userID'";
		$dbController->runQueryNoReturn($query);
		
		$query = "INSERT INTO login(userID, ipAddr, failed, loginDate) VALUES('$userID','$ip', '0', '$loginDate')";
		$dbController->runQueryNoReturn($query);
		
		// Goto Home Page
		header("location:../home.php".$addHeader);
	} else {
		if ($foundUserID > -1) {
			// Set Failed Login Attempts
			if ($failedLogins < $maxAttemtps) {
				$_SESSION['failedLoginCount'] = $_SESSION['failedLoginCount'] + 1;
				$query = "INSERT INTO login(userID, ipAddr, failed, loginDate) VALUES('$foundUserID', '$ip', '1', '$loginDate')";
				$dbController->runQueryNoReturn($query);
			}
			$_SESSION['userFound'] = 1;
			
			
		} else {
			// Display Username not found
			$_SESSION['userFound'] = 0;
		}
		
		header("location:../login.php");
	}
?>