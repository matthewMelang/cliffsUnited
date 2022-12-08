<?php
	date_default_timezone_set('America/New_York');
	session_start();
	$userID = $_SESSION['nID'];
	
	require("DBController.php");
	$dbController = new DBController();
	
	
	// Get User Account Type
	$query = "SELECT * FROM accounts WHERE id=$userID";
	$result = $dbController->runQuery($query);
	$accountType = $result[0]['accountType'];
	
	$accountID = $_POST['accountID'];
	
	
	
	$query = "SELECT * FROM accounts WHERE id=$accountID";
	$result = $dbController->runQuery($query);
	$createDate = date('Y-m-d H:i:s');
	if (isset($result)) {
		if ($accountType == "Admin" || $accountType == "Moderator") {
			$searchable = $_POST['searchable'];
			$comment = $_POST['comment'];
			$editContent = $_POST['editContent'];
			$editProfile = $_POST['editProfile'];
			$inviteUsers = $_POST['inviteUsers'];
			$message = $_POST['message'];
			$report = $_POST['report'];
			$banned = $_POST['banned'];
			
			$searchableDuration = $_POST['searchable-duration'];
			$commentDuration = $_POST['comment-duration'];
			$editContentDuration = $_POST['editContent-duration'];
			$editProfileDuration = $_POST['editProfile-duration'];
			$inviteUsersDuration = $_POST['inviteUsers-duration'];
			$messageDuration = $_POST['message-duration'];
			$reportDuration = $_POST['report-duration'];
			$bannedDuration = $_POST['banned-duration'];
			
			$infoArray = array (
					array('isSearchable',($result[0]['isSearchable'] == 1 ? "true" : "false"),$searchable, 'bannedSearchableUntil', $searchableDuration, $result[0]['bannedSearchableUntil']),
					array('canComment',($result[0]['canComment'] == 1 ? "true" : "false"),$comment, 'bannedCommentUntil', $commentDuration, $result[0]['bannedCommentUntil']),
					array('canEditContent',($result[0]['canEditContent'] == 1 ? "true" : "false"),$editContent, 'bannedEditContentUntil', $editContentDuration, $result[0]['bannedEditContentUntil']),
					array('canEditProfile',($result[0]['canEditProfile'] == 1 ? "true" : "false"), $editProfile, 'bannedEditProfileUntil', $editProfileDuration, $result[0]['bannedEditProfileUntil']),
					array('canInvite',($result[0]['canInvite'] == 1 ? "true" : "false"),$inviteUsers, 'bannedInviteUntil', $inviteUsersDuration, $result[0]['bannedInviteUntil']),
					array('canSendMessage',($result[0]['canSendMessage'] == 1 ? "true" : "false"),$message, 'bannedMessageUntil', $messageDuration, $result[0]['bannedMessageUntil']),
					array('canReport',($result[0]['canReport'] == 1 ? "true" : "false"),$report, 'bannedReportUntil', $reportDuration, $result[0]['bannedReportUntil'])
				);
			
			if ($accountType == "Admin" || ($accountType != "Admin" && $accountID != $userID)) {
				
				$isUpdated = false;
				foreach($infoArray as $row) {
					if (($row[1] != $row[2] || $row[4] != 0)) {
						//echo $row[0] . " - " . $row[1] . " - " . $row[2] . " - " . $row[4];
						
						if ($row[4] < 1000) {
							if ($row[4] != 0) {
								$val = date('m/d/Y H:i:s', strtotime($createDate . " + ".$row[4]." days"));
							} else {
								$val = "";
							}
						} else {
							$val = "";
						}
						
						// Update Account
						if ($row[2] == "false" && $row[4] > 0) {
							$isUpdated = true;
							$query = "UPDATE accounts SET ".$row[0]."='".($row[2] == "true" ? 1 : 0)."', ".$row[3]."='".$val."' WHERE id=".$accountID;
							$dbController->runQueryNoReturn($query);
							
							
							if ($row[1] != $row[2]) {
								// Changelog isBanned
								$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','$row[0]','$row[1]','$row[2]','$createDate','$userID')";
								$dbController->runQueryNoReturn($query);
							}
							
						} else if ($row[2] == "true") {
							$isUpdated = true;
							$val = "";
							$query = "UPDATE accounts SET ".$row[0]."=".($row[2] == "true" ? 1 : 0).", ".$row[3]."='' WHERE id=".$accountID;
							$dbController->runQueryNoReturn($query);
							
							if ($row[1] != $row[2]) {
								// Changelog isBanned
								$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','$row[0]','$row[1]','$row[2]','$createDate','$userID')";
								$dbController->runQueryNoReturn($query);
							}
						}
						
						if ($row[5] != $val) {
							// Changelog Ban Duration
							$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','$row[3]','$row[5]','$val','$createDate','$userID')";
							$dbController->runQueryNoReturn($query);
						}
					}
				}
				
				// Complete Ban
				$otherAcctType = $result[0]['accountType'];
				if ($otherAcctType != "Admin") {
					
					$bannedDate = date('m/d/Y H:i:s', strtotime($createDate . " + ".$bannedDuration." days"));
					if ($bannedDuration == 0) {
						$bannedDate = "";
					}
					$prevAcctStatus = $result[0]['acctStatus'];
					$prevBanDate = $result[0]['bannedUntilDate'];
					
					
					
					if ($banned == "true") {
						$isUpdated = true;
						$query = "UPDATE accounts SET acctStatus='Inactive', bannedUntilDate='".$bannedDate."' WHERE id=".$accountID;
						$dbController->runQueryNoReturn($query);
						
						// Changelog
						if ($prevAcctStatus != "Inactive") {
							$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','acctStatus','$prevAcctStatus','Inactive','$createDate','$userID')";
							$dbController->runQueryNoReturn($query);
						}
						
						if ($prevBanDate != $bannedDate) {
							$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','bannedUntilDate','$prevBanDate','".$bannedDate."','$createDate','$userID')";
							$dbController->runQueryNoReturn($query);
						}
						
					} else {
						$isUpdated = true;
						$bannedDate = "";
						$query = "UPDATE accounts SET acctStatus='Active', bannedUntilDate='".$bannedDate."' WHERE id=".$accountID;
						$dbController->runQueryNoReturn($query);
						
						// Changelog
						if ($prevAcctStatus != "Active") {
							$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','acctStatus','$prevAcctStatus','Active','$createDate','$userID')";
							$dbController->runQueryNoReturn($query);
						}
						
						if ($prevBanDate != $bannedDate) {
							$query = "INSERT INTO changelog(userID, tableChanged, tableField, changedFrom, changedTo, dateChanged, moderatorID) VALUES('$accountID','accounts','bannedUntilDate','$prevBanDate','".$bannedDate."','$createDate','$userID')";
							$dbController->runQueryNoReturn($query);
						}
					}
				}
				
				
				
				if ($isUpdated == true) {
					echo "Changes Submitted.";
				} else {
					echo "No Changes Made.";
				}
			} else {
				echo "Cannot change priviligies for yourself.";
			}
		} else {
			echo "Users cannot change priviliges.";
		}
	} else {
		echo "Cannot find account.";
	}
?>