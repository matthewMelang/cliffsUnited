<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT * FROM accounts WHERE acctStatus='Inactive' OR isSearchable=0 OR canComment=0 OR canEditContent=0 OR canEditProfile=0 OR canInvite=0 OR canSendMessage=0 OR canReport=0";
	$result = $dbController->runQuery($query);
	
	$banList = [];
	
	if (isset($result)) {
		foreach($result as $key => $val) {
			$acctID = $val['id'];
			$acctUsername = $val['username'];
			$acctStatus = $val['acctStatus'];
			$searchable = $val['isSearchable'];
			$comment = $val['canComment'];
			$editContent = $val['canEditContent'];
			$editProfile = $val['canEditProfile'];
			$invite = $val['canInvite'];
			$message = $val['canSendMessage'];
			$report = $val['canReport'];
			
			
			$bannedDate = $val['bannedUntilDate'];
			$searchableDate = $val['bannedSearchableUntil'];
			$commentDate = $val['bannedCommentUntil'];
			$editContentDate = $val['bannedEditContentUntil'];
			$editProfileDate = $val['bannedEditProfileUntil'];
			$inviteDate = $val['bannedInviteUntil'];
			$messageDate = $val['bannedMessageUntil'];
			$reportDate = $val['bannedReportUntil'];
			
			$infoArray = array (
					array('Sitewide Ban','bannedUntilDate',$acctStatus,$bannedDate),
					array('Discoverable','bannedSearchableUntil',$searchable,$searchableDate),
					array('Can Comment','bannedCommentUntil',$comment,$commentDate),
					array('Can Edit Content','bannedEditContentUntil',$editContent,$editContentDate),
					array('Can Edit Profile','bannedEditProfileUntil',$editProfile,$editProfileDate),
					array('Can Invite Users','bannedInviteUntil',$invite,$inviteDate),
					array('Can Message Users','bannedMessageUntil',$message,$messageDate),
					array('Can Send Reports','bannedReportUntil',$report,$reportDate)
					
				);
			
			
			// Privilige Bans
			foreach($infoArray as $row) {
				if ($row[2] == 0 || $row[2] == "Inactive") {
					
					// Get Banned On & by Moderator
					$query = "SELECT * FROM changelog WHERE userID=$acctID AND tableChanged='accounts' AND tableField='".$row[1]."' AND changedTo='".$row[3]."'";
					$changelog = $dbController->runQuery($query);
					
					if (isset($changelog)) {
						$bannedOn = $changelog[0]['dateChanged'];
						$byMod = $changelog[0]['moderatorID'];
						$otherDate = new DateTime($row[3]);
						$today = new DateTime('now');
						$diff = date_diff($otherDate, $today);
						$daysLeft = $diff->format('%a');
						
						// Get Moderator Username
						$query = "SELECT username FROM accounts WHERE id=$byMod";
						$username = $dbController->runQuery($query);
						if (isset($username)) {
							$username = $username[0]['username'];
						} else {
							$username = "";
						}
						
						$tempArr = array($acctID, $acctUsername, $row[0], $bannedOn, $row[3], $daysLeft, $byMod, $username);
						array_push($banList, $tempArr);
					}
				}
				
			}
		}
		echo json_encode($banList);
	} else {
		echo "No Results";
	}

?>