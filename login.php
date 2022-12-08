<?php
	date_default_timezone_set('America/New_York');
	session_start();
	unset($_SESSION['nID']);
	
	$bgImage = "images/Media/defaultLocation.png";
	$loginText = "";
	$loginEnabled = 1;
	
	if (!isset($_SESSION['userFound'])) {
		$_SESSION['userFound'] = -1;
	}
	
	if (!isset($_SESSION['lastUsername'])) {
		$_SESSION['lastUsername'] = "";
	}
	
	if (!isset($_SESSION['failedLoginCount'])) {
		$_SESSION['failedLoginCount'] = 0;
	}
	
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT * FROM policy WHERE createDate <= (now() + INTERVAL 1 DAY) AND ((expiryDate IS NULL || expiryDate = '') || (expiryDate IS NOT NULL AND STR_TO_DATE(expiryDate, '%Y-%m-%d %h:%i:%s') >= now())) ORDER BY createDate DESC LIMIT 1";
	$result = $dbController->runQuery($query);

	if (isset($result)) {
		$policyID = $result[0]['id'];
		
		$loginText = $result[0]['loginMessage'];
		if ($loginEnabled == 1) {
			$loginEnabled = $result[0]['loginEnabled'];
		}
		
		$forceReset = $result[0]['forcePasswordReset'];
		$createDate = $result[0]['createDate'];
		
		$maxAttemtps = $result[0]['maxFailedLogins'];
		
		// Attempts Left Message
		if ($_SESSION['failedLoginCount'] > 0) {
			$attemptsLeft = $maxAttemtps - $_SESSION['failedLoginCount'];
			$loginText = "Attempts Left: ".$attemptsLeft;
			
			if ($attemptsLeft <= 0) {
				$loginText = "Login Disabled. Please try again later.";
			}
		}
		
		// Username does not exist
		if ($_SESSION['userFound'] == 0) {
			$loginText = "Account does not exist";
		}
		
		// Get Background Image
		$query = "SELECT url FROM media WHERE forPolicyID=$policyID";
		$result = $dbController->runQuery($query);
		if (isset($result)) {
			$bgImage = $result[0]['url'];
		}
	}
?>

<html>
	<head>
		<title>Cliffs United</title>
		<link rel="stylesheet" type="text/css" href="styles/styleBackgroundImg.css">
		<link rel="stylesheet" type="text/css" href="styles/styleLogin.css">
	</head>
	<body>
		<script>
			document.body.style.backgroundImage = "url(" + <?php echo '"'.$bgImage.'"'; ?> + ")";
		</script>
		<div class="loginbox">
			<img src="images/avatar.png" class="avatar">
			<h1>Login</h1>
			<form id="form_id" method="post" name="myform" action="scripts/validation.php">
				<p>Username</p>
				<input type="text" name="username" id="username" placeholder="Enter Username" value="<?php echo $_SESSION['lastUsername']; ?>" required>
				<p>Password</p>
				<input type="password" name="password" id="password" minlength="8" placeholder="Enter Password" required>
				<input type="submit" value="Login">
				<a href="#" onclick="getInfo()">Lost your Password?</a>
				<script>
					function getInfo() {
						var user = document.getElementById("username").value;
						if (user != "") {
							location.replace("lostPassword.php?user=" + user);
						} else {
							alert("Please input username");
						}
					}
				</script>
			</form>
		</div>
		<?php
			if ($loginText != "") {
				echo "<div class='loginMessage'>";
				echo" <p>$loginText</p>";
				echo "</div>";
			}
		?>
	</body>
</html>