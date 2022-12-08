<?php
	date_default_timezone_set('America/New_York');
	session_start();
	
	require("DBController.php");
	$dbController = new DBController();

	$urlInviteCode = $_GET["inviteCode"];
	
	$uname = $_POST['username'];
	$email = $_POST['email'];
	$pass = $_POST['password'];
	$createDate = date('Y-m-d H:i:s');
	$newInviteCode = bin2hex(random_bytes(10));
	$passRepeat = $_POST['passwordRepeat'];
	
	if ($pass == $passRepeat) {
		// Verify this is an active Invite Code
		$query = "SELECT id, inviteCode FROM accounts WHERE inviteCode='".$urlInviteCode."'";
		$inviteRows = $dbController->runQueryBasic($query);
		
		if ($inviteRows > 0) {
			// Verify New User Doesnt Exist
			$query = "SELECT * FROM accounts WHERE username='$uname'";
			$accountRows = $dbController->runQueryBasic($query);
			
			if ($accountRows >= 1) {
				echo "Username already taken.";
			} else {
				
				// Create New User
				$inviteeID = $inviteRows['id'];
				$emailConfirmCode = bin2hex(random_bytes(5));
				$query = "INSERT INTO accounts(username,pass,email,createDate,inviteCode,invitedByID, acctStatus, isSearchable, accountType, canComment, canEditContent, canEditProfile, canInvite, emailConfirmCode) VALUES('$uname','$pass','$email','$createDate','$newInviteCode','$inviteeID','Pending', '1', 'User', '1', '1', '1', '1', '$emailConfirmCode')";
				$dbController->runQueryNoReturn($query);
				
				// Update Invitee Invite Code
				$newInviteCode = bin2hex(random_bytes(10));
				$query = "UPDATE accounts SET inviteCode='$newInviteCode' WHERE id='$inviteeID'";
				$dbController->runQueryNoReturn($query);
				
				// Get new User ID
				$query = "SELECT id FROM accounts WHERE username='$uname'";
				$result = $dbController->runQueryBasic($query);
				$userID = $result[0];
				$_SESSION['nID'] = $userID;
				
				// Goto Thanks for Registering Page
				header("location:../emailconfirmation.php");
			}
		} else {
			echo "Invalid Invite Code: " . $urlInviteCode;
		}
	} else {
		echo "Passwords do not match";
	}
?>