<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	require("uploadController.php");
	$upController = new uploadController($dbController);
	
	$spotID = $_POST['formTB-spotID'];
	$locID = $_POST['formTB-spotLocID'];
	$spotName = $_POST['formTB-spotName'];
	$spotTypeID = $_POST['formTB-spotTypeID'];
	$createDate = date('Y-m-d H:i:s');
	$btnStatus = $_POST['formTB-btnStatus']; // 1 = Delete, 2 = Update, 3 = Add
	
	$imageChanged = 0;
	if (is_numeric($_POST['formTB-spotFileChanged'])) {
		$imageChanged = 1;
	} else {
		$imageChanged = 0;
	}
	
	// Delete Spot
	if ($btnStatus == 1) {
			
		// Update Spot
		$query = "UPDATE takeoff SET isActive='0', lastUpdate='$createDate', updatedBy=".$userID." WHERE id=".$spotID;
		$dbController->runQueryNoReturn($query);
		
		$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID, takeoffID) VALUES('$userID','takeoff','isActive','1','0','$createDate','$locID','$spotID')";
		$dbController->runQueryNoReturn($query);
		
		echo "Spot Sucessfully Deleted.";
	} else {
		$minHeight = $maxHeight = $minDepth = $maxDepth = $minGap = $maxGap = 0;
		if (strpos($_POST['formTB-spotHeight'], "-") !== false) {
			$heights = explode("-",str_replace(" ", "",$_POST['formTB-spotHeight']));
			$minHeight = $heights[0];
			$maxHeight = $heights[1];
		} else {
			$minHeight = $_POST['formTB-spotHeight'];
		}
		
		if (strpos($_POST['formTB-spotDepth'], "-") !== false) {
			$depths = explode("-",str_replace(" ", "",$_POST['formTB-spotDepth']));
			$minDepth = $depths[0];
			$maxDepth = $depths[1];
		} else {
			$minDepth = $_POST['formTB-spotDepth'];
		}
		
		if (strpos($_POST['formTB-spotGap'], "-") !== false) {
			$gaps = explode("-",str_replace(" ", "",$_POST['formTB-spotGap']));
			$minGap = $gaps[0];
			$maxGap = $gaps[1];
		} else {
			$minGap = $_POST['formTB-spotGap'];
		}
		$runnable = ($_POST['formCB-run'] === 'true' ? '1' : '0');
		
		// Update Spot
		if ($btnStatus == 2) {
			$query = "SELECT * FROM takeoff WHERE id=".$spotID;
			$result = $dbController->runQueryBasic($query);
			
			$infoArray = array (
				array('spotTypeID',$result['spotTypeID'],$spotTypeID),
				array('description',$result['description'],$spotName),
				array('heightFt',$result['heightFt'],$minHeight),
				array('heightFtMax',$result['heightFtMax'], $maxHeight),
				array('isRunnable',$result['isRunnable'],$runnable),
				array('poolDepth',$result['poolDepth'],$minDepth),
				array('poolDepthMax',$result['poolDepthMax'],$maxDepth),
				array('gapFt',$result['gapFt'],$minGap),
				array('gapFtMax',$result['gapFtMax'],$maxGap)
			);
		}
		
		// Add Spot
		$nameTaken = false;
		if ($btnStatus == 3) {
			
			// Check if Name is Taken
			$query = "SELECT * FROM takeoff WHERE locationID=".$locID." AND description='".$spotName."'";
			$result = $dbController->runQueryBasic($query);
			
			if ($result !== null) {
				echo "Spot Name is already taken.";
				$nameTaken = true;
			} else {
				
				// Add New Spot to Table
				$query = "INSERT INTO takeoff(locationID, createDate, isActive) VALUES('$locID','$createDate','1')";
				$spotID = $dbController->insertQuery($query);
				
				$infoArray = array (
					array('spotTypeID','',$spotTypeID),
					array('description','',$spotName),
					array('heightFt','',$minHeight),
					array('heightFtMax','', $maxHeight),
					array('isRunnable','0',$runnable),
					array('poolDepth','',$minDepth),
					array('poolDepthMax','',$maxDepth),
					array('gapFt','',$minGap),
					array('gapFtMax','',$maxGap)
				);
			}
		}
		
		// Upload Image
		$isUpdated = false;
		if ($imageChanged == 1) {
			$isUpdated = true;
			$imageSrc = $_POST['formTB-imageFile'];
			$newName = $upController->uploadURL($imageSrc, $userID);
			
			// Update Database
			$createDate = date('Y-m-d H:i:s');
			$query = "SELECT url FROM media WHERE mediaType='Main' && forSpotID='$spotID' && isActive='1'";
			$oldName = $dbController->runQuery($query);
			if (isset($oldName)) {
				$oldName = $oldName[0]['url'];
			} else {
				$oldName = "";
			}
			
			$query = "UPDATE media SET isActive='0' WHERE mediaType='Main' && forSpotID='$spotID' && isActive='1'";
			$dbController->runQueryNoReturn($query);
			
			$query = "INSERT INTO media(mediaType, forSpotID, addedByUserID, url, isActive, createDate) VALUES ('Main', '$spotID', '$userID', '$newName', '1', '$createDate')";
			$dbController->runQueryNoReturn($query);
			
			$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID, takeoffID) VALUES('$userID','takeoff','mainPhoto','$oldName','$newName','$createDate','$locID','$spotID')";
			$dbController->runQueryNoReturn($query);
		}

		// Send Updates if the information has changed.
		if ($nameTaken == false) {
			foreach($infoArray as $row) {
				if (($row[1] != $row[2])) {
					//echo $row[0] . " - " . $row[1] . " - " . $row[2];
					
					// Update Spot
					$query = "UPDATE takeoff SET ".$row[0]."='".$row[2]."' WHERE id=".$spotID;
					$dbController->runQueryNoReturn($query);
					
					// Changelog
					$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID, takeoffID) VALUES('$userID','takeoff','$row[0]','$row[1]','$row[2]','$createDate','$locID','$spotID')";
					$dbController->runQueryNoReturn($query);
					
					$isUpdated = true;
				}
			}
			
			// Final Update
			if ($isUpdated == true) {
				$query = "UPDATE takeoff SET lastUpdate='$createDate', updatedBy=".$userID." WHERE id=".$spotID;
				$dbController->runQueryNoReturn($query);
				
				if ($btnStatus == 2) {
					echo "Spot Updated Sucessfully.";
				}
				if ($btnStatus == 3) {
					echo "New Spot Added Sucessfully.";
				}
			} else {
				if ($btnStatus == 2) {
					echo "No Changes Made.";
				}
				if ($btnStatus == 3) {
					echo "New Spot Added Sucessfully.";
				}
			}
		}
	}
?>