<?php
	date_default_timezone_set('America/New_York');
	session_destroy();
	header("location:../login.php");
?>