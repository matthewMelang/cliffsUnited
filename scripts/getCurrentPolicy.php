<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT * FROM policy WHERE createDate <= (now() + INTERVAL 1 DAY) AND ((expiryDate IS NULL || expiryDate = '') || (expiryDate IS NOT NULL AND STR_TO_DATE(expiryDate, '%Y-%m-%d %h:%i:%s') >= now())) ORDER BY createDate DESC LIMIT 1";
	$result = $dbController->runQuery($query);
	
	
	if (isset($result)) {
		$policyID = $result[0]['id'];
		// Get Policy Image
		$query = "SELECT url FROM media WHERE forPolicyID=".$policyID." AND mediaType='Background' AND isActive='1'";
		$policyImg = $dbController->runQueryBasic($query);
		if (!isset($policyImg)) {
			$policyImg = "images/media/defaultLocation.png";
		} else {
			$policyImg = $policyImg['url'];
		}
		$result[0]['backgroundImg'] =  $policyImg;
		
		echo json_encode($result);
	} else {
		echo json_encode("");
	}
?>