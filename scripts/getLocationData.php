<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	
	// Get Location ID by Name
	if (isset($_GET["locationName"])) {
		$locationName = $_GET["locationName"];
		$query = "SELECT id, latitude, longitude FROM location WHERE description='".$locationName."'";
		$result = $dbController->runQuery($query);

		// Update Last Viewed Location
		$query = "UPDATE accounts SET lastViewLocationID=".$result[0]['id']." WHERE id=".$userID;
		$dbController->runQueryNoReturn($query);
		
		echo json_encode($result);
	}
	
	// Get Location Data BY ID
	if (isset($_GET["locationID"])) {
		$locationID = $_GET["locationID"];
		
		// Update Last Viewed Location
		$query = "UPDATE accounts SET lastViewLocationID=".$locationID." WHERE id=".$userID;
		$dbController->runQueryNoReturn($query);
		
		$query = "SELECT * FROM location WHERE id=".$locationID;
		$result = $dbController->runQuery($query);
		
		// Get Location Type Color
		//$locTypeID = $result[0]['locationTypeID'];
		//$query = "SELECT color FROM locationtype WHERE id=$locTypeID";
		//$locColor = $dbController->runQuery($query);
		//if (isset($locColor)) {
		//	$locColor = $locColor[0]['color'];
		//} else {
		//	$locColor = "red";
		//}
		//$result[0]['locationColor'] = $locColor;
		
		
		if (isset($_GET["updateViews"])) {
			$views = $result[0]["locationViews"];
			$views = $views + 1;
			$query = "UPDATE location SET locationViews=".$views." WHERE id=".$locationID;
			$dbController->runQueryNoReturn($query);
		}
		
		// Get Last Updated Username
		$query = "SELECT username FROM accounts WHERE id=".$result[0]['updatedBy'];
		$username = $dbController->runQueryBasic($query);
		if (isset($username)) {
			$username = $username['username'];
		} else {
			$username = "system";
		}
		$result[0]["username"] = $username;
		
		// Get Location Image
		$query = "SELECT url FROM media WHERE forLocationID=".$locationID." AND mediaType='Main' AND isActive='1'";
		$locationMainImg = $dbController->runQueryBasic($query);
		if (!isset($locationMainImg)) {
			$locationMainImg = "images/media/defaultLocation.png";
		} else {
			$locationMainImg = $locationMainImg['url'];
		}
		array_push($result, $locationMainImg);
		
		// Get Spots
		$query = "SELECT * FROM takeoff WHERE locationID=".$locationID." AND isActive='1'";
		$locationSpotList = $dbController->runQuery($query);
		
		
		// Get Location Spots
		if (isset($locationSpotList)) {
			foreach($locationSpotList as $key => $val) {
				$spotID = $val[0];
				$spotTypeID = $val['spotTypeID'];
				
				// Get Spot Type Description
				$query = "SELECT description FROM spottype WHERE id=".$spotTypeID;
				$spotType = $dbController->runQueryBasic($query);
				if (isset($spotType)) {
					$spotType = $spotType['description'];
				} else {
					$spotType = 'Unknown';
				}
				$locationSpotList[$key]["spotType"] = $spotType;
				
				$description = $val['description'];
				$query = "SELECT url FROM media WHERE forSpotID=".$spotID." AND mediaType='Main' AND isActive='1'";
				$spotMainImg = $dbController->runQueryBasic($query);
				if (!isset($spotMainImg)) {
					$spotMainImg = "images/media/defaultLocation.png";
				} else {
					$spotMainImg = $spotMainImg['url'];
				}
				$locationSpotList[$key]['image'] =  $spotMainImg;
			}
			$result["spotList"] = $locationSpotList;
		}
		
		
		// Get Location Comments
		$query = "SELECT * FROM comments WHERE locationID=".$locationID." AND isActive='1' ORDER BY createDate DESC";
		$commentList = $dbController->runQuery($query);
		if (isset($commentList)) {
			foreach($commentList as $key => $val) {
				$uID = $val['userID'];
				$defaultFile = "images/Media/defaultProfile.jpg";
				
				// Get User Profile Picture
				$query = "SELECT url FROM media WHERE forAccountID=".$uID." && isActive='1'";
				$imgFile = $dbController->runQueryBasic($query);
				if (!isset($imgFile)) {
					$imgFile = "images/media/defaultLocation.png";
				} else {
					$imgFile = $imgFile['url'];
				}
				$commentList[$key]["image"] = $imgFile;
				
				// Get Username
				$query = "SELECT username FROM accounts WHERE id=".$uID;
				$username = $dbController->runQueryBasic($query);
				$commentList[$key]["username"] = $username[0];
			}
			$result["commentList"] = $commentList;
		}
		
		// Get Location Changelog
		$query = "SELECT * FROM changelog WHERE locationID=".$locationID." ORDER BY dateChanged DESC";
		$changelogList = $dbController->runQuery($query);
		if (isset($changelogList)) {
			$dict_clPair = array();
			
			// Convert Changelog to Changelog-groups by User & date Added
			foreach($changelogList as $key => $value) {
				$uID = $value['userID'];
				$query = "SELECT username FROM accounts WHERE id=".$value['userID'];
				$username = $dbController->runQueryBasic($query)['username'];
				$clID = $value['id'];
				$dateChanged = $value['dateChanged'];
				$changedTo = $value['changedTo'];
				$field = $value['tableField'];
				$spotID = $value['takeoffID'];
				if ($spotID != 0) {
					// Get Spot Name
					$query = "SELECT description FROM takeoff WHERE id=".$spotID;
					$spotName = $dbController->runQueryBasic($query)['description'];
				} else {
					$spotName = "";
				}
				
				$arrKey = $username . "_" . $dateChanged;
				$pair = array($username, $dateChanged, $changedTo, $field, $spotID, $spotName, $uID, $clID);
				
				// Add pair to dictonary
				if (array_key_exists($arrKey, $dict_clPair)) {
					array_push($dict_clPair[$arrKey], $pair);
				} else {
					$dict_clPair[$arrKey] = array($pair);
				}
			}
			$result["changelog"] = $dict_clPair;
		}
		echo json_encode($result);
	}
?>