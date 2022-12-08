<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$target_dir = "../images/Media/";
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$ext = pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION);
	$newName = $target_dir . $userID . "_" . round(microtime(true) * 1000) . "_" . bin2hex(random_bytes(5)) . ".jpg";
	$uploadOk = 1;
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
	// Check if image file is a actual image or fake image
	if(isset($_POST["submit"])) {
		$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
		if($check !== false) {
			echo "File is an image - " . $check["mime"] . ".";
			$uploadOk = 1;
		} else {
			echo "File is not an image.";
			$uploadOk = 0;
		}
	}
	// Check if file already exists
	if (file_exists($target_file)) {
		echo "Sorry, file already exists.";
		$uploadOk = 0;
	}
	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 500000) {
		echo "Sorry, your file is too large.";
		$uploadOk = 0;
	}
	// Allow certain file formats
	if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
	&& $imageFileType != "gif" ) {
		echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		echo "Sorry, your file was not uploaded.";
	// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $newName)) {
			echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
			
			// Set old Media to Inactive
			$query = "UPDATE media SET isActive='0' WHERE mediaType='Profile Picture' && forAccountID='$userID' && isActive='1'";
			$dbController->runQueryNoReturn($query);
			
			// Update Database with active Media
			$createDate = date('Y-m-d H:i:s');
			$newName = str_replace("../","", $newName);
			$query = "INSERT INTO media(mediaType, forAccountID, addedByUserID, url, isActive, createDate) VALUES ('Profile Picture', '$userID', '$userID', '$newName', '1', '$createDate')";
			$dbController->runQueryNoReturn($query);
			
			
			
		} else {
			echo "Sorry, there was an error uploading your file.";
		}
	}
	header("location:../profile.php");
?>