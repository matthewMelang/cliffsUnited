<?php
	date_default_timezone_set('America/New_York');
	session_start();
	if (!isset($_SESSION['nID'])) {
		header("Location: login.php");
	}
?>

<html>
	<head>
		<title>Cliffs United</title>
		<link rel="stylesheet" type="text/css" href="styles/styleBackgroundImg.css">
		<link rel="stylesheet" type="text/css" href="styles/styleLogin.css">
	</head>
	<body>
		<div class="loginbox">
			<img src="images/security.png" class="avatar">
			<h1>Change Password</h1>
			<form id="form_id" method="post" name="myform" action="scripts/validateNewPassword.php">
				<p>New Password</p>
				<input type="password" name="password" id="password" minlength="8" placeholder="Password..." required>
				<p>Retype Password</p>
				<input type="password" name="repeat" id="repeat" minlength="8" placeholder="Password..." required>
				<input type="submit" value="Login">
			</form>
		</div>
	</body>
	
</html>