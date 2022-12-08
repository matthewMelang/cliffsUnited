<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	require("uploadController.php");
	$upController = new uploadController($dbController);
	
	$updateType = $_POST['updateType'];
	$changeDate = date('Y-m-d H:i:s');
	if ($updateType == "general") {
		$username = $_POST['formTB-username'];
		$email = $_POST['formTB-email'];
		$bio = $_POST['formTB-bio'];
		$name = $_POST['formTB-fullName'];
		$website = $_POST['formTB-website'];
		
		$imageChanged = 0;
		if (is_numeric($_POST['formTB-imageFileChanged'])) {
			$imageChanged = 1;
		} else {
			$imageChanged = 0;
		}
		

		// Get Previous VALUES
		$query = "SELECT * FROM accounts WHERE id='$userID'";
		$result = $dbController->runQueryBasic($query);
		$changelog = array
		(
			array("username", $result['username'], $username),
			array("email", $result['email'], $email),
			array("bio", $result['bio'], $bio),
			array("name", $result['name'], $name),
			array("website", $result['website'], $website)
		);
		
		
		// Upload Image
		$isChanged = false;
		if ($imageChanged == 1) {
			$isChanged = true;
			$imageSrc = $_POST['formTB-imageFile'];
			$newName = $upController->uploadURL($imageSrc, $userID);
			
			// Update Database
			$createDate = date('Y-m-d H:i:s');
			$query = "SELECT url FROM media WHERE mediaType='Profile Picture' && forAccountID='$userID' && isActive='1'";
			$oldName = $dbController->runQuery($query);
			if (isset($oldName)) {
				$oldName = $oldName[0]['url'];
			} else {
				$oldName = "";
			}
			
			$query = "UPDATE media SET isActive='0' WHERE mediaType='Profile Picture' && forAccountID='$userID' && isActive='1'";
			$dbController->runQueryNoReturn($query);
			
			$query = "INSERT INTO media(mediaType, forAccountID, addedByUserID, url, isActive, createDate) VALUES ('Profile Picture', '$userID', '$userID', '$newName', '1', '$createDate')";
			$dbController->runQueryNoReturn($query);
			
			$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged) VALUES('$userID','accounts','profilePicture','$oldName','$newName','$createDate')";
			$dbController->runQueryNoReturn($query);
		}
		
		
		// Update Accounts
		$query = "UPDATE accounts SET username='$username', email='$email', bio='$bio', name='$name', website='$website' WHERE id='$userID'";
		$dbController->runQueryNoReturn($query);
		
		// Update Changelog
		
		for ($row = 0; $row < count($changelog); $row++) {
			$tableField = $changelog[$row][0];
			$from = $changelog[$row][1];
			$to = $changelog[$row][2];
			
			if ($from != $to) {
				$isChanged = true;
				$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged) VALUES('$userID', 'accounts', '$tableField', '$from', '$to', '$changeDate')";
				$dbController->runQueryNoReturn($query);
			}
		}
		
		if ($isChanged) {
			echo "Account updated.";
		} else {
			echo "No changes made.";
		}
	} else if ($updateType == "password") {
		$password = $_POST['formTB-pass'];
		$query = "UPDATE accounts SET pass='$password', lastPassChange='$changeDate' WHERE id='$userID'";
		$dbController->runQueryNoReturn($query);
		echo "Password Updated.";
	}
?>