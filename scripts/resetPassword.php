<?php
	date_default_timezone_set('America/New_York');
	session_start();

	$userID = $_POST['userID'];
	$pin = $_POST['pin'];
	$today = strtotime(date("Y-m-d"));
	
	$conn = mysqli_connect("localhost", "root", "");
	mysqli_select_db($conn, "cliffs");
	$query = "SELECT * FROM accounts WHERE id='$userID'";
	$accountResult = mysqli_query($conn, $query);
	$accountRows = mysqli_fetch_array($accountResult);
	
	$actualPin = $accountRows[12];
	$pinExpiry = strtotime($accountRows[13]);
	
	if ($pin == $actualPin) {
		if ($today > $pinExpiry) {
			echo "Pin is Expired";
		} else {
			// Goto Change Password Screen & Login
			$_SESSION['nID'] = $userID;
			header("location:../changePassword.php");
		}
	} else {
		echo "Incorrect Pin";
	}
?>