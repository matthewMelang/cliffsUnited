<?php
	date_default_timezone_set('America/New_York');
	require("DBController.php");
	$dbController = new DBController();
	
	$showIncomplete = $_POST['showIncomplete'];

	$query = "SELECT * FROM location LEFT JOIN takeoff ON location.id = takeoff.locationID";

	$locQuery = "";
	if (isset($_POST['locType'])) {
		$locTypeObj = $_POST['locType'];
		
		foreach($locTypeObj[0] as $key => $val) {
			if (strlen($locQuery) == 0) {
				$locQuery = "(location.locationTypeID=".$key;
			} else {
				$locQuery = $locQuery . " OR location.locationTypeID=".$key;
			}
		}
	}
	$locQuery = $locQuery . ")";
	if (strlen($locQuery) > 1) {
		$query = $query . " WHERE " . $locQuery;
	}
	
	$spotQuery = "";
	if (isset($_POST['spotType'])) {
		$spotTypeObj = $_POST['spotType'];
		
		foreach($spotTypeObj[0] as $key => $val) {
			if (strlen($spotQuery) == 0) {
				$spotQuery = "(takeoff.spotTypeID=".$key;
			} else {
				$spotQuery = $spotQuery . " OR takeoff.spotTypeID=".$key;
			}
		}
	}
	$spotQuery = $spotQuery . ")";
	if (strlen($spotQuery) > 1) {
		if (strlen($spotQuery) == 1) {
			$query = $query . " WHERE " . $spotQuery;
		} else {
			$query = $query . " AND " . $spotQuery;
		}
	}
	
	$logQuery = "";
	if (isset($_POST['logistic'])) {
		$logisticObj = $_POST['logistic'];
		
		foreach($logisticObj[0] as $key => $val) {
			if (strlen($logQuery) == 0) {
				$logQuery = "(location." . $key . "='1'";
			} else {
				$logQuery = $logQuery . " OR location." . $key . "='1'";
			}
		}
	}
	
	$logQuery = $logQuery . ")";
	if (strlen($logQuery) > 1) {
		if (strlen($locQuery) == 1) {
			$query = $query . " WHERE " . $logQuery;
		} else {
			$query = $query . " AND " . $logQuery;
		}
	}
	
	$stateQuery = "";
	if (isset($_POST['stateList'])) {
		$stateList = explode(",",$_POST['stateList']);
		foreach ($stateList as $v) {
			if ($v != "") {
				if (strlen($stateQuery) == 0) {
					$stateQuery = '(location.state="'.$v.'"';
				} else {
					$stateQuery = $stateQuery . ' OR location.state="'.$v.'"';
				}
			}
		}
	}
	$stateQuery = $stateQuery . ")";
	if (strlen($stateQuery) > 1) {
		if (strlen($locQuery) == 1 && strlen($logQuery) == 1) {
			$query = $query . " WHERE " . $stateQuery;
		} else {
			$query = $query . " AND " . $stateQuery;
		}
	}
	
	if (isset($_POST['minMax'])) {
		$minMaxObj = $_POST['minMax'];
		
		$minHeight = $minMaxObj[0]['minHeight'];
		$maxHeight = $minMaxObj[0]['maxHeight'];
		$addIncomplete = ($showIncomplete == "true" ? ' OR takeoff.heightFt IS NULL' : '');
		$heightQuery = "(takeoff.heightFt >= ". $minHeight . " OR (takeoff.heightFtMax >= ". $minHeight ." AND takeoff.heightFtMax != 0)".$addIncomplete.")".
					" AND (takeoff.heightFt <= ". $maxHeight ." OR (takeoff.heightftMax <= ". $maxHeight ." AND takeoff.heightFtMax != 0)".$addIncomplete.")";
		if (strlen($locQuery) == 1 && strlen($logQuery) == 1 && strlen($stateQuery) == 1) {
			$query = $query . " WHERE " . $heightQuery;
		} else {
			$query = $query . " AND " . $heightQuery;
		}
		
		$minDepth = $minMaxObj[0]['minDepth'];
		$maxDepth = $minMaxObj[0]['maxDepth'];
		$addIncomplete = ($showIncomplete == "true" ? ' OR takeoff.poolDepth IS NULL' : '');
		$depthQuery = "(takeoff.poolDepth >= ". $minDepth . " OR (takeoff.poolDepthMax >= ". $minDepth ." AND takeoff.poolDepthMax != 0)".$addIncomplete.")".
				" AND (takeoff.poolDepth <= ". $maxDepth ." OR (takeoff.poolDepthMax <= ". $maxDepth ." AND takeoff.poolDepthMax != 0)".$addIncomplete.")";
		$query = $query . " AND " . $depthQuery;
		
		
		$minMiles = $minMaxObj[0]['minMiles'];
		$maxMiles = $minMaxObj[0]['maxMiles'];
	}
	
	$dictLocations = array();
	$result = $dbController->runQuery($query);
	foreach ($result as $key => $val) {
		$query = "SELECT id FROM takeoff WHERE locationID=".$val[0];
		$rows = $dbController->contQuery($query);
		
		$locTypeID = $val['locationTypeID'];
		
		//echo $locTypeID;
		
		if ($locTypeID == "") {
			$locColor = "white";
		} else {
			$query = "SELECT * FROM locationType WHERE id=".$locTypeID;
			$locColor = $dbController->runQuery($query);
			if (isset($locColor)) {
				$locColor = $locColor[0]['color'];
			} else {
				$locColor = "white";
			}
		}
		//$locColor = "white";
		
		
		
		$arr = array('id' => $val[0], 'latitude' => $val['latitude'], 'longitude' => $val['longitude'], 'spotCount' => $rows, 'locationColor' => $locColor);
		$dictLocations[$val[0]] = $arr;
	}
	
	echo json_encode($dictLocations);
	
?>