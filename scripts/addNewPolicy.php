<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	require("uploadController.php");
	$upController = new uploadController($dbController);
	
	$infoArray = array (
		array('minUsername',$_POST['policy-minUser']),
		array('maxUsername',$_POST['policy-maxUser']),
		array('minPassword',$_POST['policy-minPass']),
		array('maxPassword',$_POST['policy-maxPass']),
		array('usernameAllowed',$_POST['policy-userRegex']),
		array('passwordAllowed',$_POST['policy-passRegex']),
		array('forcePasswordReset',$_POST['policy-forcePassReset']),
		array('loginEnabled',$_POST['policy-canLogin']),
		array('searchableEnabled',$_POST['policy-canSearch']),
		array('commentEnabled',$_POST['policy-canComment']),
		array('editContentEnabled',$_POST['policy-canEditContent']),
		array('editProfileEnabled',$_POST['policy-canEditProfile']),
		array('inviteEnabled',$_POST['policy-canInvite']),
		array('sendMessagesEnabled',$_POST['policy-canMessage']),
		array('reportingEnabled',$_POST['policy-canReport']),
		array('maxUserCount',$_POST['policy-maxUserCount']),
		array('maxFailedLogins',$_POST['policy-maxLoginAttepts']),
		array('failedLoginTimeout',$_POST['policy-loginTimeout']),
		array('expiryDate',$_POST['policy-expiry']),
	);
	
	$createDate = date('Y-m-d H:i:s');
	
	// Add the New Policy
	$query = "INSERT INTO policy(createDate) VALUES('$createDate')";
	$policyID = $dbController->insertQuery($query);
	foreach($infoArray as $row) {
		$query = "UPDATE policy SET ".$row[0]."='".$row[1]."' WHERE id=".$policyID;
		$dbController->runQueryNoReturn($query);
	}
	
	// Update the Background Photo
	$imageSrc = $_POST['policy-imageFile'];
	if ($imageSrc != "") {
		$newName = $upController->uploadURL($imageSrc, $userID);
	}
	
	$query = "INSERT INTO media(mediaType, forPolicyID, addedByUserID, url, isActive, createDate) VALUES ('Background', '$policyID', '$userID', '$newName', '1', '$createDate')";
	$dbController->runQueryNoReturn($query);
	
	echo "Policy Updated Sucessfully.";
?>