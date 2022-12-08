<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$user = "";
	$userID = -1;
	
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	if (isset($_GET["user"])) {
		$user = $_GET["user"];
		
		// Get User Email Address
		$query = "SELECT * FROM accounts WHERE username='$user'";
		$accountRows = $dbController->runQueryBasic($query);
		
		$userID = $accountRows[0];
		$email = $accountRows[3];
		$emailParts = explode("@", $email);
		$firstLetter = $emailParts[0][0];
		$lastLetter = substr($emailParts[0],-1);
		$finalEmail = $firstLetter."********".$lastLetter."@".$emailParts[1];
		
		// Set Pin Details
		$digits = 5;
		$expireDays = 3;
		$pin = rand(pow(10, $digits-1), pow(10, $digits)-1);
		$expiry = date("Y-m-d", time() + (86400 * $expireDays));
		
		$query = "UPDATE accounts SET resetPin='$pin', pinActiveUntil='$expiry' WHERE id='$userID'";
		$dbController->runQueryNoReturn($query);
		
	} else {
		header("Location: login.php");
	}
?>

<html>
	<head>
		<title>Cliffs United</title>
		<link rel="stylesheet" type="text/css" href="styles/styleBackgroundImg.css">
		<link rel="stylesheet" type="text/css" href="styles/styleLostPassword.css">
	</head>
	<body>
		<div class="loginbox">
			<img src="images/email.png" class="avatar">
			<h1>Verification Code Sent</h1>
			<?php echo "<p>A 5-digit verification code has been sent the email address $finalEmail</p>"; ?>
			
			<form id="form_id" method="post" name="myform" action="scripts/resetPassword.php">
				<input type="hidden" name="userID" value="<?=$userID;?>">
				<p>Enter the one-time PIN received.</p>
				<input type="text" name="pin" maxlength=5>
				<a href="#" class="floatLeft">Resend Pin</a>
				<a href="login.php" class="floatRight">Cancel</a>
			</form>
			
		</div>
	</body>
	
</html>