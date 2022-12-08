<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	$p1 = $_POST['password'];
	$p2 = $_POST['passwordRepeat'];
	
	if ($p1 == $p2) {
		$conn = mysqli_connect("localhost", "root", "");
		mysqli_select_db($conn, "cliffs");
		
		$query = "UPDATE accounts SET pass='$p1' WHERE id='$userID'";
		mysqli_query($conn, $query);
		
		// Send Email & notify of a password change
		
		header("location:../profile.php");
	} else {
		echo "Passwords did not match.";
	}
?>