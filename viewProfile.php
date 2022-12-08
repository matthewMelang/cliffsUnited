<?php
	date_default_timezone_set('America/New_York');
	session_start();
	if (!isset($_SESSION['nID'])) {
		header("Location: login.php");
	}
	$userID = $_SESSION['nID'];
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	// Check a Username was specified
	$username = "";
	if (isset($_GET["username"])) {
		$username = $_GET["username"];
	} else {
		header("Location: home.php");
	}
	
	// Current Profile Data
	$query = "SELECT * FROM accounts WHERE username='$username' AND isSearchable=1";
	$profileResults = $dbController->runQueryBasic($query);
	if (!isset($profileResults)) {
		header("Location: home.php");
	}
	$query = "SELECT * FROM comments WHERE userID=".$profileResults["id"]." AND isActive=1";
	$commentResults = $dbController->runQuery($query);
	$query = "SELECT * FROM changelog WHERE userID=".$profileResults["id"]." AND locationID <> 0";
	$changelogResults = $dbController->runQuery($query);
	
	// Update Profile Views
	$views = $profileResults["profileViews"];
	$views = $views + 1;
	$query = "UPDATE accounts SET profileViews=".$views." WHERE id=".$profileResults["id"];
	$dbController->runQueryNoReturn($query);
	
	// List of Searchable Accounts
	$query = "SELECT username FROM accounts WHERE isSearchable=1 AND acctStatus='Active'";
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
	
	// Get Current Profile Image
	$profileImgFile = $defaultFile;
	$query = "SELECT url FROM media WHERE forAccountID=".$profileResults["id"]." && isActive='1'";
	$result = $dbController->runQueryBasic($query);
	if (isset($result)) {
		$urlFile = $result['url'];
		if (isset($urlFile)) {
			if ($urlFile != "") {
				$profileImgFile = $urlFile;
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
	
	<script type="text/javascript">
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
		
		function collapse(element) {
			var parentElement = element.parentElement;
			var container = parentElement.getElementsByClassName('collapsible-container')[0];
			if (container.classList.contains('hidden') == true) {
				container.classList.remove('hidden');
				element.innerHTML = "[Collapse]";
			} else {
				container.classList.add('hidden');
				element.innerHTML = "[Expand]";
			}
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
		
		function reportProfile(profileID) {
			var form = $("#reportContent");
			var btn = $("#btn-reportSubmit");
			var reportType = $("#reportReason");
			
			if (profileID != -1) {
				// Send Report
				var objText = "{" +
					"\"profileID\": \"" + profileID + "\"," +
					"\"reportTypeID\": \"" + reportType.val() + "\"" +
					"}";
				var obj = JSON.parse(objText);
				
				//*Post to PHP page
				$.post("scripts/reportProfile.php", obj, function(data) {
					alert(data);
				});
				//*/
				
				
				// Close Form
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
	</script>
</head>

<body class="d-flex flex-column" style="height: 100%;padding-bottom: 5px;">
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
								array_push($phpArray, $value[0] . " - Account");
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
    <div class="d-flex flex-grow-1 flex-fill" id="page-contents" style="overflow: hidden;">
        <div class="d-flex flex-column flex-grow-0" id="profile-info-bar" style="background-color: #eee;margin: 0px;padding: 5px;width: 20%;position: relative;margin-right: 10px;border-radius: 10px;">
            <div class="dropdown d-inline float-right" style="margin-left: 5px;position: absolute;top: 5px;right: 5px;"><button class="btn btn-primary dropdown-toggle float-right" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgba(0,0,0,0);"><i class="fa fa-align-justify"></i></button>
                <div class="dropdown-menu" role="menu">
				<?php
					if ($profileResults["id"] == $userID) {
						echo "<a class='dropdown-item' role='presentation' href='profile.php'>Edit Profile</a>";
					} else {
						echo "<a class='dropdown-item' role='presentation' href='javascript:;'>Send Message</a>";
						echo "<a class='dropdown-item' role='presentation' onclick=\"toggleReportForm(true,'Profile','Report Profile', 'reportProfile(".$profileResults["id"].")')\" href='javascript:;'>Report</a>";
					}
				?>
				</div>
            </div>
			<img src="<?php echo $profileImgFile; ?>" style="margin: 0 auto;">
            <h2 style="text-align: center;font-size: 25px;"><?php echo $profileResults["username"]; ?></h2>
            <p style="text-align: center;"><strong><?php echo ($profileResults["name"] == "" ? "-" : $profileResults["name"]); ?></strong></p>
            <div class="flex-grow-1" style="background-color: #ddd;margin-top: 10px;margin-bottom: 10px;padding: 5px;margin-right: 5px;margin-left: 5px;">
                <p style="width: 100%;height: 100%;"><?php echo $profileResults["bio"]; ?><br></p>
            </div>
            <div class="d-flex flex-column" style="height: auto;">
                <p style="text-align: center;font-size: 12px;">Member Since: <?php echo $profileResults["createDate"]; ?></p>
				<a class="text-center" href="<?php echo $profileResults["website"]; ?>" style="color: rgb(36,0,255);"><?php echo $profileResults["website"]; ?></a></div>
        </div>
        <div class="d-flex flex-column flex-grow-1" id="profile-contributions" style="background-color: #eee;padding: 5px;width: 50%;margin-left: 10px;border-radius: 10px;">
            <h1 style="margin-bottom:0px">Contributions:</h1>
            <div class="flex-grow-1" id="contribution-container" style="padding: 5px;overflow-y: auto;">
                <?php
					$dict_clPair = array();
					// Convert Changelog to Changelog-groups by User & date Added
					if (isset($changelogResults)) {
						foreach($changelogResults as $key => $value) {
							$query = "SELECT username FROM accounts WHERE id=".$userID;
							$username = $dbController->runQueryBasic($query)['username'];
							$dateChanged = $value['dateChanged'];
							$changedFrom = $value['changedFrom'];
							$changedTo = $value['changedTo'];
							$field = $value['tableField'];
							$locID = $value['locationID'];
							$spotID = $value['takeoffID'];
							if ($spotID != 0) {
								// Get Spot Name
								$query = "SELECT description FROM takeoff WHERE id=".$spotID;
								$spotName = $dbController->runQueryBasic($query)['description'];
							} else {
								$spotName = "";
							}
							$query = "SELECT description FROM location WHERE id=".$locID;
							$locationName = $dbController->runQueryBasic($query)['description'];
							
							$arrKey = $dateChanged;
							$pair = array('changelog',$username, $dateChanged, $changedFrom, $changedTo, $field, $spotID, $spotName, $locationName, $locID);
							
							// Add pair to dictonary
							if (array_key_exists($arrKey, $dict_clPair)) {
								array_push($dict_clPair[$arrKey], $pair);
							} else {
								$dict_clPair[$arrKey] = array($pair);
							}
						}
					}
					
					if (isset($commentResults)) {
						foreach($commentResults as $key => $value) {
							$locID = $value['locationID'];
							$dateChanged = $value['createDate'];
							$query = "SELECT description FROM location WHERE id=".$locID;
							$locationName = $dbController->runQueryBasic($query)['description'];
							
							
							$arrKey = $dateChanged;
							$pair = array('comment', $username, $locationName, $value['description'], $value['createDate']);
							
							$dict_clPair[$arrKey] = array($pair);
						}
					}
					
					$changesCount = 0;
					foreach ($dict_clPair as $key => $value) {
						$valArr = $value[0];
						$pairType = $valArr[0];
						if ($pairType == "changelog") {
							echo "<div class='contribution-item' style='background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;'>";
							echo "<div class='d-flex'>";
							echo "<div class='flex-grow-1'>";
							echo "<p class='float-left'><strong>Edited&nbsp;</strong></p><a href='home.php?locationID=".$valArr[9]."' style='color: rgb(0,25,255);font-style: italic;'><strong>".$valArr[8].":</strong></a></div>";
							echo "<p class='flex-grow-1' style='text-align: right;font-size: 12px;'>".$valArr[2]."</p>";
							echo "</div>";
							if(count($value) > 1) {
								echo "<div class='collapsible-container hidden'>";
							}
							foreach ($value as $k => $v) {
								echo "<p>Changed Field <strong>".$v[5]." </strong>from: <em>'".$v[3]."'</em> to <em>'".$v[4]."'</em><br></p>";
								$changesCount++;
							}
							if(count($value) > 1) {
								echo "</div>";
								echo "<a class='float-right' href='javascript:;' onclick='collapse(this);' style='font-size: 12px;color: rgb(0,10,255);text-align: center;width: 100%;'>[Expand]</a>";
							}
							echo "</div>";
						}
						if ($pairType == "comment") {
							echo "<div class='contribution-item' style='background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;'>";
							echo "<div class='d-flex'>";
							echo "<div class='flex-grow-1'>";
							echo "<p class='float-left'><strong>Commented on&nbsp;</strong></p><a href='#' style='color: rgb(0,25,255);font-style: italic;'><strong>".$valArr[2].":</strong></a></div>";
							echo "<p class='flex-grow-1' style='text-align: right;font-size: 12px;'>".$valArr[4]."</p>";
							echo "</div>";
							echo "<p>".$valArr[3]."<br></p>";
							echo "</div>";
							$changesCount++;
						}
					}
				?>
			</div>
        </div>
    </div>
	
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