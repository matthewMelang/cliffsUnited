<?php
	date_default_timezone_set('America/New_York');
	require("DBController.php");
	$dbController = new DBController();
	
	$spotID = $_GET["spotID"];
	
	$query = "SELECT * FROM takeoff WHERE id=".$spotID;
	$result = $dbController->runQuery($query);
	
	// Get Image
	$query = "SELECT url FROM media WHERE forSpotID=".$spotID." AND mediaType='Main' AND isActive='1'";
	$spotMainImg = $dbController->runQueryBasic($query);
	if (!isset($spotMainImg)) {
		$spotMainImg = "images/media/defaultLocation.png";
	} else {
		$spotMainImg = $spotMainImg['url'];
	}
	array_push($result, $spotMainImg);
	
	echo json_encode($result);
?>