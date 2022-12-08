<?php
	date_default_timezone_set('America/New_York');
	session_start();
	
	$confirmationCode = "";
	$userID = -1;
	
	$conn = mysqli_connect("localhost", "root", "");
	mysqli_select_db($conn, "cliffs");
	
	if (isset($_GET["confirmationCode"])) {
		$confirmationCode = $_GET["confirmationCode"];
		
		// Check if Confirmation Code Exists
		$reg = "SELECT id FROM accounts WHERE emailVerified='FALSE' AND emailConfirmCode='$confirmationCode'";
		$result = mysqli_query($conn, $reg);
		$rows = mysqli_fetch_array($result);
		
		if ($rows <= 0) {
			//header("Location: login.php");
		} else {
			$userID = $rows[0];
		}
	}
	
	if (!isset($_SESSION['nID']) && $confirmationCode == "") {
		//header("Location: login.php");
	}
	
	if ($userID == -1 && isset($_SESSION['nID'])) {
		$userID = $_SESSION['nID'];
	}
	
	$reg = "SELECT username, emailConfirmCode FROM accounts WHERE id='$userID'";
	$result = mysqli_query($conn, $reg);
	$rows = mysqli_fetch_array($result);
	$username = $rows[0];
	$emailCode = $rows[1];
	
	$userMsg = "<p>Hi ".$username."! Welcome to Cliffs United. Before you can start using your account, a confirmation email has been set to the email address provided.</p>";
	
	// Confirm Email Address & goto Login Page
	if ($emailCode != "" && $confirmationCode == $emailCode) {
		$reg = "UPDATE accounts SET emailVerified='1', acctStatus='Active' WHERE id='$userID'";
		mysqli_query($conn, $reg);
		
		$userMsg = "<p>Thanks ".$username."! Your email address has been verified. Please return to the login screen to continue.</p>";
	}
	
?>

<html>
	<head>
		<title>Cliffs United</title>
		<link rel="stylesheet" type="text/css" href="styles/styleBackgroundImg.css">
		<link rel="stylesheet" type="text/css" href="styles/styleEmailConfirm.css">
		<body>
			<div class="loginbox">
				<img src="images/email.png" class="avatar">
				<h1>Email Confirmation</h1>
				<?php echo $userMsg; ?>
				<input type="button" value="Return Home" onClick="document.location.href='login.php'">
			</div>
		</body>
	</head>
</html>