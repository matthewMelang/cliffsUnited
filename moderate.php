<?php
	date_default_timezone_set('America/New_York');
	session_start();
	if (!isset($_SESSION['nID'])) {
		header("Location: login.php");
	}
	$userID = $_SESSION['nID'];
	
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	// Check if the User is an admin or moderator
	$query = "SELECT username, accountType FROM accounts WHERE id=$userID";
	$acctType = $dbController->runQuery($query);
	$username = $acctType[0]['username'];
	$acctType = $acctType[0]['accountType'];
	$addModButton = "";
	if ($acctType != "Admin" && $acctType != "Moderator") {
		header("Location: home.php");
	} else {
		$addModButton = "<a class='dropdown-item' role='presentation' href='moderate.php'>Admin Dashboard</a>";
	}
	
	// List of Ban Durations
	$query = "SELECT description, days FROM banduration ORDER BY days ASC";
	$banDuration = $dbController->runQuery($query);
	//foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }
	
	
	// List of Searchable Accounts
	if ($acctType == "Admin") {
		$query = "SELECT id, username FROM accounts";
		$accountLookup = $dbController->runQuery($query);
	} else if ($acctType == "Moderator") {
		$query = "SELECT id, username FROM accounts WHERE accountType='User'";
		$accountLookup = $dbController->runQuery($query);
	}
	
	
	// List of Searchable Accounts - Header
	$query = "SELECT id, username FROM accounts WHERE isSearchable=1 AND acctStatus='Active'";
	$accountResult = $dbController->runQuery($query);
	
	// List of Locations
	$query = "SELECT description FROM location";
	$locationList = $dbController->runQuery($query);
	
	$defaultFile = "images/Media/defaultProfile.jpg";
	$imgFile = $defaultFile;
	$query = "SELECT url FROM media WHERE forAccountID=".$userID." && isActive='1'";
	$result = $dbController->runQueryBasic($query);
	if (isset($result)) {
		$urlFile = $result['url'];
		if (isset($urlFile)) {
			if ($urlFile != "") {
				$imgFile = $urlFile;
			}
		}
	}
	
	function getTableInfo($controller, int $id, string $field, string $table) {
		$acctQuery = "SELECT ".$field." FROM ".$table." WHERE id=".$id;
		$acctResult = $controller->runQueryBasic($acctQuery);
		return $acctResult[$field];
	}
?>

<!DOCTYPE html>
<html style="height: 100vh;">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>CliffsUnited</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/multirange.css">
    <link rel="stylesheet" href="assets/css/Navigation-Clean.css">
    <link rel="stylesheet" href="assets/css/Navigation-with-Search.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/Toggle-Checkbox.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	
	<script type="text/javascript">
		$(document).ready(function() {
			getBannedUsers();
			getModeratorHistory();
			getCurrentPolicy();
		});
	
		function searchFor() {
			var searchInfo = $("#search-field").val();
			if (searchInfo.indexOf(" - ") !== -1) {
				var item = searchInfo.split(" - ")[0];
				var itemType = searchInfo.split(" - ")[1];
				
				if (itemType == "Location") {
					$(document).ready(function() {
						$.ajax({
							url: 'scripts/getLocationData.php?locationName='+item,
							type: 'GET',
							dataType: 'JSON',
							success: function(response) {
								window.location.href = "home.php?locationID=" + response[0].id
							}
						});
					});
					$("#search-field").val("");
				}
				
				if (itemType == "Account") {
					window.location.href = "viewProfile.php?username=" + item;
				}
			}
		}
		
		function selectReport(element, userID) {
			var container = $("#activeReportList tr");
			container.each(function() {
				$(this).removeClass("is-selected");
			});
			element.classList.add("is-selected");
			getReportDetails(userID);
			getAccountDetails(userID);
		}
		
		function getModeratorHistory() {
			var container = $("#tab-4");
			var objText = "{}";
			var obj = JSON.parse(objText);
			
			var fullHTML = "";
			container.html(fullHTML);
			
			$.post("scripts/getModHistory.php", obj, function(data) {
				//alert(data);
				var response = JSON.parse(data);
				$.each(response, function(index, value) {
					var thisHTML = "<div class='d-flex flex-column report-detail' style='background-color: #ddd;padding: 5px;margin-top: 10px;margin-bottom: 10px;'>" +
						"<div class='d-flex flex-column report-header' style='border-bottom: solid;border-color: #ccc;margin-bottom: 5px;'>" +
						"<div class='d-flex flex-row flex-grow-1'><a href='#'><strong>"+value[0]['modUsername']+"</strong></a>" +
						"<p><strong>&nbsp;Updated Priviliges for&nbsp;</strong></p><a href='#'><strong>"+value[0]['acctUsername']+"</strong></a>" +
						"<p class='text-right float-right flex-grow-1' style='font-size: 12px;'>"+value[0]['dateChanged']+"</p>" +
						"</div>" +
						"</div>" +
						"<div class='d-flex flex-column flex-grow-1 report-body collapsible-container hidden' style='margin-bottom: 5px;margin-top: 5px;'>";

						var field = value[0][3];
						var setTo = value[0][5];
						if (value.length > 1) {
							var setOn = new Date(value[1][6]);
							if (value[1][5] != null) {
								var setUntil = new Date(value[1][5]);
								var diff = setUntil.getTime() - setOn.getTime(); 
								var dayDiff = " for - " + (diff / (1000 * 3600 * 24)) + " days."; 
							} else {
								var dayDiff = " for - Forever";
							}
						} else {
							if (setTo == "false") {
								dayDiff = " for - Forever";
							} else {
								dayDiff = "";
							}
						}
						
						thisHTML = thisHTML + "<p style='font-size: 14px;'><strong>"+value[0][3]+"</strong> set to <strong>"+value[0][5]+"</strong>"+dayDiff+"</p>";
					
					thisHTML = thisHTML + "</div><a class='text-center flex-grow-1 align-self-end' onclick='collapse(this,0)' href='javascript:;' style='font-size: 12px;width: 100%;'>[Expand]</a>" +
						"</div>";
					
					fullHTML = fullHTML + thisHTML;
				});
				container.html(fullHTML);
			});
		}
		
		function getBannedUsers() {
			var container = $("#bannedUsersTable");
			var objText = "{}";
			var obj = JSON.parse(objText);
			//Post to PHP page
			
			var fullHTML = "";
			container.html(fullHTML);
			
			$.post("scripts/getBannedUsers.php", obj, function(data) {
				var response = JSON.parse(data);
				
				$.each(response, function( index, value ) {
					var username = value[1];
					var banType = value[2];
					var bannedOn = value[3];
					var bannedUntil = value[4];
					var daysLeft = value[5];
					var byMod = value[7];

					var thisHTML = "<tr class='text-center'>" +
							"<td><a href='viewProfile.php?username="+username+"'><strong>"+username+"</strong></a></td>" +
							"<td>"+banType+"</td>" +
							"<td>"+bannedOn+"</td>" +
							"<td>"+(bannedUntil == "" ? 'Forever' : bannedUntil)+"</td>" +
							"<td>"+(daysLeft == 0 ? '-' : daysLeft)+"</td>" +
							"<td><a href='viewProfile.php?username="+byMod+"'><strong>"+byMod+"</strong></a></td>" +
							"</tr>";
					fullHTML = fullHTML + thisHTML;
				});
				container.html(fullHTML);
			});
		}
		
		function getAccountDetails(userID, username = "") {
			var objText = "{" +
				"\"reportedID\": \"" + userID + "\"," +
				"\"reportedUsername\": \"" + username + "\"" +
				"}";
			var obj = JSON.parse(objText);
			//Post to PHP page
			$.post("scripts/getAccountDetails.php", obj, function(data) {
				if (data != "\"\"") {
					var response = JSON.parse(data);
					
					$("#mod-info-noneSelected").addClass("hidden");
					$("#mod-info-container").removeClass("hidden");
					
					$("#user-lookup").val(response[0]['username']);
					$("#user-acctType").html(response[0]['accountType']);
					$("#user-id").html(response[0]['id']);
					$("#user-username").html(response[0]['username']);
					$("#user-inviteCount").html(response[0]['inviteCount']);
					$("#user-changeCount").html(response[0]['changeCount']);
					$("#user-totalReports").html(response[0]['totalReports']);
					$("#user-reportsActiveInactive").html(response[0]['activeRep'] + " / " + response[0]['inactiveRep']);
					$("#user-totalSeverity").html(response[0]['totalSeverity']);
					$("#user-severityActiveInactive").html(response[0]['activeSev'] + " / " + response[0]['inactiveSev']);
					
					$("#toggle-searchable").prop('checked', (response[0]['isSearchable'] == 1 ? true : false)).change();
					$("#toggle-comment").prop('checked', (response[0]['canComment'] == 1 ? true : false)).change();
					$("#toggle-editContent").prop('checked', (response[0]['canEditContent'] == 1 ? true : false)).change();
					$("#toggle-editProfile").prop('checked', (response[0]['canEditProfile'] == 1 ? true : false)).change();
					$("#toggle-inviteUsers").prop('checked', (response[0]['canInvite'] == 1 ? true : false)).change();
					$("#toggle-message").prop('checked', (response[0]['canSendMessage'] == 1 ? true : false)).change();
					$("#toggle-report").prop('checked', (response[0]['canReport'] == 1 ? true : false)).change();
					$("#toggle-banned").prop('checked', (response[0]['acctStatus'] == "Inactive") ? true : false).change();
					
					
					$("#disabledUntil-searchable").html((response[0]['isSearchable'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedSearchableUntil'] == "" ? 'Forever' : response[0]['bannedSearchableUntil']) : ""));
					$("#disabledUntil-comment").html((response[0]['canComment'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedCommentUntil'] == "" ? 'Forever' : response[0]['bannedCommentUntil']) : ""));
					$("#disabledUntil-editContent").html((response[0]['canEditContent'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedEditContentUntil'] == "" ? 'Forever' : response[0]['bannedEditContentUntil']) : ""));
					$("#disabledUntil-editProfile").html((response[0]['canEditProfile'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedEditProfileUntil'] == "" ? 'Forever' : response[0]['bannedEditProfileUntil']) : ""));
					$("#disabledUntil-inviteUsers").html((response[0]['canInvite'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedInviteUntil'] == "" ? 'Forever' : response[0]['bannedInviteUntil']) : ""));
					$("#disabledUntil-message").html((response[0]['canSendMessage'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedMessageUntil'] == "" ? 'Forever' : response[0]['bannedMessageUntil']) : ""));
					$("#disabledUntil-report").html((response[0]['canReport'] == 0 ? "<strong>Disabled Until: </strong>"+(response[0]['bannedReportUntil'] == "" ? 'Forever' : response[0]['bannedReportUntil']) : ""));
					$("#disabledUntil-banned").html((response[0]['acctStatus'] == "Inactive" ? "<strong>Disabled Until: </strong>"+(response[0]['bannedUntilDate'] == "" ? 'Forever' : response[0]['bannedUntilDate']) : ""));
					
					$("#acctDetailsSaveChanges").attr('onclick', 'updateAccountDetails('+response[0]['id']+')');
				} else {
					
					$("#mod-info-noneSelected").removeClass("hidden");
					$("#mod-info-container").addClass("hidden");
					
					$("#user-acctType").html("");
					$("#user-id").html("");
					$("#user-username").html("");
					$("#user-inviteCount").html("");
					$("#user-totalReports").html("");
					$("#user-reportsActiveInactive").html("");
					$("#user-totalSeverity").html("");
					$("#user-severityActiveInactive").html("");
					
					$("#toggle-searchable").prop('checked', false).change();
					$("#toggle-comment").prop('checked', false).change();
					$("#toggle-editContent").prop('checked', false).change();
					$("#toggle-editProfile").prop('checked', false).change();
					$("#toggle-inviteUsers").prop('checked', false).change();
					$("#toggle-message").prop('checked', false).change();
					$("#toggle-report").prop('checked', false).change();
					$("#toggle-banned").prop('checked', false).change();
					$("#acctDetailsSaveChanges").attr('onclick', '');
					
					$("#disabledUntil-searchable").html("");
					$("#disabledUntil-comment").html("");
					$("#disabledUntil-editContent").html("");
					$("#disabledUntil-editProfile").html("");
					$("#disabledUntil-inviteUsers").html("");
					$("#disabledUntil-message").html("");
					$("#disabledUntil-report").html("");
					$("#disabledUntil-banned").html("");
				}
			});
		}
		
		function getReportDetails(userID) {
			var objText = "{" +
				"\"reportedID\": \"" + userID + "\"" +
				"}";
			var obj = JSON.parse(objText);
			
			//Post to PHP page
			$.post("scripts/getReportDetails.php", obj, function(data) {
				var response = JSON.parse(data);
				
				var detailsHTML = "";
				$("#mod-details-container").html(detailsHTML);
				$.each(response, function(index, value) {
					var reportID = value['id'];
					var reportTypeID = value['reportTypeID'];
					var reportType = value['reportType'];
					var tbl = value['tbl'];
					var reportedID = value['userID'];
					var reportedUsername = value['reportedUsername'];
					var creatorUsername = value['creatorUsername'];
					var modUsername = value['modUsername'];
					var reportContent = value['reportContent'];
					var status = (value['status'] == 0 ? 'Active' : 'Closed');
					var statusColor = (value['status'] == 0 ? 'rgb(0,174,7)' : 'rgb(255,0,0)');
					var isGuilty = value['isGuilty'];
					var createDate = value['createDate'];
					
					var thisHTML = "<div class='d-flex flex-column report-detail' style='background-color: #ddd;padding: 5px;margin-top: 10px;margin-bottom: 10px;'>" +
						"<div class='d-flex flex-column report-header' style='border-bottom: solid;border-color: #ccc;margin-bottom: 5px;'>" +
							"<div class='d-flex flex-row flex-grow-1'>";
								
								if (reportTypeID == 1) {
									thisHTML = thisHTML + 
									"<p><strong>"+reportType+"</strong></p>";
								} else if (reportTypeID == 2) {
									thisHTML = thisHTML + 
									"<p><strong>"+reportType+"</strong></p>";
								} else if (reportTypeID == 3 || reportTypeID == 4) {
									if (tbl == "thread") {
										thisHTML = thisHTML + 
										"<p><strong>"+reportType+" in Private Message</strong></p>";
									} else if (tbl == "comments") {
										var location = reportContent['locationName'];
										thisHTML = thisHTML + 
										"<p><strong>"+reportType+" on&nbsp;</strong></p><a href='home.php?locationID="+reportContent[0]['locationID']+"'><strong>"+location+"</strong></a>";
									}
								} else if (reportTypeID == 5) {
									var location = reportContent['locationName'];
									thisHTML = thisHTML + 
									"<p><strong>"+reportType+" on&nbsp;</strong></p><a href='home.php?locationID="+reportContent[0]['locationID']+"'><strong>"+location+"</strong></a>";
								}
								
								thisHTML = thisHTML + "<div class='d-flex flex-grow-1'>" +
									"<p class='text-right flex-grow-1'><strong>Status:&nbsp;</strong></p>" +
									"<p class='text-left flex-grow-1' style='color: "+statusColor+";'><strong>"+status+"</strong></p>" +
									"<p class='float-right' style='font-size: 12px;'>"+createDate+"</p>" +
								"</div>" +
							"</div>" +
							"<div class='d-flex'>";
								if (reportedID != <?php echo $userID ?>) {
									thisHTML = thisHTML + "<p style='font-size: 12px;'><strong>Submitted By:&nbsp;</strong></p><a href='viewProfile.php?username="+creatorUsername+"' style='font-size: 12px;'><strong>"+creatorUsername+"</strong></a>";
								}
								if (status == 'Closed') {
									thisHTML = thisHTML + "<p class='text-right flex-grow-1' style='font-size: 12px;'><strong>Closed By:&nbsp;</strong></p><a class='flex-grow-1' href='#' style='font-size: 12px;'><strong>"+modUsername+"</strong></a>";
								}
							thisHTML = thisHTML + "</div>" +
						"</div>" +
						"<div class='d-flex flex-row flex-grow-1 report-body collapsible-container hidden' style='margin-bottom: 5px;margin-top: 5px;'>";
						
						if (reportTypeID == 1) {
							thisHTML = thisHTML + "<img src='"+reportContent[0]['url']+"'>" +
                            "<p style='font-size: 14px;'>Uploaded on:&nbsp;"+reportContent[0]['createDate']+"</p>";
						} else if (reportTypeID == 2) {
							thisHTML = thisHTML + 
							"<div>";
							$.each(reportContent, function(index, v) {
								thisHTML = thisHTML + "<p style='font-size: 14px;'><strong>"+v['dateChanged']+"</strong>:&nbsp;"+v['changedTo']+"</p>";
							});
							thisHTML = thisHTML + "</div>";
							
						} else if (reportTypeID == 3) {
							thisHTML = thisHTML + 
							"<div>";
							$.each(reportContent, function(index, v) {
								thisHTML = thisHTML + "<p style='font-size: 14px;'><strong>"+v['createDate']+"</strong>:&nbsp;"+v['description']+"</p>";
							});
							thisHTML = thisHTML + "</div>";
							
						} else if (reportTypeID == 4) {
							thisHTML = thisHTML + 
							"<p style='font-size: 14px;'>Comment: "+reportContent[0]['description']+"</p>";
						} else if (reportTypeID == 5) {
							thisHTML = thisHTML + "<div class='flex-grow-1' style='width: 50%;'>" +
								"<p><strong>Before:</strong></p>";
								
								$.each(reportContent, function(index, v) {
									if (v['tableField'] != null) {
										var field = v['tableField'];
										var from = v['changedFrom'];
										thisHTML = thisHTML + "<p style='font-size: 14px;'>"+field+": '"+(from == "" ? 'BLANK' : from)+"'</p>";
									}
								});
								
							thisHTML = thisHTML + "</div>" +
							"<div class='flex-grow-1' style='width: 50%;'>" +
								"<p><strong>After:</strong></p>";
								
								$.each(reportContent, function(index, v) {
									if (v['tableField'] != null) {
										var field = v['tableField'];
										var to = v['changedTo'];
										thisHTML = thisHTML + "<p style='font-size: 14px;'>"+field+": '"+(to == "" ? 'BLANK' : to)+"'</p>";
									}
								});

							thisHTML = thisHTML + "</div>";
						}
							
							
						thisHTML = thisHTML + "</div>" +
						"<div class='d-flex flex-row report-options' style='margin-top: 5px;'>";
							if ((status == 'Active' && reportedID != <?php echo $userID; ?>) || (status == 'Closed' && isGuilty == 0)) {
								thisHTML = thisHTML + "<button class='btn btn-primary float-left' type='button' "+(status != 'Closed' ? 'onclick=\'judgeReport('+reportID+',false)\'' : 'disabled')+">Innocent</button>";
							}
							thisHTML = thisHTML + "<a class='text-center flex-grow-1 align-self-end' href='javascript:;' onclick='collapse(this,1)' style='font-size: 12px;'>[Expand]</a>";
							if ((status == 'Active' && reportedID != <?php echo $userID; ?>) || (status == 'Closed' && isGuilty == 1)) {
								thisHTML = thisHTML + "<button class='btn btn-primary float-right' type='button' "+(status != 'Closed' ? 'onclick=\'judgeReport('+reportID+',true)\'' : 'disabled')+" >Guilty</button>";
							}
						thisHTML = thisHTML + "</div>" +
					"</div>";
					
					detailsHTML = detailsHTML + thisHTML;
				});
				$("#mod-details-container").html(detailsHTML);
				
				if (detailsHTML != "") {
					$("#mod-details-noneSelected").addClass("hidden");
				}
			});
		}
		
		function collapse(element, parent = 0) {
			if (parent != 0) {
				var parentElement = element.parentElement.parentElement;
			} else {
				var parentElement = element.parentElement;
			}
			var container = parentElement.getElementsByClassName('collapsible-container')[0];
			if (container.classList.contains('hidden') == true) {
				container.classList.remove('hidden');
				element.innerHTML = "[Collapse]";
			} else {
				container.classList.add('hidden');
				element.innerHTML = "[Expand]";
			}
		}
		
		function judgeReport(reportID, isGuilty = false) {
			var objText = "{" +
				"\"reportID\": \"" + reportID + "\"," +
				"\"isGuilty\": \"" + isGuilty + "\"" +
				"}";
			var obj = JSON.parse(objText);
			//Post to PHP page
			$.post("scripts/judgeReport.php", obj, function(data) {
				var response = JSON.parse(data);
				getReportDetails(reportID);
				alert(response);
			});
		}
		
		function updateAccountDetails(userID) {
			var objText = "{" +
				"\"accountID\": \"" + userID + "\"," +
				"\"searchable\": \"" + $("#toggle-searchable").prop('checked') + "\"," +
				"\"comment\": \"" + $("#toggle-comment").prop('checked') + "\"," +
				"\"editContent\": \"" + $("#toggle-editContent").prop('checked') + "\"," +
				"\"editProfile\": \"" + $("#toggle-editProfile").prop('checked') + "\"," +
				"\"inviteUsers\": \"" + $("#toggle-inviteUsers").prop('checked') + "\"," +
				"\"message\": \"" + $("#toggle-message").prop('checked') + "\"," +
				"\"report\": \"" + $("#toggle-report").prop('checked') + "\"," +
				"\"banned\": \"" + $("#toggle-banned").prop('checked') + "\"," +
				
				"\"searchable-duration\": \"" + $("#select-searchable").val() + "\"," +
				"\"comment-duration\": \"" + $("#select-comment").val() + "\"," +
				"\"editContent-duration\": \"" + $("#select-editContent").val() + "\"," +
				"\"editProfile-duration\": \"" + $("#select-editProfile").val() + "\"," +
				"\"inviteUsers-duration\": \"" + $("#select-inviteUsers").val() + "\"," +
				"\"message-duration\": \"" + $("#select-message").val() + "\"," +
				"\"report-duration\": \"" + $("#select-report").val() + "\"," +
				"\"banned-duration\": \"" + $("#select-banned").val() + "\"" +
				"}";
			var obj = JSON.parse(objText);
			//Post to PHP page
			$.post("scripts/updateAccountPriviliges.php", obj, function(data) {
				getAccountDetails(userID);
				getBannedUsers();
				alert(data);
			});
		}
		
		function getCurrentPolicy() {
			var objText = "{}";
			var obj = JSON.parse(objText);
			//Post to PHP page
			$.post("scripts/getCurrentPolicy.php", obj, function(data) {
				var response = JSON.parse(data);
				
				$("#policy-revision").html("Revision <strong>" + response[0]['id'] + "</strong> created on <strong>" + response[0]['createDate'] + "</strong>");
				
				$("#policy-minUser").val(response[0]['minUsername']);
				$("#policy-maxUser").val(response[0]['maxUsername']);
				$("#policy-minPass").val(response[0]['minPassword']);
				$("#policy-maxPass").val(response[0]['maxPassword']);
				$("#policy-userRegex").val(response[0]['usernameAllowed']);
				$("#policy-passRegex").val(response[0]['passwordAllowed']);
				$("#policy-forcePassReset").prop('checked', response[0]['forcePasswordReset']);
				
				$("#policy-canLogin").prop('checked', response[0]['loginEnabled']);
				$("#policy-canSearch").prop('checked', response[0]['searchableEnabled']);
				$("#policy-canComment").prop('checked', response[0]['commentEnabled']);
				$("#policy-canEditContent").prop('checked', response[0]['editContentEnabled']);
				$("#policy-canEditProfile").prop('checked', response[0]['editProfileEnabled']);
				$("#policy-canInvite").prop('checked', response[0]['inviteEnabled']);
				$("#policy-canMessage").prop('checked', response[0]['sendMessagesEnabled']);
				$("#policy-canReport").prop('checked', response[0]['reportingEnabled']);
				
				$("#policy-maxUserCount").val(response[0]['maxUserCount']);
				$("#policy-maxLoginAttepts").val(response[0]['maxFailedLogins']);
				$("#policy-loginTimeout").val(response[0]['failedLoginTimeout']);
				
				$("#policy-img").attr("src", response[0]['backgroundImg']);
				
				//$("#policy-expiry").datetimepicker("setDate", response[0]['expiryDate']);
				
			});
		}
		
		function submitPolicy() {
			var objText = "{" +
				"\"policy-minUser\": \"" + $("#policy-minUser").val() + "\"," +
				"\"policy-maxUser\": \"" + $("#policy-maxUser").val() + "\"," +
				"\"policy-minPass\": \"" + $("#policy-minPass").val() + "\"," +
				"\"policy-maxPass\": \"" + $("#policy-maxPass").val() + "\"," +
				"\"policy-userRegex\": \"" + $("#policy-userRegex").val() + "\"," +
				"\"policy-passRegex\": \"" + $("#policy-passRegex").val() + "\"," +
				"\"policy-forcePassReset\": \"" + $("#policy-forcePassReset").val() + "\"," +
				
				"\"policy-canLogin\": \"" + $("#policy-canLogin").prop('checked') + "\"," +
				"\"policy-canSearch\": \"" + $("#policy-canSearch").prop('checked') + "\"," +
				"\"policy-canComment\": \"" + $("#policy-canComment").prop('checked') + "\"," +
				"\"policy-canEditContent\": \"" + $("#policy-canEditContent").prop('checked') + "\"," +
				"\"policy-canEditProfile\": \"" + $("#policy-canEditProfile").prop('checked') + "\"," +
				"\"policy-canInvite\": \"" + $("#policy-canInvite").prop('checked') + "\"," +
				"\"policy-canMessage\": \"" + $("#policy-canMessage").prop('checked') + "\"," +
				"\"policy-canReport\": \"" + $("#policy-canReport").prop('checked') + "\"," +
				
				"\"policy-maxUserCount\": \"" + $("#policy-maxUserCount").val() + "\"," +
				"\"policy-maxLoginAttepts\": \"" + $("#policy-maxLoginAttepts").val() + "\"," +
				"\"policy-loginTimeout\": \"" + $("#policy-loginTimeout").val() + "\"," +
				"\"policy-expiry\": \"" + $("#policy-expiry").val() + "\"," +
				
				"\"policy-imageFile\": \"" + $("#policy-img").prop('src') + "\"" +
				
				"}";
			var obj = JSON.parse(objText);
			
			//Post to PHP page
			$.post("scripts/addNewPolicy.php", obj, function(data) {
				alert(data);
			});
		}
		
		function rollbackLocation() {
			alert("Rollback Location!");
		}
		
		function getRBDate(versionNum) {
			if (versionNum > 0) {
				var dateList = $("#locRB-rbDateList").val().split(",");
				$("#locRB-rbDate").html(dateList[versionNum - 1]);
			}
		}
		
		function getRBLocData() {
			var loc = $("#locRB-location").val();
			
			var objText = "{" +
				"\"location\": \"" + loc + "\"" +
				"}";
			var obj = JSON.parse(objText);
			
			//Post to PHP page
			$("#locRB-rbToVer").val(0);
			$("#locRB-rbToVer").prop("max",0);
			$("#locRB-rbToVer").prop("min",0);
			$("#locRB-rbDateList").val("");
			
			$("#locRB-createDate").html("");
			$("#locRB-lastUpdate").html("");
			$("#locRB-totalVer").html("");
			$("#locRB-totalCont").html("");
			$("#locRB-rbDate").html("");
			
			
			
			$.post("scripts/getLocationRBData.php", obj, function(data) {
				var response = JSON.parse(data);
				if (response != "" && response != null) {
					$("#locRB-rbToVer").prop("min",1);
					$("#locRB-rbToVer").prop("max",response[0]['versionCount']);
					$("#locRB-rbToVer").val(response[0]['versionCount']);
					$("#locRB-createDate").html(response[0]['createDate']);
					$("#locRB-lastUpdate").html(response[0]['lastUpdate'] + " by: " + response[0]['updatedByUser']);
					$("#locRB-totalVer").html(response[0]['versionCount']);
					$("#locRB-totalCont").html(response[0]['contCount']);
					$("#locRB-rbDate").html("");
					
					var list = "";
					for (i = 0; i < response[0]['versionList'].length; i++) {
						var cDate = response[0]['versionList'][i]['dateChanged'];
						
						if (list.length == 0) {
							list = cDate;
						} else {
							list = list + "," + cDate;
						}
					}
					$("#locRB-rbDateList").val(list);
					var dateList = $("#locRB-rbDateList").val().split(",");
					$("#locRB-rbDate").html(dateList[response[0]['versionCount'] - 1]);
				}
			});
		}
		
		function readURL(input, elementID, changedID) {
			if (input.files && input.files[0]) {
				var reader = new FileReader();
				reader.onload = function (e) {
					document.getElementById(elementID).src = e.target.result
					document.getElementById(changedID).value = "1";
				};
				reader.readAsDataURL(input.files[0]);
			}
		}
	</script>
</head>

<body class="d-flex flex-column" style="height: 100%;">
	<nav class="navbar navbar-light navbar-expand-md navigation-clean-search" style="padding-right: 0px;padding-left: 0px;">
        <div class="container"><a class="navbar-brand" href="home.php"><img id="logo" src="assets/img/logo.png"></a><button data-toggle="collapse" class="navbar-toggler" data-target="#navcol-1"><span class="sr-only">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navcol-1">
                <form class="form-inline mx-auto" autocomplete="off" style="width: 50%;">
                    <div class="form-group" style="width: 100%;">
						<input class="form-control search-field" type="search" id="search-field" name="search" placeholder="Search Locations or People" style="width: 90%;margin: 0px;color: rgb(0,0,0);">
						<button type="button" onclick="searchFor();" style="background-color: Transparent;border: none;margin-left: 5px">
							<i class="fa fa-search" style="color: white;"></i>
						</button>
					</div>
                </form>
				<script src="scripts/autoComplete.js"></script>
					<script>
						<?php
							$phpArray = array();
							foreach ($locationList as $key => $value) {
								array_push($phpArray, $value[0] . " - Location");
							}
							foreach ($accountResult as $key => $value) {
								array_push($phpArray, $value['username'] . " - Account");
							}
							sort($phpArray);
							$jsArray = json_encode($phpArray);
							echo "autocomplete(document.getElementById(\"search-field\"), ".$jsArray.");";
						?>
					</script>
                <ul class="nav navbar-nav">
                    <li class="nav-item dropdown"><a class="dropdown-toggle nav-link" data-toggle="dropdown" aria-expanded="false" href="#"><?php echo $username; ?><img id="profile-picture" src="<?php echo $imgFile; ?>"></a>
                        <div class="dropdown-menu" role="menu">
						<a class="dropdown-item" role="presentation" href="profile.php">Edit Profile</a>
						<?php echo $addModButton; ?>
						<a class="dropdown-item" role="presentation" href="scripts/logout.php">Logout</a></div>
                    </li>
                </ul>
			</div>
        </div>
    </nav>
    <div class="d-flex flex-grow-1" id="page-contents" style="overflow: hidden;">
        <div class="d-flex flex-column flex-grow-0" id="active-report-list" style="background-color: #eee;margin: 5px;padding: 5px;border-radius: 10px;width: 20%;min-width: 250px;">
            <h2 style="text-align: center;">Reports</h2>
            <div class="table-responsive" style="width: 100%;margin-top: 20px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center">Username</th>
                            <th class="text-center"># Active Reports</th>
                            <th class="text-center">Severity</th>
                        </tr>
                    </thead>
                    <tbody id="activeReportList" class="text-center">
						<?php
							$phpArray = array();
							$query = "SELECT * FROM report WHERE status=0";
							$reportList = $dbController->runQuery($query);
							foreach ($reportList as $key => $value) {
								$reportedID = $value['userID'];
								$reportTypeID = $value['reportTypeID'];
								
								// Get Username
								$query = "SELECT username FROM accounts WHERE id=$reportedID";
								$username = $dbController->runQuery($query);
								$username = $username[0]['username'];
								
								// Get Report Severity
								$query = "SELECT severity FROM reporttype WHERE id=$reportTypeID";
								$severity = $dbController->runQuery($query);
								$severity = $severity[0]['severity'];
								
								// Check if Key Exists
								if (array_key_exists($reportedID, $phpArray)) {
									$prevSeverity = $phpArray[$reportedID]['severity'];
									$countReports = $phpArray[$reportedID]['count'];
									$phpArray[$reportedID]['severity'] = $prevSeverity + $severity;
									$phpArray[$reportedID]['count'] = $countReports + 1;
								} else {
									$phpArray[$reportedID]['severity'] = $severity;
									$phpArray[$reportedID]['count'] = 1;
									$phpArray[$reportedID]['username'] = $username;
									$phpArray[$reportedID]['id'] = $reportedID;
								}
							}
							
							// Loop through Array
							foreach ($phpArray as $key => $value) {
								echo "<tr class='clickable-row' onclick='selectReport(this,".$value['id'].")'>";
								echo "<td>".$value['username']."</td>";
								echo "<td>".$value['count']."</td>";
								echo "<td>".$value['severity']."</td>";
								echo "</tr>";
							}
						
						?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex flex-column flex-grow-1" id="page-tabs" style="padding: 5px;overflow: auto;overflow-y: hidden;">
            <div class="d-flex flex-column" style="background-color: #eee;border-radius: 10px;overflow: auto;overflow-y: hidden; height: 100%;">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" role="tab" data-toggle="tab" href="#tab-1"><strong>Details</strong></a></li>
                    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#tab-2"><strong>Account Info</strong></a></li>
                    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#tab-3"><strong>Banned Users</strong></a></li>
                    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#tab-4"><strong>Moderator History</strong></a></li>
                    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#tab-5"><strong>Policies</strong></a></li>
					<li class="nav-item"><a role="tab" data-toggle="tab" class="nav-link" href="#tab-6"><strong>RollBack</strong></a></li>
                </ul>
                <div class="tab-content flex-grow-1" style="margin: 5px;padding: 5px;overflow: auto;">
                    <div id="tab-1" class="reportDetailsContainer tab-pane active" role="tabpanel" id="tab-1" style="overflow-y: auto;">
                        <div id="mod-details-container">
						
						</div>
						<div id="mod-details-noneSelected">
							<h2>No account selected.</h2>
							<p>Please select an account from the Reports dropdown to continue.</p>
						</div>
						
                    </div>
                    <div class="tab-pane" role="tabpanel" id="tab-2">
                        <div id="form-userLookup" class="d-flex flex-row">
							<h1 style="font-size: 25px;margin-right: 5px;margin-bottom: 0px;">Account Details</h1>
							<form class="flex-grow-1 adminPanel" style="position: relative; height: 30px">
								<input id="user-lookup" type="text" placeholder="Search Users..." onkeyup="getAccountDetails(-1, this.value)"  style="margin-left: 5px;padding: 0px;height: 30px;width: 50%;">
								<script>
									<?php
										$phpArray = array();
										foreach ($accountLookup as $key => $value) {
											array_push($phpArray, $value['username']);
										}
										sort($phpArray);
										$jsArray = json_encode($phpArray);
										echo "autocomplete(document.getElementById(\"user-lookup\"), ".$jsArray.");";
									?>
								</script>
							</form>
							<hr>
						</div>
						
						<div id="mod-info-container" class="hidden">
							<div class="d-flex flex-column" style="margin-bottom: 10px;">
								<div class="d-flex flex-column">
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Account Type:&nbsp;</strong></p>
										<p id="user-acctType" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>ID:&nbsp;</strong></p>
										<p id="user-id" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Username:&nbsp;</strong></p>
										<p id="user-username" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Users Invited:&nbsp;</strong></p>
										<p id="user-inviteCount" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Total Changes Made:&nbsp;</strong></p>
										<p id="user-changeCount" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row" style="margin-top: 10px;">
										<p style="font-size: 14px;"><strong>Total Reports:&nbsp;</strong></p>
										<p id="user-totalReports" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Active / Inactive:&nbsp;</strong></p>
										<p id="user-reportsActiveInactive" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row" style="margin-top: 10px;">
										<p style="font-size: 14px;"><strong>Total Severity:&nbsp;</strong></p>
										<p id="user-totalSeverity" style="font-size: 14px;"></p>
									</div>
									<div class="d-flex flex-row">
										<p style="font-size: 14px;"><strong>Active / Inactive:&nbsp;</strong></p>
										<p id="user-severityActiveInactive" style="font-size: 14px;"></p>
									</div>
								</div>
							</div>
							<div style="margin-top: 10px;margin-bottom: 5px;overflow: auto;">
								<h1 style="font-size: 25px;margin-right: 5px;margin-bottom: 0px;">Priviliges</h1>
								<div class="table-responsive" style="width: 50%;">
									<table class="table">
										<tbody>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Searchable:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-searchable" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-searchable" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-searchable" ></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Comment:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-comment" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-comment" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-comment"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Edit Content:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-editContent" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-editContent" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-editContent"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Edit Profile:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-editProfile" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-editProfile" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-editProfile"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Invite Users:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-inviteUsers" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-inviteUsers" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-inviteUsers"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Message:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-message" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-message" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-message"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Can Report:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-report" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-report" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-report"></p></td>
											</tr>
											<tr class="d-flex flex-row">
												<td class="text-right" style="width: 120px;"><strong>Banned:</strong></td>
												<td class="d-flex flex-grow-1 justify-content-center align-items-center" style="width: 70px;"><input type="checkbox" id="toggle-banned" data-toggle="toggle" data-on="Yes" data-off="No" data-onstyle="success" data-offstyle="danger" data-width="50" data-height="20"></td>
												<td class="text-left flex-grow-1 justify-content-end align-items-center"><strong>Disabled For:&nbsp;</strong><select id="select-banned" class="selectBanDuration"><?php foreach ($banDuration as $key => $value) { echo "<option value='".$value['days']."'>".$value['description']."</option>"; }?></select></td>
												<td class="text-right" style="width: 200px;"><p id="disabledUntil-banned"></p></td>
											</tr>
										</tbody>
									</table>
								</div>
								<button id="acctDetailsSaveChanges" class="btn btn-primary float-right" type="button" style="margin-top: 5px;">Save Changes</button>
							</div>
						</div>
						<div id="mod-info-noneSelected" style="margin-top: 20px;">
							<h2>Account not found.</h2>
							<p>The account name specified does not exist. Please check and try again.</p>
						</div>
                    </div>
                    <div class="tab-pane" role="tabpanel" id="tab-3" style="overflow-y: auto;">
                        <div class="table-responsive" style="width: 100%;">
                            <table class="table">
                                <thead class="text-center">
                                    <tr>
                                        <th>Username</th>
                                        <th>Ban Type</th>
                                        <th>Banned On</th>
                                        <th>Banned Until</th>
                                        <th>Days Left</th>
                                        <th>By Moderator</th>
                                    </tr>
                                </thead>
                                <tbody id="bannedUsersTable" >
                                    <tr class="text-center">
                                        <td><a href="#"><strong>SomeUser</strong></a></td>
                                        <td>Can Comment</td>
                                        <td>1/1/2020</td>
                                        <td>3/3/2020</td>
                                        <td>35</td>
                                        <td><a href="#"><strong>SomeModerator</strong></a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" role="tabpanel" id="tab-4" style="overflow-y: auto;">
                        
						<div class="d-flex flex-column report-detail" style="background-color: #ddd;padding: 5px;margin-top: 10px;margin-bottom: 10px;">
                            <div class="d-flex flex-column report-header" style="border-bottom: solid;border-color: #ccc;margin-bottom: 5px;">
                                <div class="d-flex flex-row flex-grow-1"><a href="#"><strong>someModerator</strong></a>
                                    <p><strong>&nbsp;Updated Priviliges for&nbsp;</strong></p><a href="#"><strong>someUser</strong></a>
                                    <p class="text-right float-right flex-grow-1" style="font-size: 12px;">2/2/2020</p>
                                </div>
                            </div>
                            <div class="d-flex flex-row flex-grow-1 report-body" style="margin-bottom: 5px;margin-top: 5px;">
                                <p style="font-size: 14px;">Can Comment: OFF for - 1 Week</p>
                            </div><a class="text-center flex-grow-1 align-self-end" href="#" style="font-size: 12px;width: 100%;">[Collapse]</a>
						</div>
                        <div class="d-flex flex-column report-detail" style="background-color: #ddd;padding: 5px;margin-top: 10px;margin-bottom: 10px;">
                            <div class="d-flex flex-column report-header" style="border-bottom: solid;border-color: #ccc;margin-bottom: 5px;">
                                <div class="d-flex flex-row flex-grow-1"><a href="#"><strong>someModerator</strong></a>
                                    <p><strong>&nbsp;Updated Report Status for Report ID:&nbsp;</strong></p><a href="#"><strong>123113</strong></a>
                                    <p class="text-right float-right flex-grow-1" style="font-size: 12px;">2/2/2020</p>
                                </div>
                            </div>
                            <div class="d-flex flex-row flex-grow-1 report-body" style="margin-bottom: 5px;margin-top: 5px;">
                                <p style="font-size: 14px;">Report Type: Inappropriate PFP<br>Reported User: badUser<br>Status: GUILTY<br>Submitted by: someUser<br>Submitted on: 1/1/2020</p>
                            </div><a class="text-center flex-grow-1 align-self-end" href="#" style="font-size: 12px;width: 100%;">[Collapse]</a>
						</div>
                        <div class="d-flex flex-column report-detail" style="background-color: #ddd;padding: 5px;margin-top: 10px;margin-bottom: 10px;">
                            <div class="d-flex flex-column report-header" style="border-bottom: solid;border-color: #ccc;margin-bottom: 5px;">
                                <div class="d-flex flex-row flex-grow-1"><a href="#"><strong>someModerator</strong></a>
                                    <p><strong>&nbsp;Reverted Changes made on&nbsp;</strong></p><a href="#"><strong>Twisted Falls</strong></a>
                                    <p class="text-right float-right flex-grow-1" style="font-size: 12px;">2/2/2020</p>
                                </div>
                            </div>
                            <div class="d-flex flex-row flex-grow-1 report-body" style="margin-bottom: 5px;margin-top: 5px;">
                                <p style="font-size: 14px;">Editor: someUser<br>Edit Date: 1/1/2020</p>
                            </div><a class="text-center flex-grow-1 align-self-end" href="#" style="font-size: 12px;width: 100%;">[Collapse]</a>
						</div>
                    </div>
                    <div class="tab-pane" role="tabpanel" id="tab-5">
                        <h1 style="font-size: 25px;margin-right: 5px;margin-bottom: 0px;">Policy Details</h1>
                        <p id="policy-revision" style="font-size: 12px;">Revision <strong>1.2</strong> created on <strong>03/28/2020</strong> expiring on <strong>05/05/2020</strong></p>
                        <form>
                            <div id="policy-usernamePassword" style="margin-top: 10px;">
                                <h1 style="font-size: 21px;margin-right: 5px;margin-bottom: 0px;">Username / Password Details</h1>
                                <hr style="margin-top: 0px;">
                                <div class="d-flex">
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Min Username Length</label>
										<input id="policy-minUser" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="3" max="30" step="1">
									</div>
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 160px;">Max Username Length</label>
										<input id="policy-maxUser" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="3" max="30" step="1">
									</div>
                                </div>
                                <div class="d-flex">
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Min Password Length</label>
										<input id="policy-minPass" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="3" max="30" step="1">
									</div>
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 160px;">Max Password Length</label>
										<input id="policy-maxPass" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="3" max="30" step="1">
									</div>
                                </div>
                                <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
									<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Username Regex</label>
									<input id="policy-userRegex" class="form-control flex-shrink-1" type="text">
								</div>
                                <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
									<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Password Regex</label>
									<input id="policy-passRegex" class="form-control flex-shrink-1" type="text">
								</div>
                                <div class="form-check">
									<input class="form-check-input" type="checkbox" id="formCheck-1">
									<label id="policy-forcePassReset" class="form-check-label" for="formCheck-1">Force Password Reset on Policy Date</label>
								</div>
                            </div>
                            <div id="policy-abilities" style="margin-top: 10px;">
                                <h1 style="font-size: 21px;margin-right: 5px;margin-bottom: 0px;">Abilities</h1>
                                <hr style="margin-top: 0px;">
                                <div class="table-responsive" style="width: 500px;">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canLogin" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Can Login</label>
													</div>
                                                </td>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canSearch" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Search Users Enabled</label>
													</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canComment" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Commenting Enabled</label>
													</div>
                                                </td>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canEditContent" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Edit Content Enabled</label>
													</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canEditProfile" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Edit Profile Enabled</label>
													</div>
                                                </td>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canInvite" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Invite Users Enabled</label>
													</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
													<input id="policy-canMessage" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Message Users Enabled</label>
													</div>
                                                </td>
                                                <td>
													<div class="form-check">
													<input id="policy-canReport" class="form-check-input" type="checkbox">
													<label class="form-check-label text-nowrap" for="formCheck-2">Reporting Enabled</label>
													</div>
												</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div id="policy-security" style="margin-top: 10px;">
                                <h1 style="font-size: 21px;margin-right: 5px;margin-bottom: 0px;">Security</h1>
                                <hr style="margin-top: 0px;">
                                <div class="d-flex">
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Max User Count</label>
										<input id="policy-maxUserCount" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="1" max="10000" step="1">
									</div>
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 160px;">Max Failed Logins</label>
										<input id="policy-maxLoginAttepts" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="1" max="1000" step="1">
									</div>
                                </div>
                                <div class="d-flex">
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Login Timeout (mins)</label>
										<input id="policy-loginTimeout" class="form-control" type="number" style="margin-left: 5px;width: 80px;" min="1" max="86400" step="1">
									</div>
                                </div>
                            </div>
                            <div id="policy-other">
                                <h1 style="font-size: 21px;margin-right: 5px;margin-bottom: 0px;">Misc</h1>
                                <hr style="margin-top: 0px;">
                                <div class="d-flex">
                                    <div class="form-group d-flex flex-row flex-grow-1" style="margin-bottom: 5px;">
										<label class="text-nowrap" style="margin-right: 5px;width: 170px;">Policy Expiry Date</label>
										<input id="policy-expiry" class="form-control" type="date" style="width: 180px;">
                                        <p class="align-self-center" style="font-size: 12px;width: 50%;margin-left: 5px;">Once the policy expires, it will roll back to the previous policy. Good for disabling abilities for a short period of time</p>
                                    </div>
                                </div>
                                <div>
                                    <p>Background Image</p>
									<img id="policy-img" style="width: 150px;height: 150px;margin-right: 5px;" src="assets/img/defaultLocation.png">
									<label for="policy-fileToUpload">
										<input type="file" name="policy-fileToUpload" id="policy-fileToUpload" style="display:none;" onchange="readURL(this, 'policy-img', 'policy-FileChanged');"></input>
										<span class="btn btn-primary" type="button" style="margin-left: 5px;">Upload</span>
										<input class="form-control" type="hidden" id="policy-FileChanged" name="policy-FileChanged" value="">
									</label>
								</div>
                            </div>
                        </form>
					<button class="btn btn-primary float-right" onclick="submitPolicy()" type="button">Submit New Policy</button>
					</div>
					<div role="tabpanel" class="tab-pane" id="tab-6">
						<div class="d-flex flex-column">
							<div class="d-flex flex-row">
								<h2 class="d-xl-flex align-items-xl-center">Location Rollback: </h2>
								<form id="form-locRollback" class="d-flex flex-grow-1 adminPanel" style="position: relative">
									<input id="locRB-location" type="text" style="margin-left: 5px;margin-right: 5px;padding: 5px;height: 30px;width: 50%" placeholder="Search Locations..." onkeyup="getRBLocData()" />
									<script>
										<?php
											$phpArray = array();
											foreach ($locationList as $key => $value) {
												array_push($phpArray, $value['description']);
											}
											sort($phpArray);
											$jsArray = json_encode($phpArray);
											echo "autocomplete(document.getElementById(\"locRB-location\"), ".$jsArray.");";
										?>
									</script>
									
									<p class="text-nowrap d-xl-flex align-items-xl-center" style="font-size: 14px;"><strong>to Version: </strong></p>
									<input id= "locRB-rbToVer" type="number" class="form-control" style="height: 30px;width: 70px;margin-left: 5px;" min="0" max="0" value="0" step="1" onchange="getRBDate(this.value)" />
									<input id="locRB-rbDateList" type="hidden" />
								</form>
							</div>
							<div class="d-flex flex-row">
								<p style="font-size: 14px;"><strong>Created On: </strong></p>
								<p id="locRB-createDate" style="font-size: 14px;">1/1/2020</p>
							</div>
							<div class="d-flex flex-row">
								<p style="font-size: 14px;"><strong>Last Updated On:  </strong></p>
								<p id="locRB-lastUpdate" style="font-size: 14px;">4/20/2020 by: SomeUser</p>
							</div>
							<div class="d-flex flex-row">
								<p style="font-size: 14px;"><strong>Total # of Versions: </strong></p>
								<p id="locRB-totalVer" style="font-size: 14px;">150</p>
							</div>
							<div class="d-flex flex-row">
								<p style="font-size: 14px;"><strong>Total # of Contributors: </strong></p>
								<p id="locRB-totalCont" style="font-size: 14px;">16</p>
							</div>
							<div class="d-flex flex-row">
								<p style="font-size: 14px;"><strong>Rollback Date: </strong></p>
								<p id="locRB-rbDate" style="font-size: 14px;">2/20/2020 by: SomeUser</p>
							</div>
							<button class="btn btn-primary align-self-end" type="button" onclick="rollbackLocation()">Rollback Location</button>
						</div>
					</div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/multirange.js"></script>
    <script src="assets/js/Toggle-Checkbox.js"></script>
</body>

</html>