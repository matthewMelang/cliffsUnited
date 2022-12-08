<?php
	date_default_timezone_set('America/New_York');
	session_start();
	if (!isset($_SESSION['nID'])) {
		header("Location: login.php");
	}
	$userID = $_SESSION['nID'];
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	// List of Searchable Accounts
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
	
	// Check if user is mod or admin
	$query = "SELECT * FROM accounts WHERE id='$userID'";
	$result = $dbController->runQueryBasic($query);
	$acctType = $result['accountType'];
	$username = $result['username'];
	$addModButton = "";
	if ($acctType == "Admin" || $acctType == "Moderator") {
		$addModButton = "<a class='dropdown-item' role='presentation' href='moderate.php'>Admin Dashboard</a>";
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
		$(document).ready(function(){
			checkUsername();
			checkPass();
			$('#formTB-username').on("input",checkUsername);
			$("#formTB-passRepeat").on("input", checkPass);
			$("#formTB-pass").on("input", checkPass);
		});

		function checkUsername(){
			var labelParent = $("#availabilityText");
			var checkBad = $("#checkBad");
			var checkGood = $("#checkGood");
			var checkText = $("#usernameCheck")
			var username = $('#formTB-username').val();
			var hidden = $("#formTB-usernameAvaiable");
			var check = true;
			
			if (username == "" || username.length < 3) {
				check = false;
			}
			
			if (check) {
				$.ajax({
					url: 'scripts/validateUsername.php?username='+username,
					type: 'GET',
					dataType: 'JSON',
					async: false,
					success: function(response) {
						var val = parseInt(response);
						if (val > 0) {
							check = false;
						}
					}
				});
			}
			
			if(check){
				checkBad.addClass("hidden");
				checkGood.removeClass("hidden");
				checkText.text("Available");
				labelParent.css("color", "green");
				hidden.val("1");
			} else {
				checkBad.removeClass("hidden");
				checkGood.addClass("hidden");
				checkText.text("Not Available");
				labelParent.css("color", "red");
				hidden.val("0");
			}
		}
		
		function checkPass() {
			var pass = $("#formTB-pass").val();
			var passRepeat = $("#formTB-passRepeat").val();
			var passError = $("#passwordText");
			var hidden = $("#formTB-passCheck");
			var check = true;
			
			if (pass == "" && passRepeat == "") {
				check = false;
			} else {
				if (pass != passRepeat) {
					check = false;
				}
			}
			
			if (check) {
				passError.addClass("hidden");
				hidden.val("1");
			} else {
				passError.removeClass("hidden");
				hidden.val("0");
			}
		}
		
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
		
		function showPage(page) {
			var genDetails = $("#btn-showDetailsPage");
			var security = $("#btn-showSecurityPage");
			var messages = $("#btn-showMessagesPage");
			
			var pageGeneral = $("#page-general");
			var pageSecurity = $("#page-security");
			var pageMessages = $("#page-message-center");
			
			var btn = $("#btn-saveChanges");
			
			// Deselect Other Tabs
			genDetails.removeClass("selected");
			security.removeClass("selected");
			messages.removeClass("selected");
			
			// Select Tab
			page.classList.add("selected");
			
			// Hide other Pages
			pageGeneral.addClass("hidden");
			pageSecurity.addClass("hidden");
			pageMessages.addClass("hidden");
			
			if (page.id == "btn-showDetailsPage") {
				pageGeneral.removeClass("hidden");
				btn.removeClass("hidden");
			} else if (page.id == "btn-showSecurityPage") {
				pageSecurity.removeClass("hidden");
				btn.removeClass("hidden");
			} else if (page.id == "btn-showMessagesPage") {
				retrieveThreads();
				$("#message-noneSelected").removeClass("hidden");
				$("#message-thread").addClass("hidden");
				pageMessages.removeClass("hidden");
				btn.addClass("hidden");
			}
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
		
		function copyToClipboard(element) {
			var $temp = $("<input>");
			$("body").append($temp);
			$temp.val($(element).text()).select();
			document.execCommand("copy");
			$temp.remove();
		}
		
		function saveChanges() {
			var pageGeneral = $("#page-general");
			var pageSecurity = $("#page-security");
			
			if (pageGeneral.hasClass("hidden") == false) {
				if ($("#formTB-usernameAvaiable").val() != 0) {
					var objText = "{" +
						"\"updateType\": \"" + "general" + "\"," +
						"\"formTB-imageFile\": \"" + $("#page-general-img").prop('src') + "\"," +
						"\"formTB-imageFileChanged\": \"" + $("#formTB-profileFileChanged").val() + "\"," +
						"\"formTB-username\": \"" + $("#formTB-username").val() + "\"," +
						"\"formTB-fullName\": \"" + $("#formTB-fullName").val() + "\"," +
						"\"formTB-email\": \"" + $("#formTB-email").val() + "\"," +
						"\"formTB-website\": \"" + $("#formTB-website").val() + "\"," +
						"\"formTB-bio\": \"" + $("#formTB-bio").val() + "\"" +
						"}";
					var obj = JSON.parse(objText);
					
					//Post to PHP page
					$.post("scripts/updateUser.php", obj, function(data) {
						alert(data);
					});
				} else {
					alert("Please change your username.");
				}
				
			} else if (pageSecurity.hasClass("hidden") == false) {
				var objText = "{" +
					"\"updateType\": \"" + "password" + "\"," +
					"\"formTB-pass\": \"" + $("#formTB-pass").val() + "\"," +
					"\"formTB-passRepeat\": \"" + $("#formTB-passRepeat").val() + "\"" +
					"}";
				var obj = JSON.parse(objText);
				if ($("#formTB-pass").val() == $("#formTB-passRepeat").val()) {
					if ($("#formTB-passCheck").val() != 0) {
						//Post to PHP page
						$.post("scripts/updateUser.php", obj, function(data) {
							alert(data);
						});
					}
				} else {
					alert("Passwords dont match.");
				}
			}
		}
		
		function retrieveThreads(threadID = -1) {
			$(document).ready(function() {
				$.ajax({
					url: 'scripts/getThreadData.php',
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						var threadHTML = "";
						$("#message-conversation-list").html("");
						$.each(response, function(index, value) {
							if (value != "") {
								thisHTML = "<li class='list-group-item d-flex flex-row' style='margin: 0px;padding:0px;margin-top: 5px;margin-bottom: 5px;'>" +
									"<button class='d-flex flex-row "+(value["id"] == threadID ? 'selected' : '')+"' onclick='showMessage(this,"+value["id"]+")' style='margin: 0px;padding: 5px;border:none;'>" +
									"<img src='"+value["creatorImg"]+"' style='margin-right: 2px;'>" +
									"<div class='flex-grow-1' style='margin-left: 2px;'>" +
									"<p style='text-align: left;'><strong>"+value["creatorUsername"]+"</strong></p>" +
									"<p style='font-size: 12px;text-align: left;'>"+value["messageCount"]+" Messages</p>" + 
									"</div>" + 
									"<div class='flex-grow-1'>" +
									"<p class='text-right' style='font-size: 12px;'>"+(value["lastMessage"] == null ? '' : value["lastMessage"])+"</p>" +
									"</div>" +
									"</button>" +
									"</li>";
								threadHTML = threadHTML + thisHTML;
							}
							
						});
						$("#message-conversation-list").html(threadHTML);
					},
					error: function(jqXHR, textStatus, errorThrow) {
						alert("ERROR");
					}
				});
			});
		}
		
		function showMessage(element, threadID) {
			if (element != -1) {
				var btnList = $("#message-conversation-list button");
				btnList.each(function() {
					$(this).removeClass("selected");
				});
				
				// Add Selected Tag
				element.classList.add("selected");
				$("#message-noneSelected").addClass("hidden");
				$("#message-thread").removeClass("hidden");
			}
			$("#btn-sendMessage").attr("onclick", "sendMessage("+threadID+")");
			$("#btn-deleteThread").attr("onclick", "deleteThread("+threadID+")");
			$("#btn-reportThread").attr("onclick", "toggleReportForm(true, 'comment', 'Report Message', 'reportMessage("+threadID+")')");
			
			$(document).ready(function() {
				$.ajax({
					url: 'scripts/getMessageData.php?threadID='+threadID,
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						var messageHTML = "";
						$.each(response, function(index, value) {
							thisHTML = "<li class='list-group-item d-flex flex-row' style='margin: 0px;padding: 5px;background-color: rgba(0,0,0,0);border: none;border-bottom: solid;border-width: 1px;border-radius: 0px;padding-top: 10px;padding-bottom: 10px;'>"+
								"<img src='"+value['senderImg']+"' style='margin-right: 5px;'>"+
								"<div class='flex-grow-1' style='margin-left: 5px;'>"+
								"<h6 style='argin-bottom: 0px;margin-right: 0px;'>"+(value["senderID"] == <?php echo $userID; ?> ? 'You' : value['senderUsername'])+"</h6>"+
								"<p style='font-size: 12px;margin: 0px;padding: 0px;'>"+value['description']+"<br></p>"+
								"</div>"+
								"<div class='flex-grow-1'>"+
								"<p class='text-right' style='font-size: 12px;'>"+value['createDate']+"</p>"+
								"</div>"+
								"</li>";
							
							messageHTML = messageHTML + thisHTML;
							
						});
						$("#message-item").html(messageHTML);
						$("#inboxUsername").html("<strong>"+response[0]['creatorUsername']+"</strong>");
						$("#inboxUsername").attr('href', 'viewProfile.php?username='+response[0]['creatorUsername']);
						$("#inboxSubject").html("- " + response[0]['subject']);
					},
					error: function(jqXHR, textStatus, errorThrow) {
						$("#message-item").html("");
					}
				});
			});
		}
		
		function sendMessage(threadID) {
			var msg = $("#yourMessage").val();
			if (msg.replace(/\s/g,'') != "") {
				var objText = "{" +
					"\"threadID\": \"" + threadID + "\"," +
					"\"message\": \"" + msg + "\"" +
					"}";
				var obj = JSON.parse(objText);
				$.post("scripts/sendMessage.php", obj, function(data) {
					showMessage(-1, threadID);
					$("#yourMessage").val("");
				});
			} else {
				alert("Message Cannot be blank.");
			}
		}
		
		function deleteThread(threadID) {
			var objText = "{" +
					"\"threadID\": \"" + threadID + "\"" +
					"}";
			var obj = JSON.parse(objText);
			$.post("scripts/deleteThread.php", obj, function(data) {
				// Deselect All Message Threads
				var btnList = $("#message-conversation-list button");
				btnList.each(function() {
					$(this).removeClass("selected");
				});
				
				// Show no Convo Selected
				$("#message-noneSelected").removeClass("hidden");
				$("#message-thread").addClass("hidden");
				
				retrieveThreads();
			});
		}
		
		function toggleReportForm(open, rType, headerText, submitBtnAction) {
			var form = $("#reportContent");
			var btn = $("#btn-reportSubmit");
			var reportType = $("#reportReason");
			var reportHeader = $("#reportHeader");
			
			if (open == true) {
				form.removeClass("hidden");
				btn.attr("onclick", submitBtnAction);
				reportHeader.html(headerText);
				
				// Get Reasons
				$.ajax({
					url: 'scripts/getReportType.php?rType='+rType,
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						var reportHTML = "";
						$.each(response, function(key, value) {
							var thisHTML = "<option value='"+value['id']+"'>"+value['description']+"</option>";
							reportHTML = reportHTML + thisHTML;
						});
						reportType.html(reportHTML);
					},
					error: function(jqXHR, textStatus, errorThrow) {
					}
				});
				
			} else {
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
		
		function toggleChatForm(open) {
			var form = $("#newChatContent");
			var btn = $("#btn-newChatSubmit");
			$("#chat-to").val("");
			$("#chat-subject").val("");
			
			
			if (open == true) {
				form.removeClass("hidden");
			} else {
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
		
		function reportMessage(threadID) {
			var form = $("#reportContent");
			var btn = $("#btn-reportSubmit");
			var reportType = $("#reportReason");
			
			if (threadID != -1) {
				// Send Report
				var objText = "{" +
					"\"threadID\": \"" + threadID + "\"," +
					"\"reportTypeID\": \"" + reportType.val() + "\"" +
					"}";
				var obj = JSON.parse(objText);
				
				//*Post to PHP page
				$.post("scripts/reportMessage.php", obj, function(data) {
					alert(data);
				});
				//*/
				
				
				// Close Form
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
		
		function startChat() {
			var form = $("#newChatContent");
			var to = $("#chat-to").val();
			var subject = $("#chat-subject").val();
			
			if (to != "" && subject != "") {
				var objText = "{" +
					"\"to\": \"" + to + "\"," +
					"\"subject\": \"" + subject + "\"" +
					"}";
				var obj = JSON.parse(objText);
				
				//*Post to PHP page
				$.post("scripts/newMessage.php", obj, function(data) {
					if (data != "") {
						// Deselect All Convos
						var btnList = $("#message-conversation-list button");
						btnList.each(function() {
							$(this).removeClass("selected");
						});
						
						// Select Thread
						retrieveThreads(data);
						showMessage(-1, data);
						
						$("#inboxUsername").html("<strong>"+to+"</strong>");
						$("#inboxUsername").attr('href', 'viewProfile.php?username='+to);
						$("#inboxSubject").html("- " + subject);
						
						$("#message-noneSelected").addClass("hidden");
						$("#message-thread").removeClass("hidden");
						
						
						// Hide New Covo Screen
						form.addClass("hidden");
					}
				});
				//*/
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
    <div class="d-flex flex-row flex-grow-1" id="page-contents" style="background-color: #eee;height: auto;padding: 10px;margin: 40px;border-radius: 10px;overflow: hidden;">
        <div class="d-flex flex-column align-self-center" id="page-selector" style="background-color: #ddd;margin-right: 10px;width: 200px;height: 70%;padding: 5px;border-radius: 5px;">
            <h2 style="text-align: center;">Edit Profile</h2>
            <ul class="list-group" id="edit-profile-tabs" style="margin-top: 50px;">
                <li class="list-group-item">
					<button id="btn-showDetailsPage" class="btn btn-primary text-left selected" type="button" onclick="showPage(this);">General Details<i class="fa fa-info-circle float-right" style="font-size: 25px;"></i></button>
				</li>
                <li class="list-group-item">
					<button id="btn-showSecurityPage" class="btn btn-primary text-left" type="button" onclick="showPage(this);">Security<i class="fa fa-lock float-right" style="font-size: 25px;"></i></button>
				</li>
				
				<?php
					if ($result['canSendMessage'] == 1) {
						echo "<li class='list-group-item'>";
						echo "<div style='width: 15px;height: 15px;background-color: red;border-radius: 15px;position: absolute;right: 30px;top: 3px;'>";
						echo "<p style='color: rgb(0,0,0);font-size: 10px;text-align: center;'><strong>9</strong></p>";
						echo "</div>";
						echo "<button id='btn-showMessagesPage' class='btn btn-primary text-left' type='button' onclick='showPage(this);'>Messages<i class='fa fa-envelope-o float-right' style='font-size: 25px;'></i></button>";
						echo "</li>";
					}
				?>
            </ul>
            <div class="d-flex flex-column flex-grow-1 justify-content-end">
				<button id="btn-saveChanges" class="btn btn-primary d-flex d-xl-flex align-self-center" type="button" onclick="saveChanges();" style="text-align: center;">Save Changes</button>
			</div>
        </div>
        <div class="d-flex flex-grow-1" id="page-holder" style="background-color: #ddd;margin-left: 10px;padding: 5px;border-radius: 5px;overflow: hidden;">
            <div class="d-flex flex-row flex-grow-1" id="page-general">
                <div class="d-flex flex-column flex-fill" style="width: 50%;margin: 5px;padding: 5px;">
                    <h1>General Details</h1>
                    <div style="width: 100%;height: 100%;">
                        <form class="d-flex flex-column" style="height: 100%;">
                            <div class="form-group d-flex flex-row align-items-center" style="overflow: auto;">
								<img id="page-general-img" src="<?php echo $imgFile; ?>" style="margin-right: 10px;">
								<label for="profile-fileToUpload">
									<input type="file" name="profile-fileToUpload" id="profile-fileToUpload" style="display:none;" onchange="readURL(this, 'page-general-img', 'formTB-profileFileChanged');"></input>
									<span class="btn btn-primary text-nowrap" id="edit-profile-upload-photo" type="button" style="padding: 5px;margin-left: 5px;">Upload Photo</span>
									<input class="form-control" type="hidden" id="formTB-profileFileChanged" name="formTB-profileFileChanged" value="">
								</label>
							</div>
                            <div class="form-group" style="margin-bottom: 5px;">
								<div class="d-flex">
									<label style="margin-bottom: 0px;"><strong>Username</strong></label>
									<label id="availabilityText" class="flex-grow-1" style="margin-bottom: 0px;text-align: right;">
										<i id="checkBad" class="fa fa-close"></i>
										<i id="checkGood" class="fa fa-check"></i>
										<strong id="usernameCheck" >Available</strong>
									</label>
								</div>
								<input class="form-control" type="text" id="formTB-username" name="formTB-username" style="width: 100%;" value="<?php echo $result['username']; ?>">
								<input class="form-control" type="hidden" id="formTB-usernameAvaiable" name="formTB-usernameAvaiable" style="width: 100%;">
							</div>
                            <div class="form-group" style="margin-bottom: 5px;">
								<label style="margin-bottom: 0px;"><strong>Full Name</strong></label>
								<input class="form-control" type="text" id="formTB-fullName" name="formTB-fullName" style="width: 100%;" value="<?php echo $result['name']; ?>">
							</div>
                            <div class="form-group d-flex flex-column" style="margin-bottom: 5px;">
								<label style="margin-bottom: 0px;"><strong>Email Address</strong></label>
                                <div class="d-flex">
									<input class="form-control" type="text" id="formTB-email" name="formTB-email" style="width: 100%;margin-right: 5px;" value="<?php echo $result['email']; ?>">
									<button class="btn btn-primary text-nowrap" type="button" style="padding: 5px;margin-left: 5px;">Confirm</button>
								</div>
                            </div>
                            <div class="form-group" style="margin-bottom: 5px;">
								<label style="margin-bottom: 0px;"><strong>Website</strong></label>
								<input class="form-control" type="text" id="formTB-website" name="formTB-website" style="width: 100%;" value="<?php echo $result['website']; ?>">
							</div>
                            <div class="form-group flex-fill" style="margin-bottom: 5px;">
								<label style="margin-bottom: 0px;"><strong>Bio</strong></label>
								<textarea class="form-control" id="formTB-bio" name="formTB-bio" style="resize:none;"><?php echo $result['bio']; ?></textarea>
							</div>
                        </form>
                    </div>
                </div>
                <div class="d-flex flex-fill justify-content-center align-items-center" style="width: 50%;margin: 5px;padding: 5px;">
                    <div class="d-flex flex-row justify-content-center align-items-center" id="invite-code">
                        <div class="float-left" style="width: 90%;margin-right: 5px;">
                            <p style="font-size: 20px;"><strong>Invite Code:</strong></p>
                            <?php
								if ($result["canInvite"] == "1") {
									echo "<p style='font-size: 12px;'>To prevent mass inviting, your invite code will change every time somebody registers with it.&nbsp;</p>";
									echo "<p id='inviteCodeLink'><strong>www.CliffsUnited.com/register.php?invitecode=".$result["inviteCode"]."</strong></p>";
								} else {
									echo "<p>Inviting has been disabled for this account.</p>";
								}
							?>
                        </div>
						<?php
							if ($result["canInvite"] == "1") {
								echo "<button onclick=\"copyToClipboard('#inviteCodeLink');\" style='background-color: rgba(255,255,255,0);border:none;'><i class='fa fa-share-square-o float-right' style='font-size: 20px;'></i></button>";
							}
						?>
					</div>
                </div>
            </div>
            <div class="d-flex flex-column flex-grow-1 hidden" id="page-security" style="margin: 5px;padding: 5px;">
                <div class="flex-grow-1" id="security-form">
                    <h1>Security</h1>
                    <div style="width: 50%;margin-bottom: 10px;">
                        <form>
                            <div class="form-group" style="margin-bottom: 5px;">
								<label style="margin-bottom: 0px;"><strong>Password</strong></label>
								<input class="form-control" type="password" id="formTB-pass" name="formTB-pass" style="width: 100%;">
							</div>
                            <div class="form-group" style="margin-bottom: 0px;">
								<div class="d-flex">
									<label style="margin-bottom: 0px;"><strong>Repeat Password</strong></label>
									<label id="passwordText" class="flex-grow-1 hidden" style="margin-bottom: 0px;text-align: right;color:red;">
										<i class="fa fa-close"></i>
										<strong>Passwords dont match</strong>
									</label>
								</div>
								<input class="form-control" type="password" id="formTB-passRepeat" name="formTB-passRepeat" style="width: 100%;">
								<input class="form-control" type="hidden" id="formTB-passCheck" name="formTB-passCheck" style="width: 100%;">
							</div>
                            <p style="font-size: 12px;">Password Last Changed on <?php echo $result['lastPassChange']; ?></p>
                        </form>
                    </div>
                </div>
                <div class="flex-grow-1" id="privleges-info" style="margin-top: 10px;">
                    <h1 style="font-size: 25px;">Account&nbsp;<strong>Privileges</strong></h1>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td><strong>Discoverable</strong></td>
                                    <td><?php echo ($result['isSearchable'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Can Comment</strong></td>
                                    <td><?php echo ($result['canComment'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Can Edit Content</strong></td>
                                    <td><?php echo ($result['canEditContent'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Can Edit Profile</strong></td>
                                    <td><?php echo ($result['canEditProfile'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Can Invite Users</strong></td>
                                    <td><?php echo ($result['canInvite'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
								<tr>
                                    <td><strong>Can Send Messages</strong></td>
                                    <td><?php echo ($result['canSendMessage'] == 0 ? 'No' : 'Yes'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-column flex-grow-1 hidden" id="page-message-center" style="margin: 5px;padding: 5px;overflow: hidden;">
                <h1>Messages</h1>
                <div class="d-flex flex-row flex-grow-1" id="message-center-container" style="overflow: hidden;">
                    <div class="d-flex flex-column flex-grow-0" id="inbox-list" style="background-color: #ccc;width: 20%;min-width: 200px;margin-right: 5px;padding: 5px;border-radius: 5px;overflow: hidden;">
                        <h2 class="text-center" style="text-align: center;margin-bottom: 5px;">Inbox</h2>
						<button class="btn btn-primary" type="button" onclick="toggleChatForm(true)" style="margin-top: 5px;"><strong>New Conversation +</strong></button>
                        <ul class="list-group" id="message-conversation-list" style="margin-top: 20px;overflow-y: auto;padding-right: 5px;padding-left: 5px;">
                        </ul>
                    </div>
                    <div class="d-flex flex-column flex-grow-1 hidden" id="message-thread" style="margin-left: 5px;padding: 5px;border-radius: 5px;">
						<div class="d-flex flex-row">
							<a href="#" id="inboxUsername" style="font-size: 20px;color: rgb(0,25,255);"><strong></strong></a>
							<p id="inboxSubject" class="flex-grow-1 align-self-center" style="margin-left: 5px;"></p>
							<div class="dropdown d-inline float-right" style="margin-left: 5px;">
								<button class="btn btn-primary dropdown-toggle float-right" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgba(0,0,0,0);"><i class="fa fa-align-justify"></i></button>
								<div role="menu" class="dropdown-menu dropdown-menu-right">
									<a id="btn-deleteThread" role="presentation" class="dropdown-item" href="javascript:;">Delete Thread</a>
									<a id="btn-reportThread" role="presentation" class="dropdown-item" href="javascript:;">Report</a>
								</div>
							</div>
						</div>
						
                        <div class="flex-grow-1" id="message-replies" style="border-radius: 5px;margin-bottom: 5px;overflow-y: auto;padding-left: 5px;padding-right: 5px;">
                            <ul class="list-group" id="message-item" style="margin-top: 0px;">
                            </ul>
                        </div>
                        <div id="message-reply" style="margin-top: 5px;">
                            <form class="d-flex flex-column">
                                <div class="form-group d-flex flex-grow-1" style="margin-bottom: 5px;">
									<textarea id="yourMessage" class="form-control" style="background-color: #bbb;color: #000;" placeholder="Your Message Here..."></textarea>
								</div>
                                <div style="margin-top: 5px;">
									<button id="btn-sendMessage" class="btn btn-primary float-right" type="button"><strong>Reply</strong></button>
								</div>
                            </form>
                        </div>
                    </div>
					<div id="message-noneSelected">
						<h3>No conversation selected...</h3>
					</div>
                </div>
            </div>
        </div>
    </div>
	
	<!-- CHAT POPUP -->
	<div id="newChatContent" class="hidden">
		<div class="d-flex flex-column shadow-sm" id="newChatForm">
			<div class="d-flex">
				<h2 id="newChatHeader" class="text-center flex-grow-1">New Chat</h2>
				<button id="newChatClose" type="button" onclick="toggleChatForm(false);" style="background-color: rgba(0,0,0,0);outline: none !important;box-shadow: none !important;margin: 0px;padding: 0px;position: absolute;right: 5px;top: 5px;"><i class="fa fa-close align-self-start" style="font-size: 20px;color: rgb(205,55,55);"></i></button>
			</div>
			<form class="flex-fill" style="margin-top: 10px;">
				<div class="form-group d-flex">
					<label class="text-right flex-grow-0 align-self-center" style="width: 40%;margin-right: 5px;">To</label>
					<input class="form-control" type="text" id="chat-to" name="chat-to">
					<script>
						<?php
							$phpArray = array();
							foreach ($accountResult as $key => $value) {
								if ($value['id'] != $userID) {
									array_push($phpArray, $value['username']);
								}
							}
							sort($phpArray);
							$jsArray = json_encode($phpArray);
							echo "autocomplete(document.getElementById(\"chat-to\"), ".$jsArray.");";
						?>
					</script>
				</div>
				<div class="form-group d-flex">
					<label class="text-right flex-grow-0 align-self-center" style="width: 40%;margin-right: 5px;">Subject</label>
					<input class="form-control" type="text" id="chat-subject" name="chat-subject">
				</div>
				</form>
			<button id="btn-newChatSubmit" class="btn btn-primary align-self-center" onclick="startChat()" type="button" style="width: 50%;">Chat</button>
		</div>
	</div>
	<!-- CHAT POPUP -->
	
	<!-- REPORT POPUP -->
	<div id="reportContent" class="hidden">
		<div class="d-flex flex-column shadow-sm" id="reportForm">
			<div class="d-flex">
				<h2 id="reportHeader" class="text-center flex-grow-1">Report</h2>
				<button id="reportClose" type="button" onclick="toggleReportForm(false, '', '', '');" style="background-color: rgba(0,0,0,0);outline: none !important;box-shadow: none !important;margin: 0px;padding: 0px;position: absolute;right: 5px;top: 5px;"><i class="fa fa-close align-self-start" style="font-size: 20px;color: rgb(205,55,55);"></i></button>
			</div>
			<form class="flex-fill" style="margin-top: 10px;">
				<div class="form-group d-flex">
					<label class="text-right flex-grow-0 align-self-center" style="width: 40%;margin-right: 5px;">Reason</label>
					<select id="reportReason" class="form-control">
					</select>
				</div>
				</form>
			<button id="btn-reportSubmit" class="btn btn-primary align-self-center" type="button" style="width: 50%;">Submit</button>
		</div>
	</div>
	<!-- REPORT POPUP -->
	
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/multirange.js"></script>
    <script src="assets/js/Toggle-Checkbox.js"></script>
</body>

</html>