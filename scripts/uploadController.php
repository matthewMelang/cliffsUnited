<?php
	
class uploadController 
{
	private $DBcont;
	
	function __construct($controller)
    {
        $this->DBCont = $controller;
    }
	
	function uploadURL($url, $userID) {
		$newName = "../images/Media/" . $userID . "_" . round(microtime(true) * 1000) . "_" . bin2hex(random_bytes(5)) . ".jpg";
		copy($url, $newName);
		
		return str_replace("../","",$newName);
	}
	
	function uploadImage($mediaType, $fileToUpload, $userID, $id) {
		/*
			MEDIA TYPES:
			1 - Account Profile Picture
			2 - Location Image
			3 - Spot Image
		*/
		
		$target_file = "../images/Media/" . basename($fileToUpload["name"]);
		$ext = pathinfo($fileToUpload["name"], PATHINFO_EXTENSION);
		$newName = "../images/Media/" . $userID . "_" . round(microtime(true) * 1000) . "_" . bin2hex(random_bytes(5)) . ".jpg";
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		
		// Check if image file is a actual image or fake image
		if(isset($_POST["submit"])) {
			$check = getimagesize($fileToUpload["tmp_name"]);
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
		if ($fileToUpload["size"] > 500000) {
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
			if (move_uploaded_file($fileToUpload["tmp_name"], $newName)) {
				echo "The file ". basename($fileToUpload["name"]). " has been uploaded.";
				$newName = str_replace("../","", $newName);
				
				// Update Database
				updateDatabase($mediaType, $userID, $id, $newName);
				
			} else {
				echo "Sorry, there was an error uploading your file.";
			}
		}
	}
}

?>