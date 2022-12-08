<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	// Pre-Set & Required Variables
	$locName = $_POST['formTB-locName'];
	$locTypeID = $_POST['formTB-locTypeID'];
	$latitude = str_replace(" ", "",$_POST['formTB-lat']);
	$longitude = str_replace(" ", "",$_POST['formTB-long']);
	$isPublic = ($_POST['formCB-pubic'] == "true" ? 1 : 0);
	$isPaid = ($_POST['formCB-paid'] == "true" ? 1 : 0);
	$isOpen = ($_POST['formCB-open'] == "true" ? 1 : 0);
	$isParking = ($_POST['formCB-parking'] == "true" ? 1 : 0);
	$isLocal = ($_POST['formCB-local'] == "true" ? 1 : 0);
	
	// Check if Location Name isnt Taken
	if ($locName != "" && $locName != null) {
		$query = "SELECT * FROM location WHERE description='".$locName."'";
		$result = $dbController->runQueryBasic($query);
		if (isset($result)) {
			echo "Location Name '".$locName."' has already been taken.";
		} else {
			// Check if Latitude & Longitude is not numbers.
			if (!is_numeric($latitude) && !is_numeric($longitude)) {
				echo "Latitude and Longitude must be set as numbers.";
			} else {
				
				// Create Location
				$createDate = date('Y-m-d H:i:s');
				$query = "INSERT INTO location(locationTypeID, description, latitude, longitude, createDate, lastUpdate, updatedBy) VALUES('$locTypeID','$locName','$latitude','$longitude','$createDate','$createDate','$userID')";
				$locID = $dbController->insertQuery($query);
				
				$infoArray = array (
					array('parkingDescription','',$_POST['formTB-parkDesc']),
					array('hikeMiles','',$_POST['formTB-hikeMiles']),
					array('hikeDifficulty','',$_POST['formTB-hikeDiff']),
					array('otherInfo','',$_POST['formTB-other']),
					array('isPublic','',$isPublic),
					array('isPaid','',$isPaid),
					array('isOpen','',$isOpen),
					array('isParking','',$isParking),
					array('isLocal','',$isLocal),
					array('address','',$_POST['formTB-addr']),
					array('city','',$_POST['formTB-city']),
					array('state','',$_POST['formTB-state']),
					array('zip','',$_POST['formTB-zip']),
					array('country','',$_POST['formTB-country'])
				);

				foreach($infoArray as $row) {
					if ($row[1] != $row[2]) {
						// Update Location
						$query = "UPDATE location SET ".$row[0]."='".$row[2]."' WHERE id=".$locID;
						$dbController->runQueryNoReturn($query);
			
						// Changelog
						$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, locationID) VALUES('$userID','location','$row[0]','$row[1]','$row[2]','$createDate','$locID')";
						$dbController->runQueryNoReturn($query);
					}
				}
				
				echo $locID . ";" . $latitude . ";" . $longitude;
			}
		}
	} else {
		echo "Location Name cannot be blank.";
	}
?>