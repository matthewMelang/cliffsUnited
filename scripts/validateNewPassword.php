<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	$pass = $_POST['password'];
	$repeat = $_POST['repeat'];
	$lastChange = date("Y-m-d");
	
	if ($pass == $repeat) {
		
		$conn = mysqli_connect("localhost", "root", "");
		mysqli_select_db($conn, "cliffs");
		$query = "UPDATE accounts SET pass='$pass', lastPassChange='$lastChange' WHERE id='$userID'";
		$accountResult = mysqli_query($conn, $query);
		header("location:../home.php");
		
	} else {
		header("location:../changePassword.php");
	}


?>