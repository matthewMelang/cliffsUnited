<?php
	date_default_timezone_set('America/New_York');
	$urlInviteCode = -1;
	if (isset($_GET["inviteCode"])) {
		$urlInviteCode = $_GET["inviteCode"];
		
		// Verify valid Invite Code
		require("scripts/DBController.php");
		$dbController = new DBController();
		
		$query = "SELECT * FROM accounts WHERE inviteCode='$urlInviteCode'";
		$result = $dbController->runQuery($query);
		
		if ($result <= 0) {
			header("Location: login.php");
		}
	} else {
		header("Location: login.php");
	}
?>


<html>
	<head>
		<title>Cliffs United</title>
		<link rel="stylesheet" type="text/css" href="styles/styleBackgroundImg.css">
		<link rel="stylesheet" type="text/css" href="styles/styleRegister.css">
		<body>
			<div class="loginbox">
				<img src="images/avatar.png" class="avatar">
				<h1>Create an Account</h1>
				<?php
				echo "<form id=\"form_id\" method=\"post\" name=\"myform\" action=\"scripts/registration.php?inviteCode=".$urlInviteCode."\">";
				?>
					<p>Username</p>
					<input type="text" name="username" id="username" onchange="validateElement('Username',this)" placeholder="Enter Username" required>
					
					<p>Email</p>
					<input type="text" name="email" id="email" onchange="validateElement('Email',this)" placeholder="Email Address" required>
					
					<div class="form_row">
						<div class="password_info left">
							<p>Password</p>
							<input type="password" name="password" id="password" onchange="validateElement('Password',this)" minlength="8" placeholder="Enter Password" required>
						</div>
						
						<div class="password_info right">
							<p>Repeat Password</p>
							<input type="password" name="passwordRepeat" id="passwordRepeat" minlength="8" placeholder="Enter Password" required>
						</div>
					</div>
					
					<script type="text/javascript">
						function validateElement(validateType, element){
							var Regex;
							var testString = "";
							var validList = "";
							if (validateType == "Username") {
								Regex = /^[a-zA-Z0-9_-]{3,20}$/;
								testString = element.value;
								validList = "Must be between 3 and 20 characters. Can only contain Alphanumeric, dashes, and underscores.";
							} else if (validateType == "Password") {
								Regex = /^\S*$/;
								testString = element.value;
								validList = "Cannot contain whitespace.";
							} else if (validateType == "Email") {
								Regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
								testString = element.value;
							}
							
							if (testString != "") {
								var validate = testString.match(Regex);

								if(validate == null){
									alert("Your " + validateType + " is not valid." + validList);
									element.focus();
									return false;
								}
							}
						}
					</script>

					<input type="submit" value="Register Account">
					<a href="login.php">Already have an account? Login!</a>
				</form>
			</div>
		</body>
	</head>
</html>
