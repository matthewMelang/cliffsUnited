<?php
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	require("uploadController.php");
	$upController = new uploadController($dbController);
	
	// Get OLD Values
	$locID = $_POST['formTB-locID'];
	$locTypeID = $_POST['formTB-locTypeID'];
	
	
	$isPaid = $isOpen = $isParking = $isLocal = $run = 0;
	$isPublic = ($_POST['formCB-pubic'] == "true" ? 1 : 0);
	$isPaid = ($_POST['formCB-paid'] == "true" ? 1 : 0);
	$isOpen = ($_POST['formCB-open'] == "true" ? 1 : 0);
	$isParking = ($_POST['formCB-parking'] == "true" ? 1 : 0);
	$isLocal = ($_POST['formCB-local'] == "true" ? 1 : 0);
	
	
	$imageChanged = 0;
	if (is_numeric($_POST['formTB-imageChanged'])) {
		$imageChanged = 1;
	} else {
		$imageChanged = 0;
	}
	
	// Upload Image
	$hasUpdated = false;
	if ($imageChanged == 1) {
		$hasUpdated = true;
		$imageSrc = $_POST['formTB-imageFile'];
		$newName = $upController->uploadURL($imageSrc, $userID);
		
		// Update Database
		$createDate = date('Y-m-d H:i:s');
		$query = "SELECT url FROM media WHERE mediaType='Main' && forLocationID='$locID' && isActive='1'";
		$oldName = $dbController->runQuery($query);
		if (isset($oldName)) {
			$oldName = $oldName[0]['url'];
		} else {
			$oldName = "";
		}
		
		$query = "UPDATE media SET isActive='0' WHERE mediaType='Main' && forLocationID='$locID' && isActive='1'";
		$dbController->runQueryNoReturn($query);
		
		$query = "INSERT INTO media(mediaType, forLocationID, addedByUserID, url, isActive, createDate) VALUES ('Main', '$locID', '$userID', '$newName', '1', '$createDate')";
		$dbController->runQueryNoReturn($query);
		
		$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID) VALUES('$userID','location','mainPhoto','$oldName','$newName','$createDate','$locID')";
		$dbController->runQueryNoReturn($query);
	}
	
	$query = "SELECT * FROM location WHERE id=".$locID;
	$result = $dbController->runQueryBasic($query);
	
	$infoArray = array (
		array('description',$result['description'],$_POST['formTB-locName']),
		array('locationTypeID',$result['locationTypeID'],$locTypeID),
		array('parkingDescription',$result['parkingDescription'],$_POST['formTB-parkDesc']),
		array('hikeMiles',$result['hikeMiles'],$_POST['formTB-hikeMiles']),
		array('hikeDifficulty',$result['hikeDifficulty'],$_POST['formTB-hikeDiff']),
		array('otherInfo',$result['otherInfo'],$_POST['formTB-other']),
		
		array('isPublic',$result['isPublic'],$isPublic),
		array('isPaid',$result['isPaid'],$isPaid),
		array('isOpen',$result['isOpen'],$isOpen),
		array('isParking',$result['isParking'],$isParking),
		array('isLocal',$result['isLocal'],$isLocal),
		
		array('address',$result['address'],$_POST['formTB-addr']),
		array('city',$result['city'],$_POST['formTB-city']),
		array('state',$result['state'],$_POST['formTB-state']),
		array('zip',$result['zip'],$_POST['formTB-zip']),
		array('country',$result['country'],$_POST['formTB-country']),
		array('latitude',$result['latitude'],$_POST['formTB-lat']),
		array('longitude',$result['longitude'],$_POST['formTB-long'])
	);
	
	$createDate = date('Y-m-d H:i:s');
	
	
	foreach($infoArray as $row) {
		if ($row[1] != $row[2]) {
			$hasUpdated = true;
			
			// Update Location
			$query = "UPDATE location SET ".$row[0]."='".$row[2]."' WHERE id=".$locID;
			$dbController->runQueryNoReturn($query);
			
			// Changelog
			$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID) VALUES('$userID','location','$row[0]','$row[1]','$row[2]','$createDate','$locID')";
			$dbController->runQueryNoReturn($query);
		}
	}
	
	if ($hasUpdated == true) {
		$query = "UPDATE location SET lastUpdate='$createDate', updatedBy=$userID WHERE id=".$locID;
		$dbController->runQueryNoReturn($query);
		echo "Changes has been Submitted.";
	} else {
		echo "No Changes Submitted.";
	}
?>