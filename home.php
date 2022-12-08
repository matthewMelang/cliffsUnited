<?php
	date_default_timezone_set('America/New_York');
	session_start();
	if (!isset($_SESSION['nID'])) {
		header("Location: login.php");
	}
	$userID = $_SESSION['nID'];
	require("scripts/DBController.php");
	$dbController = new DBController();
	
	$query = "SELECT username FROM accounts WHERE isSearchable=1 AND acctStatus='Active'";
	$accountResult = $dbController->runQuery($query);
	
	$query = "SELECT description FROM location";
	$locationList = $dbController->runQuery($query);
	
	$query = "SELECT id, description FROM locationtype";
	$locationTypeList = $dbController->runQuery($query);
	
	$query = "SELECT id, description FROM reporttype";
	$reportTypeList = $dbController->runQuery($query);
	
	$query = "SELECT id, description FROM spottype";
	$spotTypeList = $dbController->runQuery($query);
	
	// Check a location was specified
	$locationID = "";
	if (isset($_GET["locationID"])) {
		$locationID = $_GET["locationID"];
	} else {
		$locationID = 1;
	}
	
	// Get Location Changes
	$query = "SELECT * FROM changelog WHERE locationID=".$locationID." ORDER BY dateChanged DESC";
	$changelogList = $dbController->runQuery($query);
	
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
	<link rel="stylesheet" type="text/css" href="assets/slick/slick.css"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/graphhopper-js-api-client/dist/graphhopper-client.js"></script>
	
	<script type="text/javascript">
		var options = {
		  enableHighAccuracy: true,
		  timeout: 5000,
		  maximumAge: 0
		};
		
		var userLat = -1;
		var userLng = -1;
		
		
		$(document).ready(function() {
			navigator.geolocation.getCurrentPosition(success, error, options);
			
			var urlParams = new URLSearchParams(window.location.search);
			var locID = urlParams.get('locationID');
			if (locID != null) {
				retrieveLocationData(locID);
				$.ajax({
					url: 'scripts/getLocationData.php?locationID='+locID,
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						if (checkMarkerExists(response[0].latitude, response[0].longitude) == false) {
							var newMarker = addMarker(response[0].latitude, response[0].longitude, "red", response[0].id);
							newMarker.icon = pinSymbol('yellow');
						} else {
							var marker = getMarkerByLocation(response[0].latitude, response[0].longitude);
							marker.icon = pinSymbol('yellow');
						}
					},
					error: function(jqXHR, textStatus, errorThrow) {
						//alert("Failed");
					}
				});
			}
		});
		
		function success(pos) {
		  var crd = pos.coords;
		  userLat = crd.latitude;
		  userLng = crd.longitude;
		  createDonut(userLat, userLng, 0, 100);
		}

		function error(err) {
		  userLat = -1;
		  userLng = -1;
		}
		
		function distanceBetween(lat1, lon1, lat2, lon2, unit) {
			if ((lat1 == lat2) && (lon1 == lon2)) {
				return 0;
			}
			else {
				var radlat1 = Math.PI * lat1/180;
				var radlat2 = Math.PI * lat2/180;
				var theta = lon1-lon2;
				var radtheta = Math.PI * theta/180;
				var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
				if (dist > 1) {
					dist = 1;
				}
				dist = Math.acos(dist);
				dist = dist * 180/Math.PI;
				dist = dist * 60 * 1.1515;
				if (unit=="K") { dist = dist * 1.609344 }
				if (unit=="N") { dist = dist * 0.8684 }
				return dist;
			}
		}
		
		function distanceMatrix(lat1, lon1, lat2, lon2) {
			var ghRouting = new GraphHopper.Routing({
				key: "f04e54f6-4310-4bfc-9c9f-276461d3180b",
				vehicle: "car",
				elevation: false
			});

			ghRouting.addPoint(new GHInput(lat1, lon1));
			ghRouting.addPoint(new GHInput(lat2, lon2));

			ghRouting.doRequest()
			.then(function(json) {
				// Add your own result handling here
				var routeDistance = (json["paths"][0]["distance"] * 0.00062137).toFixed(2);
				var routeTime = msToHMS(json["paths"][0]["time"]);
				
				
				$("#disp-drivingDist").html("<strong>Driving Distance:</strong> " + routeDistance + "mi");
				$("#disp-drivingTime").html("<strong>Driving Time:</strong> " + routeTime);
				
			})
			.catch(function(err) {
				console.error(err.message);
			});
		}
		
		function msToHMS( ms ) {
			// 1- Convert to seconds:
			var seconds = ms / 1000;
			// 2- Extract hours:
			var hours = parseInt( seconds / 3600 ); // 3,600 seconds in 1 hour
			seconds = seconds % 3600; // seconds remaining after extracting hours
			// 3- Extract minutes:
			var minutes = parseInt( seconds / 60 ); // 60 seconds in 1 minute
			// 4- Keep only seconds not extracted to minutes:
			seconds = (seconds % 60).toFixed(0);
			return  hours+":"+minutes+":"+seconds;
		}

		function deg2rad(deg) {
		  return deg * (Math.PI/180)
		}
		
		window.onload = function() {
			
			// Pages to Cycle
			var viewPage = document.getElementById("view-current-location");
			var addeditPage = document.getElementById("add-edit-location");
			var changelogPage = document.getElementById("location-changelog");
			
			// Links to Change Page
			var lel = document.getElementById("link-edit-location");
			var lal = document.getElementById("link-add-location");
			var lvc = document.getElementById("link-view-changelog");
			var addEditBack = document.getElementById("link-addedit-back");
			var changelogBack = document.getElementById("link-changelog-back");
			
			//Elements
			var addEditHeader = document.getElementById("addedit-header");
			
			if (lel !== null) {
				lel.onclick = function() {
					setEditForm(getCurrentSelectedMarker());
					viewPage.classList.add('hidden');
					addeditPage.classList.remove('hidden');
					changelogPage.classList.add('hidden');
					addEditHeader.innerHTML = "Edit Location";
					return false;
				}
			}
			
			if (lal !== null) {
				lal.onclick = function() {
					resetAddEditForm();
					viewPage.classList.add('hidden');
					addeditPage.classList.remove('hidden');
					changelogPage.classList.add('hidden');
					addEditHeader.innerHTML = "Add Location";
					return false;
				}
			}
			
			if (lvc !== null) {
				lvc.onclick = function() {
					viewPage.classList.add('hidden');
					addeditPage.classList.add('hidden');
					changelogPage.classList.remove('hidden');
					return false;
				}
			}
			
			addEditBack.onclick = function() {
				viewPage.classList.remove('hidden');
				addeditPage.classList.add('hidden');
				changelogPage.classList.add('hidden');
				return false;
			}
			
			changelogBack.onclick = function() {
				viewPage.classList.remove('hidden');
				addeditPage.classList.add('hidden');
				changelogPage.classList.add('hidden');
				return false;
			}
		}
		
		function deselectLocation() {
			$("#no-location-selected").removeClass("hidden");
			$("#view-current-location").addClass("hidden");
			$("#add-edit-location").addClass("hidden");
			$("#location-changelog").addClass("hidden");
		}
		
		function postComment(locationID) {
			var comment = $.trim($("#formComment").val());
			if (comment != "") {
				$(document).ready(function() {
					$.ajax({
						url: 'scripts/postComment.php?locationID='+locationID+"&comment="+comment,
						type: 'GET',
						dataType: 'JSON',
						success: function(response) {
							var commentHTML = "";
							$.each(response, function(index, value) {
								thisHTML = "<div class='comment-box' style='overflow: auto;margin-bottom: 5px'><img class='float-left' src='"+value['image']+"' style='margin-right: 5px;'>" +
									"<div class='d-flex'>"+
									"<p class='float-left post-date'><strong>"+value['username']+"</strong> commented on "+value['createDate']+"</p>"+
									"</div>"+
									"<p class='comment-info'>"+value['description']+"</p>"+
									"</div>";
								commentHTML = commentHTML + thisHTML;
							});
							if (commentHTML == "") {
								commentHTML = "<div>" + 
									"<p>No comments yet. Be the first to say something!</p>" +
									"</div>";
							}
							$("#disp-comments").html(commentHTML);
						},
						error: function(jqXHR, textStatus, errorThrow) {
						}
					});
				});
			}
			$("#formComment").val("");
			$("#formComment").text("");
			$("#formComment").html("");
		}
	
		function retrieveLocationData(id) {
			$("#view-current-location").removeClass("hidden");
			$("#no-location-selected").addClass("hidden");
			$(document).ready(function() {
				$.ajax({
					url: 'scripts/getLocationData.php?locationID='+id+"&updateViews=1",
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						
						var locLat = response[0]["latitude"];
						var locLng = response[0]["longitude"];
						
						var exactDist = -1;
						var matrix = -1;
						
						$("#disp-drivingDist").html("");
						$("#disp-drivingTime").html("");
						
						if (userLat != -1 && userLng != -1) {
							exactDist = distanceBetween(userLat, userLng, locLat, locLng);
							//distanceMatrix(userLat, userLng, locLat, locLng);
						}
						
						var spots = response["spotList"];
						var comments = response["commentList"];
						var changelog = response["changelog"];
						var totalHeightRange = "";
						var totalHMin = null;
						var totalHMax = null;
						
						var totalDepthRange = "";
						var totalDMin = null;
						var totalDMax = null;
						
						var totalGapRange = "";
						var totalGMin = null;
						var totalGMax = null;
						
						var spotHTML = "";
						var spotCounter = 0;
						$.each(spots, function(index, value) {
							
							if (value['description'] != null) {
								var spotID = value[0];
								var height = parseInt(value['heightFt']);
								var heightMax = parseInt(value['heightFtMax']);
								
								if ((height < totalHMin || totalHMin == null) && height != null) {
									totalHMin = height;
								}
								if ((heightMax < totalHMin || totalHMin == null) && heightMax != 0 && heightMax != null) {
									totalHMin = heightMax;
								}
								if ((height > totalHMax || totalHMax == null) && height != null) {
									totalHMax = height;
								}
								if ((heightMax > totalHMax || totalHMax == null) && heightMax != 0 & heightMax != null) {
									totalHMax = heightMax;
								}
								var heightRange = height + "ft";
								if (heightMax != "" && heightMax != 0) {
									heightRange = heightRange + " - " + heightMax + "ft";
								}
								if (height == "" || height == 0 || height == null) {
									heightRange = "Unknown";
								}
								
								var depth = (value['poolDepth'] != null ? parseInt(value['poolDepth']) : null);
								var depthMax = (value['poolDepthMax'] != null ? parseInt(value['poolDepthMax']) : null);
								if ((depth < totalDMin || totalDMin == null) && depth != null) {
									totalDMin = depth;
								}
								if ((depthMax < totalDMin || totalDMin == null) && depthMax != 0 && depthMax != null) {
									totalDMin = depthMax;
								}
								if ((depth > totalDMax || totalDMax == null) && depth != null) {
									totalDMax = depth;
								}
								if ((depthMax > totalDMax || totalDMax == null) && depthMax != 0 && depthMax != null) {
									totalDMax = depthMax;
								}
								var depthRange = depth + "ft";
								if (depthMax != "" && depthMax != 0) {
									depthRange = depthRange + " - " + depthMax + "ft";
								}
								if (depth == "" || depth == 0 || depth == null) {
									depthRange = "Unknown";
								}
								
								var gap = (value['gapFt'] != null ? parseInt(value['gapFt']) : null);
								var gapMax = (value['gapFtMax'] != null ? parseInt(value['gapFtMax']) : null);
								if ((gap < totalGMin || totalGMin == null) && gap != null) {
									totalGMin = gap;
								}
								if ((gapMax < totalGMin || totalGMin == null) && gapMax != 0 && depthMax != null) {
									totalGMin = gapMax;
								}
								if ((gap > totalGMax || totalGMax == null)  && gap != null) {
									totalGMax = gap;
								}
								if ((gapMax > totalGMax || totalGMax == null) && gapMax != 0 && depthMax != null) {
									totalGMax = gapMax;
								}
								var gapRange = gap + "ft";
								if (gapMax != "" && gapMax != 0) {
									gapRange = gapRange + " - " + gapMax + "ft";
								}
								if (gap == "" || gap == 0 || gap == null) {
									gapRange = "Unknown";
								}
								
								thisHTML = "<div class='spot-container spot-selected' style='padding: 5px;margin: 5px;'>" + 
									"<h3>"+value['description']+"</h3>" +
									"<div class='d-flex flex-row'>" +
									"<img class='float-left spot-image' src='"+value['image']+"' />" +
									"<div class='float-left spot-data' style='padding-right: 5px;padding-left: 5px;'>" +
									"<p><strong>Type: </strong>"+value['spotType']+"</p>" + 
									"<p><strong>Height: </strong>"+heightRange+"</p>" + 
									"<p><strong>Depth: </strong>"+depthRange+"</p>" + 
									"<p><strong>Gap: </strong>"+gapRange+"</p>" + 
									"<p><strong>Runnable: </strong>"+(value['isRunnable'] == 1 ? 'Yes' : 'No')+"</p>" + 
									"</div>" + 
									"</div>" +
									"</div>";
								
								if (value['description'] != null) {
									spotHTML = spotHTML + thisHTML;
									spotCounter = spotCounter + 1;
								}
							}
						});
						if (spotHTML == "") {
							spotHTML = "<div class='d-flex justify-content-center align-items-center' style='height: 50px;'>"+ 
								"<h2 style='margin: 0px;'>No Spots Added Yet.</h2>" + 
								"</div>";
						}
						$("#disp-spots").html(spotHTML);
						setSlick();
						
						
						var commentHTML = "";
						$.each(comments, function(index, value) {
							if (value['description'] != null) {
								thisHTML = "<div class='comment-box' style='overflow: auto;margin-bottom: 5px'><img class='float-left' src='"+value['image']+"' style='margin-right: 5px;'>" +
									"<div class='d-flex'>"+
									"<p class='float-left post-date'><strong>"+value['username']+"</strong> commented on "+value['createDate']+"</p>"+
									"<a class='d-xl-flex flex-grow-1 justify-content-end align-items-center' href='javascript:;' onclick='"+(value['userID'] == <?php echo $userID; ?> ? "deleteComment("+value['id']+","+id+")" : 'toggleReportForm(true,"Comment","Report Comment","reportComment('+value['id']+')")')+"' style='color: rgb(51,0,255);font-size: 10px;margin-left: 5px;'>"+(value['userID'] == <?php echo $userID; ?> ? '[Delete]' : '[Report]')+"</a>"+
									"</div>"+
									"<p class='comment-info'>"+value['description']+"</p>"+
									"</div>";
								commentHTML = commentHTML + thisHTML;
							}
						});
						if (commentHTML == "") {
							commentHTML = "<div>" + 
								"<p>No comments yet. Be the first to say something!</p>" +
								"</div>";
						}
						$("#disp-comments").html(commentHTML);
						
						
						var changelogHTML = "";
						var counter = 0;
						$.each(changelog, function(index, value) {
							var spotID = value[0][4];
							var spotName = value[0][5];
							var uID = value[0][6];
							var clID = value[0][7];
							
							thisHTML = "<div class='changelog-card' style='width: 100%;background-color: #ddd;padding: 5px;margin-top: 5px;margin-bottom: 5px;'>" +
								"<p><a href='viewProfile.php?username=" + value[0][0] + "' style='color: rgb(36,0,255);'><strong>" + value[0][0] + "</strong></a> " + ( spotID == 0 ? 'Updated the Location' : 'Updated Spot <strong>' + spotName + "</strong>") + " on " + value[0][1];
							if(uID != <?php echo $userID; ?>) {
								thisHTML = thisHTML + "<a class='float-right' href='javascript:;' onclick=\"toggleReportForm(true,'Location','Report User','reportChangelog("+uID+","+clID+")')\" style='font-size: 12px;color: rgb(0,10,255);'>[Report]</a>";
							}
							thisHTML = thisHTML + "</p>" +
								"<div class='collapsible-container hidden'>";
							$.each(value, function(index, value) {
								thisHTML = thisHTML + "<p><strong>" + value[3] + "</strong>: " + value[2] + "</p>";
								counter = counter + 1;
							});
							thisHTML = thisHTML + "</div><a class='float-right' href='javascript:;' onclick='collapse(this);' style='font-size: 12px;color: rgb(0,10,255);text-align: center;width: 100%;'>[Expand]</a></div>";
								
							changelogHTML = changelogHTML + thisHTML;
							
						});
						$("#changelog-container").html(changelogHTML);
						$("#changelog-count").html(counter + " Changes Made")
						
						$("#location-image").attr("src", response[1]);
						
						$("#frm-postComment-btn").attr("onclick", "postComment(" + response[0].id + ")");
						
						$("#disp-description").text(response[0].description);
						$("#disp-lastUpdate").html("<strong>Last Updated on:</strong> " + response[0].lastUpdate + " <strong>by:</strong> " + response[0].username);
						$("#disp-parkingDesc").html("");
						$("#disp-otherInfo").html("");
						(response[0].otherInfo == "" ? '' : $("#disp-otherInfo").html("<strong>Other Info:</strong> " + response[0].otherInfo));
						(response[0].parkingDescription == "" || response[0].parkingDescription == null ? '' : $("#disp-parkingDesc").html("<strong>Parking:</strong> " + response[0].parkingDescription));
						
						var address = (response[0].address != null ? response[0].address : '') + " " + (response[0].city != null ? response[0].city : '') + ", " + (response[0].state != null ? response[0].state : '') + " " + (response[0].zip != null ? response[0].zip : '') + ", " + (response[0].country != null ? response[0].country : '');
						if (address == " ,  , ") {
							address = "Unknown";
						}
						$("#disp-address").html("<strong>Address:</strong> " + address);
						
						var coords = response[0].latitude + ", " + response[0].longitude;
						$("#disp-coordinates").html("<strong>Coordinates:</strong> " + coords);
						if (exactDist > -1) {
							$("#disp-distance").html("<strong>Exact Distance:</strong> " + exactDist.toFixed(2) + "mi");
						}
						$("#disp-numSpots").text(spotCounter);
						
						if (totalHMin == totalHMax) {
							if (totalHMin != null) {
								totalHeightRange = totalHMin + "ft";
							}
						} else {
							totalHeightRange = totalHMin + "ft - " + totalHMax + "ft";
						}
						if (totalDMin == totalDMax) {
							if (totalDMin != null) {
								totalDepthRange = totalDMin + "ft";
							}
						} else {
							totalDepthRange = totalDMin + "ft - " + totalDMax + "ft";
						}
						if (totalGMin == totalGMax) {
							if (totalGMin != null) {
								totalGapRange = totalGMin + "ft";
							}
						} else {
							totalGapRange = totalGMin + "ft - " + totalGMax + "ft";
						}
						
						
						$("#disp-heightRange").text((totalHeightRange != "" ? totalHeightRange : 'Unknown'));
						$("#disp-depthRange").text((totalDepthRange != "" ? totalDepthRange : 'Unknown'));
						$("#disp-gapRange").text((totalGapRange != "" ? totalGapRange : 'Unknown'));
						
						$("#disp-public").text((response[0].isPublic == 0 ? 'No' : 'Yes'));
						$("#disp-paid").text((response[0].isPaid == 0 ? 'No' : 'Yes'));
						$("#disp-open").text((response[0].isOpen == 0 ? 'No' : 'Yes'));
						$("#disp-parking").text((response[0].isParking == 0 ? 'No' : 'Yes'));
						
						$("#disp-hikeMiles").text((response[0].hikeMiles == "" ? 'Unknown' : response[0].hikeMiles));
						$("#disp-hikeDiff").text((response[0].hikeDifficulty == "" ? 'Unknown' : response[0].hikeDifficulty));
						$("#formTB-spotLocID").val(id);
						retreiveSpotData(-1);
						
					},
					error: function(jqXHR, textStatus, errorThrow) {
						
					}
				});
			});
		}
		
		function deleteComment(cID,lID) {
			$(document).ready(function() {
				$.ajax({
					url: 'scripts/deleteComment.php?commentID='+cID,
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						retrieveLocationData(lID)
						if (response != "") {
							alert(response);
						}
					},
					error: function(xhr, status, error) {
						
					}
				});
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
		
		function reportComment(commentID) {
			var form = $("#reportContent");
			var btn = $("#btn-reportSubmit");
			var reportType = $("#reportReason");
			
			if (commentID != -1) {
				// Send Report
				var objText = "{" +
					"\"commentID\": \"" + commentID + "\"," +
					"\"reportTypeID\": \"" + reportType.val() + "\"" +
					"}";
				var obj = JSON.parse(objText);
				
				//*Post to PHP page
				$.post("scripts/reportComment.php", obj, function(data) {
					alert(data);
				});
				//*/
				
				
				// Close Form
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
		
		function reportChangelog(uID, clID) {
			var form = $("#reportContent");
			var btn = $("#btn-reportSubmit");
			var reportType = $("#reportReason");
			
			if (uID != -1 && clID != -1) {
				// Send Report
				var objText = "{" +
					"\"reportedID\": \"" + uID + "\"," +
					"\"changelogID\": \"" + clID + "\"," +
					"\"reportTypeID\": \"" + reportType.val() + "\"" +
					"}";
				var obj = JSON.parse(objText);
				
				//*Post to PHP page
				$.post("scripts/reportChangelog.php", obj, function(data) {
					alert(data);
				});
				//*/
				
				// Close Form
				form.addClass("hidden");
				btn.attr("onclick", "");
			}
		}
	
		function retreiveSpotData(id) {
			
			if (id != -1) {
				$(document).ready(function() {
					$.ajax({
						url: 'scripts/getSpotData.php?spotID='+id,
						type: 'GET',
						dataType: 'JSON',
						success: function(response) {
							
							var len = response.length;
							var description = response[0].description;
							var spotTypeID = response[0].spotTypeID;
							var locationID = response[0].locationID;
							var heightFt = response[0].heightFt;
							var heightFtMax = response[0].heightFtMax;
							if (heightFtMax == 0) {
								heightFtMax = "";
							} else {
								heightFtMax = "-" + heightFtMax;
							}
							var poolDepth = response[0].poolDepth;
							var poolDepthMax = response[0].poolDepthMax;
							if (poolDepthMax == 0) {
								poolDepthMax = "";
							} else {
								poolDepthMax = "-" + poolDepthMax;
							}
							var gapFt = response[0].gapFt;
							var gapFtMax = response[0].gapFtMax;
							if (gapFtMax == 0) {
								gapFtMax = "";
							} else {
								gapFtMax = "-" + gapFtMax;
							}
							var run = response[0].isRunnable;
							
							$("#edit-spot-img").attr("src", response[1]);
							$("#formTB-spotName").val(description);
							$("#formTB-spotID").val(id);
							$("#formTB-spotLocID").val(locationID);
							$("#formTB-spotType").val(spotTypeID);
							
							var heightStr = (heightFt == null ? '' : heightFt + heightFtMax);
							var depthStr = (poolDepth == null ? '' : poolDepth + poolDepthMax);
							var gapStr = (gapFt == null ? '' : gapFt + gapFtMax);
							
							$("#formTB-spotHeight").val(heightStr);
							$("#formTB-spotDepth").val(depthStr);
							$("#formTB-spotGap").val(gapStr);
							$("#formCB-run").prop("checked", (run == 1 ? true : false));
						},
						error: function(jqXHR, textStatus, errorThrow) {
						}
					});
				});
			} else {
				$("#edit-spot-img").attr("src", "images/Media/defaultLocation.png");
				$("#formTB-spotFileChanged").val("");
				$("#spot-fileToUpload").val("");
				$("#formTB-spotName").val("");
				$("#formTB-spotID").val("");
				$("#formTB-spotType").val(1);
				$("#formTB-spotHeight").val("");
				$("#formTB-spotDepth").val("");
				$("#formTB-spotGap").val("");
				$("#formCB-run").prop("checked", false);
			}
		}
		
		function findLocations() {
			var start = performance.now();
			deselectLocation();
			var rangeHeight = $("#rangeHeight");
			var minHeight = rangeHeight.val().split(";")[0];
			var maxHeight = rangeHeight.val().split(";")[1];
			
			var rangeDepth = $("#rangeDepth");
			var minDepth = rangeDepth.val().split(";")[0];
			var maxDepth = rangeDepth.val().split(";")[1];
			
			var rangeMiles = $("#rangeMiles");
			var minMiles = rangeMiles.val().split(";")[0];
			var maxMiles = rangeMiles.val().split(";")[1];
			
			var minMaxText = '{"minHeight":'+ minHeight + ', "maxHeight":'+ maxHeight + ', "minDepth":'+ minDepth + ', "maxDepth":'+ maxDepth + ', "minMiles":'+ minMiles + ', "maxMiles":'+ maxMiles + '}';
			
			var showIncomplete = $("#checkbox-showAll").prop('checked');
			var stateList = $("#stateList").val();
			
			var locType = $("#dropdownLocType :input");
			var spotType = $("#dropdownSpotType :input");
			var logistic = $("#dropdownLogistic :input");
			
			var locTypeText = "{";
			locType.each(function() {
				$elementID = $(this).attr("id").split("-")[1];
				$isChecked = $(this)[0].checked;
				
				if ($isChecked) {
					if (locTypeText.length == 1) {
						locTypeText = locTypeText + "\"" + $elementID + "\":true";
					} else {
						locTypeText = locTypeText + ", \"" + $elementID + "\":true";
					}
				}
			});
			locTypeText = locTypeText + "}";
			
			var spotTypeText = "{";
			spotType.each(function() {
				$elementID = $(this).attr("id").split("-")[1];
				$isChecked = $(this)[0].checked;
				
				if ($isChecked) {
					if (spotTypeText.length == 1) {
						spotTypeText = spotTypeText + "\"" + $elementID + "\":true";
					} else {
						spotTypeText = spotTypeText + ", \"" + $elementID + "\":true";
					}
				}
			});
			spotTypeText = spotTypeText + "}";
			
			var logisticText = "{";
			logistic.each(function() {
				$elementField = $(this).attr("id").split("-")[1];
				$isChecked = $(this)[0].checked;
				if ($isChecked) {
					if (logisticText.length == 1) {
						logisticText = logisticText + "\"" + $elementField + "\":true";
					} else {
						logisticText = logisticText + ", \"" + $elementField + "\":true";
					}
				}
			});
			logisticText = logisticText+ "}";
			
			// Combine Ojbects
			var objText = "{ \"locType\": [" + locTypeText + "], \"spotType\": [" + spotTypeText + "], \"logistic\": [" + logisticText + "], \"minMax\": [" + minMaxText + "], \"showIncomplete\": " + showIncomplete + ", \"stateList\": \"" + stateList + "\"}";
			var obj = JSON.parse(objText);
			
			// Delete the Markers
			deleteMarkers();
			
			//*Post to PHP page
			$.post("scripts/findLocations.php", obj,
			function(data) {
				var obj = $.parseJSON(data);
				var count = 0;
				$.each(obj, function(i, item) {
					var latitude = item.latitude;
					var longitude = item.longitude;
					
					var exactDist = 0;
					if (maxMiles != 0) {
						exactDist = distanceBetween(userLat, userLng, latitude, longitude);
					}
					if (exactDist >= minMiles && exactDist <= maxMiles) {
						var locColor = item.locationColor;
						if (item.spotCount > 0) {
							addMarker(latitude, longitude, locColor, item.id); // Red
						} else {
							addMarker(latitude, longitude, locColor, item.id); // Grey
						}
						count = count + 1;
					}
				});
				
				var duration = (performance.now() - start);
				$("#locationsFound").html(/*Object.keys(obj).length*/ count + " Locations Found in " + duration.toFixed(2) + "ms.");
			});
			//*/
		}
		
		function postEditLocation() {
			var objText = "{" +
				"\"formTB-locName\": \"" + $("#formTB-locName").val() + "\"," +
				"\"formTB-imageFile\": \"" + $("#edit-location-img").prop('src') + "\"," +
				"\"formTB-imageChanged\": \"" + $("#formTB-locFileChanged").val() + "\"," +
				"\"formTB-locID\": \"" + $("#formTB-locID").val() + "\"," +
				"\"formTB-locTypeID\": \"" + $("#formTB-locTypeID").val() + "\"," +
				"\"formTB-parkDesc\": \"" + $("#formTB-parkDesc").val() + "\"," +
				"\"formTB-hikeMiles\": \"" + $("#formTB-hikeMiles").val() + "\"," +
				"\"formTB-hikeDiff\": \"" + $("#formTB-hikeDiff").val() + "\"," +
				"\"formTB-other\": \"" + $("#formTB-other").val() + "\"," +
				"\"formCB-pubic\": " + $("#formCB-pubic").prop("checked") + "," +
				"\"formCB-paid\": " + $("#formCB-paid").prop("checked") + "," +
				"\"formCB-open\": " + $("#formCB-open").prop("checked") + "," +
				"\"formCB-parking\": " + $("#formCB-parking").prop("checked") + "," +
				"\"formCB-local\": " + $("#formCB-local").prop("checked") + "," +
				"\"formTB-addr\": \"" + $("#formTB-addr").val() + "\"," +
				"\"formTB-city\": \"" + $("#formTB-city").val() + "\"," +
				"\"formTB-state\": \"" + $("#formTB-state").val() + "\"," +
				"\"formTB-zip\": \"" + $("#formTB-zip").val() + "\"," +
				"\"formTB-country\": \"" + $("#formTB-country").val() + "\"," +
				"\"formTB-lat\": \"" + $("#formTB-lat").val() + "\"," +
				"\"formTB-long\": \"" + $("#formTB-long").val() + "\"" +
				"}";
			var obj = JSON.parse(objText);
			if( $("#formTB-locID").val() != "") {
				//*Post to PHP page
				$.post("scripts/updateLocation.php", obj, function(data) {
					alert(data);
				});
				//*/
			} else {
				//*Post to PHP page
				$.post("scripts/addLocation.php", obj, function(data) {
					if (data.indexOf(";") >= 0) {
						var str = data.split(";");
						
						// Load New Location
						retrieveLocationData(str[0]);
						
						// Load Marker
						restoreColors();
						var newMarker = addMarker(str[1], str[2], 'red', str[0]);
						newMarker.icon = pinSymbol('yellow');
					} else {
						alert(data);
					}
				});
				//*/
			}
		}
		
		function resetAddEditForm() {
			$("#edit-location-img").attr("src", "images/Media/defaultLocation.png");
			$("#formTB-locName").val("");
			$("#formTB-locID").val("");
			$("#formTB-locType").val("1"); //
			$("#formTB-parkDesc").val("");
			$("#formTB-hikeMiles").val("");
			$("#formTB-hikeDiff").val("");
			$("#formTB-other").val("");
			
			$("#formCB-pubic").prop("checked", false);
			$("#formCB-paid").prop("checked", false);
			$("#formCB-open").prop("checked", false);
			$("#formCB-parking").prop("checked", false);
			$("#formCB-local").prop("checked", false);
			
			$("#formTB-addr").val("");
			$("#formTB-city").val("");
			$("#formTB-state").val("");
			$("#formTB-zip").val("");
			$("#formTB-country").val("");
			$("#formTB-lat").val("");
			$("#formTB-long").val("");
			
			$("#formTB-spotName").val("");
			$("#formTB-spotType").val(1).change();
			$("#formTB-spotHeight").val("");
			$("#formTB-spotDepth").val("");
			$("#formTB-spotGap").val("");
			$("#formCB-run").prop("checked", false);
			$("#edit-spot-listview").html("");
		}
		
		function setEditForm(locationID) {
			$(document).ready(function() {
				$.ajax({
					url: 'scripts/getLocationData.php?locationID='+locationID,
					type: 'GET',
					dataType: 'JSON',
					success: function(response) {
						$("#edit-location-img").attr("src", response[1]);
						$("#formTB-locName").val(response[0].description);
						$("#formTB-locID").val(response[0].id);
						$("#formTB-locTypeID").val(response[0].locationTypeID).change();
						$("#formTB-parkDesc").val(response[0].parkingDescription);
						$("#formTB-hikeMiles").val(response[0].hikeMiles);
						$("#formTB-hikeDiff").val(response[0].hikeDifficulty);
						$("#formTB-other").val(response[0].otherInfo);
						
						$("#formCB-pubic").prop("checked", (response[0].isPublic == 1 ? true : false));
						$("#formCB-paid").prop("checked", (response[0].isPaid == 1 ? true : false));
						$("#formCB-open").prop("checked", (response[0].isOpen == 1 ? true : false));
						$("#formCB-parking").prop("checked", (response[0].isParking == 1 ? true : false));
						$("#formCB-local").prop("checked", (response[0].isLocal == 1 ? true : false));
						
						$("#formTB-addr").val(response[0].address);
						$("#formTB-city").val(response[0].city);
						$("#formTB-state").val(response[0].state);
						$("#formTB-zip").val(response[0].zip);
						$("#formTB-country").val(response[0].country);
						$("#formTB-lat").val(response[0].latitude);
						$("#formTB-long").val(response[0].longitude);
						
						var spotHTML = "";
						var spotListviewHTML = "";
						$.each(response["spotList"], function(index, value) {
							
							if (value['description'] != null) {
								var spotID = value[0];
								
								thisListviewHTML = "<li class='list-group-item'>" +
								"<button id='" + spotID + "-" + value['description'].replace(" ", "-") + "' class='btn btn-primary' type='button' onclick='selectSpot(this, " + spotID + ");' >" + value['description'] + "</button>" +
								"</li>";
								
								if (value['description'] != null) {
									spotListviewHTML = spotListviewHTML + thisListviewHTML;
								}
							}
						});
						$("#edit-spot-listview").html(spotListviewHTML);
					}
				});
			});
		}
		
		
		function editSpot(delSpot) {
			var spotName = $("#formTB-spotName").val();
			var objText = "{" +
				"\"formTB-spotName\": \"" + spotName + "\"," +
				"\"formTB-imageFile\": \"" + $("#edit-spot-img").prop('src') + "\"," +
				"\"formTB-spotFileChanged\": \"" + $("#formTB-spotFileChanged").val() + "\"," +
				"\"formTB-spotID\": \"" + $("#formTB-spotID").val() + "\"," +
				"\"formTB-spotLocID\": \"" + $("#formTB-spotLocID").val() + "\"," +
				"\"formTB-spotTypeID\": \"" + $("#formTB-spotTypeID").val() + "\"," +
				"\"formTB-spotHeight\": \"" + $("#formTB-spotHeight").val() + "\"," +
				"\"formTB-spotDepth\": \"" + $("#formTB-spotDepth").val() + "\"," +
				"\"formTB-spotGap\": \"" + $("#formTB-spotGap").val() + "\"," +
				"\"formCB-run\": \"" + $("#formCB-run").prop("checked") + "\",";
			
			var verify = true;
			if (delSpot) {
				//alert("Delete Spot");
				objText = objText + "\"formTB-btnStatus\": \"" + 1 + "\"}";
			} else {
				if ($.isNumeric($("#formTB-spotID").val())) {
					//alert("Update Spot");
					objText = objText + "\"formTB-btnStatus\": \"" + 2 + "\"}";
				} else {
					//alert("Add Spot");
					objText = objText + "\"formTB-btnStatus\": \"" + 3 + "\"}";
					setEditForm($("#formTB-spotLocID").val());
				}
				
				// Verification
				if (spotName.replace(/\s/g, "") == "") {
					verify = false;
				}
			}
			
			
			if (verify == true) {
				var obj = JSON.parse(objText);
				//*Post to PHP page
				$.post("scripts/updateSpot.php", obj, function(data) {
					alert(data);
				});
			} else {
				alert("Spot Name cannot be blank.");
			}
			//*/
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
								restoreColors();
								if (checkMarkerExists(response[0].latitude, response[0].longitude) == false) {
									var newMarker = addMarker(response[0].latitude, response[0].longitude, 'red', response[0].id);
									newMarker.icon = pinSymbol('yellow');
									retrieveLocationData(response[0].id);
								} else {
									var marker = getMarkerByLocation(response[0].latitude, response[0].longitude);
									marker.icon = pinSymbol('yellow');
									retrieveLocationData(response[0].id);
								}
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
		
		function collapseFilters(element) {
			var container = $("#collapsible-filter");
			var upArrow = $("#filter-btn").find(".fa-arrow-circle-up");
			var downArrow = $("#filter-btn").find(".fa-arrow-circle-down");
			
			if (container.hasClass("hidden")) {
				container.removeClass("hidden");
				upArrow.removeClass("hidden");
				downArrow.addClass("hidden");
				
			} else {
				container.addClass("hidden");
				upArrow.addClass("hidden");
				downArrow.removeClass("hidden");
			}
		}
		
		function setSlick() {
			$(".all-spots").removeClass("slick-initialized");
			$(".all-spots").removeClass("slick-slider");
			
			$('.all-spots').slick({
			  infinite: false,
			  slidesToShow: 1,
			  slidesToScroll: 1,
			  variableWidth: true,
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
		
		function selectSpot(spot, id) {
			var dontAdd = false;
			if (spot.classList.contains("selected")) {
				dontAdd = true;
			}
			
			// Remove All Selected Classes
			$("#edit-spot-listview :button").each(function () {
				$(this).removeClass("selected");
			});
			
			if (dontAdd == false) {
				retreiveSpotData(id);
				spot.classList.add("selected");
				$("#spot-addEdit-btn").html("Update Spot");
				$("#spot-delete-btn").prop('disabled', false);
			} else {
				retreiveSpotData(-1);
				$("#spot-addEdit-btn").html("Add New Spot");
				$("#spot-delete-btn").prop('disabled', true);
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
    <div id="page-contents" style="overflow: hidden;height:100%;">
        <div class="row" style="height: 100%;overflow: auto;overflow-y: hidden;">
            <div class="col-md-6 col-xl-5 offset-xl-0 d-flex flex-column justify-content-start" id="location-finder" style="margin-bottom: 10px;">
                <div id="map-filters" style="margin: 0px;padding: 5px;">
                    <form class="d-flex flex-column flex-fill align-self-start flex-wrap" method="post" style="margin: 5px;height: 100%;">
						<a id="filter-btn" href="javascript:;" onclick="collapseFilters(this);" style="text-decoration: none;color:black;">
							<h2>
								Filters: 
								<i class="fa fa-arrow-circle-down hidden"></i>
								<i class="fa fa-arrow-circle-up"></i>
							</h2>
						</a>
						<div id="collapsible-filter" class="d-flex flex-row">
							<div class="text-left d-flex float-left flex-column flex-grow-1 col-md-6" id="filter-left" style="padding: 0px;padding-right: 5px;padding-left: 5px;">
								<div class="form-group d-flex flex-column" style="width: 100%;">
									<label style="margin-bottom: 0;margin-left: 0;margin-right: 10px;">Height</label>
									<input type="text" class="js-range-slider" id="rangeHeight" name="range_height" value="" />
									<script>
										 $(".js-range-slider").ionRangeSlider({
											type: "double",
											min: 0,
											max: 200,
											from: 0,
											to: 200,
											step: 5,
											skin: "round",
											postfix: " ft"
										 });
									</script>
								</div>
								<div class="form-group d-flex flex-column" style="margin-bottom: 16px;margin-right: 0;margin-left: 0;">
									<label style="margin-bottom: 0px;margin-right: 10px;">Depth</label>
									<input type="text" class="js-range-slider" id="rangeDepth" name="range_depth" value="" />
									<script>
										 $(".js-range-slider").ionRangeSlider({
											type: "double",
											min: 0,
											max: 100,
											from: 0,
											to: 100,
											skin: "round",
											postfix: " ft"
										 });
									</script>
								</div>
								<div class="form-group d-flex flex-column" style="margin-top: 0px;margin-bottom: 5px;">
									<label class="text-nowrap d-xl-flex align-items-xl-center" style="margin-right: 10px;margin-bottom: 0px;width: 50%;">Within Miles</label>
									<input type="text" class="js-range-slider" id="rangeMiles" name="range_miles" value="" />
									<script>
										 $(".js-range-slider").ionRangeSlider({
											type: "double",
											min: 0,
											max: 5000,
											from: 0,
											to: 100,
											step: 5,
											skin: "round",
											postfix: " mi",
											onChange: function (data) {
												updateDonut(data.from, data.to);
											}
										 });
									</script>
								</div>
							</div>
							<div class="text-left d-flex float-right flex-column flex-grow-1 col-md-6" id="filter-right" style="padding: 0px;padding-left: 5px;padding-right: 5px;">
								<div class="form-group d-flex" style="margin-bottom: 5px;"><label class="text-nowrap d-flex d-xl-flex flex-column align-items-end" style="margin-right: 10px;margin-bottom: 0px;width: 50%;">Location Type</label>
									<div class="dropdown dropdown-checkbox" style="width: 50%;">
										<button class="btn btn-primary dropdown-toggle btn-checkbox" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgb(255,255,255);color: rgb(0,0,0);">Select...</button>
										<div class="dropdown-menu" id="dropdownLocType" role="menu" style="padding-right: 0px;padding-left: 10px;">
											<?php
												foreach ($locationTypeList as $key => $value) {
													echo "<div class='form-check'>";
													echo "<input class='form-check-input' type='checkbox' id='locType-".$value['id']."'>";
													echo "<label class='form-check-label' for='locType-".$value['id']."''>".$value['description']."</label>";
													echo "</div>";
												}
											?>
										</div>
									</div>
								</div>
								<div class="form-group d-flex flex-grow-0" style="margin-bottom: 5px;"><label class="text-nowrap d-flex d-xl-flex flex-column align-items-end" style="margin-right: 10px;margin-bottom: 0px;width: 50%;">Spot Type</label>
									<div class="dropdown dropdown-checkbox">
									<button class="btn btn-primary dropdown-toggle btn-checkbox" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgb(255,255,255);color: rgb(0,0,0);">Select...</button>
										<div class="dropdown-menu" id="dropdownSpotType" role="menu" style="padding-right: 0px;padding-left: 10px;">
											<?php
												foreach ($spotTypeList as $key => $value) {
													echo "<div class='form-check'>";
													echo "<input class='form-check-input' type='checkbox' id='spotType-".$value['id']."'>";
													echo "<label class='form-check-label' for='spotType-".$value['id']."'>".$value['description']."</label>";
													echo "</div>";
												}
											?>
										</div>
									</div>
								</div>
								<div class="form-group d-flex" style="margin-bottom: 5px;"><label class="text-nowrap d-flex d-xl-flex flex-column align-items-end" style="margin-right: 10px;margin-bottom: 0px;width: 50%;">Logistics</label>
									<div class="dropdown dropdown-checkbox">
									<button class="btn btn-primary dropdown-toggle btn-checkbox" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgb(255,255,255);color: rgb(0,0,0);">Select...</button>
										<div class="dropdown-menu" id="dropdownLogistic" role="menu" style="padding-right: 0px;padding-left: 10px;">
											<div class="form-check"><input class="form-check-input" type="checkbox" id="logistic-isPublic"><label class="form-check-label" for="logistic-isPublic">Public Location</label></div>
											<div class="form-check"><input class="form-check-input" type="checkbox" id="logistic-isPaid"><label class="form-check-label" for="logistic-isPaid">Paid Entrance</label></div>
											<div class="form-check"><input class="form-check-input" type="checkbox" id="logistic-isOpen"><label class="form-check-label" for="logistic-isOpen">Open</label></div>
											<div class="form-check"><input class="form-check-input" type="checkbox" id="logistic-isParking"><label class="form-check-label" for="logistic-isParking">Has Parking</label></div>
											<div class="form-check"><input class="form-check-input" type="checkbox" id="logistic-isLocal"><label class="form-check-label" for="logistic-isLocal">Locals Only (No Tourists)</label></div>
										</div>
									</div>
								</div>
								<div class="form-group d-flex" style="margin-bottom: 5px;">
								<label class="text-nowrap d-flex d-xl-flex flex-column align-items-end" style="margin-right: 10px;margin-bottom: 0px;width: 50%;">State</label>
									<input id="stateList" class="form-control" type="text" style="width: 50%;" />
								</div>
								<div class="form-group d-flex justify-content-end" style="margin-bottom: 5px;">
									<div class="form-check"><input class="form-check-input" type="checkbox" id="checkbox-showAll"><label class="form-check-label" for="checkbox-showAll">Show Incomplete Locations</label></div>
								</div>
								<div class="d-flex flex-row flex-grow-1 justify-content-between" style="padding-bottom: 5px;">
									<button class="btn btn-primary text-left d-xl-flex align-self-end" type="button">Reset Filters</button>
									<button class="btn btn-primary text-left d-xl-flex align-self-end" type="button" onclick="findLocations();">Find Locations</button>
								</div>
							</div>
						</div>
                    </form>
                </div>
                <div id="mapinfo" class="flex-grow-1" style="overflow: hidden;border-radius: 10px;">
                    <p id="locationsFound">0 Locations found</p>
					<div id="map" style="background-color: #abc;height:100%;"></div>
					<script>
						var map;
						var markers = [];
						var radiusDonut;

						function initMap() {
							var centerUSA = {lat: 39.8283, lng: -98.5795};

							map = new google.maps.Map(document.getElementById('map'), {
							  zoom: 4,
							  center: centerUSA,
							  mapTypeId: 'roadmap'
							});

							map.addListener('click', function(event) {
								restoreColors();
								deselectLocation();
							});
						}

						// Adds a marker to the map and push to the array.
						function addMarker(lat, lng, color, locID) {
							var marker = new google.maps.Marker({
							  position: new google.maps.LatLng(lat, lng),
							  map: map,
							  actualLat: lat,
							  actualLng: lng,
							  icon: pinSymbol(color),
							  originalColor: color,
							  locationID: locID
							});

							marker.addListener('click', changeColor);
							markers.push(marker);

							return marker;
						}
						
						function drawCircle(point, radius, dir) {
							var d2r = Math.PI / 180; // degrees to radians 
							var r2d = 180 / Math.PI; // radians to degrees 
							var earthsradius = 3963; // 3963 is the radius of the earth in miles

							var points = 32;

							// find the raidus in lat/lon 
							var rlat = (radius / earthsradius) * r2d;
							var rlng = rlat / Math.cos(point.lat() * d2r);


							var extp = new Array();
							if (dir == 1) {
								var start = 0;
								var end = points + 1;
							} // one extra here makes sure we connect the
							else {
								var start = points + 1;
								var end = 0;
							}
							for (var i = start; (dir == 1 ? i < end : i > end); i = i + dir) {
								var theta = Math.PI * (i / (points / 2));
								ey = point.lng() + (rlng * Math.cos(theta)); // center a + radius x * cos(theta) 
								ex = point.lat() + (rlat * Math.sin(theta)); // center b + radius y * sin(theta) 
								extp.push(new google.maps.LatLng(ex, ey));
							}
							return extp;
						}
						
						function createDonut(lt, lg, innerRad, outerRad) {
							var donut = new google.maps.Polygon({
								paths: [drawCircle(new google.maps.LatLng(lt, lg), outerRad, 1),
										drawCircle(new google.maps.LatLng(lt, lg), innerRad, -1)],
								strokeColor: "#FF0000",
								strokeOpacity: 0.8,
								strokeWeight: 2,
								fillColor: "#FF0000",
								fillOpacity: 0.35,
								actualLat: lt,
							    actualLng: lg
							});
							donut.setMap(map);
							radiusDonut = donut;
							return donut;
						}
						
						function updateDonut(innerRad, outerRad) {
							var arrPath = [drawCircle(new google.maps.LatLng(radiusDonut.actualLat, radiusDonut.actualLng), outerRad, 1),
										drawCircle(new google.maps.LatLng(radiusDonut.actualLat, radiusDonut.actualLng), innerRad, -1)];
							radiusDonut.setMap(null);
							radiusDonut.setPaths(arrPath);
							radiusDonut.setMap(map);
						}

						
						
						
						function checkMarkerExists(lat, lng) {
							for (var i = 0; i < markers.length; i++) {
								var lt = markers[i].actualLat;
								var lg = markers[i].actualLng;
								
								if (lt == lat && lg == lng) {
									return true;
								}
							}
							return false;
						}
						
						function getCurrentSelectedMarker() {
							for (var i = 0; i < markers.length; i++) {
								var ico = markers[i].icon;
								
								if (ico["fillColor"] == "yellow") {
									return markers[i].locationID;
								}
							}
							return -1;
						}

						function getMarkerByLocation(lat, lng) {
							for (var i = 0; i < markers.length; i++) {
								var lt = markers[i].actualLat;
								var lg = markers[i].actualLng;
								
								if (lt == lat && lg == lng) {
									return markers[i];
								}
							}
							return null;
						}

						// Deletes all markers in the array by removing references to them.
						function setMapOnAll(map) {
							for (var i = 0; i < markers.length; i++) {
							  markers[i].setMap(map);
							}
						}
						function clearMarkers() {
							setMapOnAll(null);
						}
						
						function deleteMarkers() {
							clearMarkers();
							markers = [];
						}
						
						function changeColor(evt) {
							restoreColors();
							this.setIcon(pinSymbol('yellow'));
							retrieveLocationData(this.locationID);
						}

						function pinSymbol(color) {
							return {
								path: 'M 0,0 C -2,-20 -10,-22 -10,-30 A 10,10 0 1,1 10,-30 C 10,-22 2,-20 0,0 z',
								fillColor: color,
								fillOpacity: 1,
								strokeColor: '#000',
								strokeWeight: 2,
								scale: 1
							};
						}
						function restoreColors() {
							for (var i=0; i<markers.length; i++) {
								markers[i].setIcon(pinSymbol(markers[i].originalColor));
							}
						}
					</script>
					<script async defer
					src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAt7fnjK_EeUbPhDxOw51SOBdc_LLNkMss&callback=initMap">
					</script>
				</div>
            </div>
            <div class="col-md-6 col-xl-7" id="overview" style="margin-bottom: 10px;height: 100%;overflow: auto;overflow-y: hidden;">
                <div id="view-current-location" class="hidden" style="height: 100%;overflow: auto;overflow-y: auto;">
                    <div class="row" id="location-section" style="margin: 0px;padding: 5px;">
                        <div class="col d-flex flex-row flex-fill align-self-start flex-wrap" style="padding: 0;">
                            <div class="flex-grow-1 data-container-half" id="location-data" style="margin: 5px;padding: 5px;background-color: #ddd;">
								<img id="location-image" src="">
                                <p id="disp-lastUpdate"></p>
                            </div>
                            <div class="flex-grow-1 flex-shrink-0 data-container-half" id="location-address" style="margin: 5px;padding: 5px;background-color: #ddd;">
                                <div style="height: 40px;">
                                    <h1 id="disp-description" class="float-left" style="margin-bottom: 0;margin-right: 5px;"></h1>
                                    <div class="dropdown d-inline float-right" style="margin-left: 5px;">
										<button class="btn btn-primary dropdown-toggle float-right" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgba(0,0,0,0);"><i class="fa fa-align-justify"></i></button>
                                        <div class="dropdown-menu" role="menu">
											<?php 
												$query = "SELECT canEditContent FROM accounts WHERE id=".$userID;
												$result = $dbController->runQueryBasic($query)['canEditContent'];
												if ($result == 1) {
													echo "<a id='link-edit-location' class='dropdown-item' role='presentation' href='#'>Edit Location</a>";
													echo "<a id='link-add-location' class='dropdown-item' role='presentation' href='#'>Add New Location</a>";
												}
											?>
											<a id="link-view-changelog" class="dropdown-item" role="presentation" href="#">View Changelog</a>
										</div>
                                    </div>
                                </div>
                                <p id="disp-address"></p>
                                <p id="disp-coordinates"></p>
								<p id="disp-distance"></p>
								<p id="disp-drivingDist"></p>
								<p id="disp-drivingTime"></p>
								<p id="disp-otherInfo" style="margin-top: 5px;"></p>
								<p id="disp-parkingDesc" style="margin-top: 5px;"></p>
                            </div>
                            <div class="flex-grow-1 flex-shrink-0 data-container" id="location-spot" style="background-color: #ddd;padding: 5px;margin: 5px;">
                                <h2 class="text-nowrap">Spot / Takeoffs</h2>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="text-right"><strong># of Spots:</strong></td>
                                                <td id="disp-numSpots" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Height Range:</strong></td>
                                                <td id="disp-heightRange" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Depth Range:</strong></td>
                                                <td id="disp-depthRange" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Gap Range:</strong></td>
                                                <td id="disp-gapRange" class="text-left"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="flex-grow-1 flex-shrink-0 data-container" id="location-logistics" style="background-color: #ddd;margin: 5px;padding: 5px;">
                                <h2>Logistics</h2>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="text-right"><strong>Public Spot:</strong></td>
                                                <td id="disp-public" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Paid Entrance:</strong></td>
                                                <td id="disp-paid" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Open:</strong></td>
                                                <td id="disp-open" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Has Parking:</strong></td>
                                                <td id="disp-parking" class="text-left"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="flex-grow-1 flex-shrink-0 data-container" id="location-hike" style="background-color: #ddd;padding: 5px;margin: 5px;">
                                <h2>Hike Info</h2>
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td class="text-right"><strong>Miles:</strong></td>
                                                <td id="disp-hikeMiles" class="text-left"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><strong>Difficulty:</strong></td>
                                                <td id="disp-hikeDiff" class="text-left"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="spot-section" style="padding: 5px;margin: 0px;margin-top: 10px;">
						<div id="disp-spots" class="col d-flex flex-row flex-fill justify-content-start align-content-center align-self-start all-spots" style="padding: 0;overflow: hidden;">
						</div>
                    </div>
                    <div class="row" id="comment-section" style="margin: 0px;padding: 5px;margin-top: 10px;overflow: auto;">
                        <div class="col" style="padding-right: 5px;padding-left: 5px;overflow: auto;overflow-y: auto;">
                            <div id="comment-container">
                                <div class="post-comment">
                                    <h1>Leave a Comment</h1>
                                    <div class="d-flex comment-area">
									<img src="<?php echo $imgFile; ?>" style="margin-left: 0px;margin-right: 5px;">
                                        <form id="frm-postComment" class="flex-grow-1" method="post">
                                            <div class="form-group">
												<textarea class="form-control" id="formComment" name="formComment" rows="3" placeholder="Comment..." maxlength="200" style="height: 75px;resize:none;" required></textarea>
												<?php
													$query = "SELECT canComment FROM accounts WHERE id=".$userID;
													$result = $dbController->runQueryBasic($query)['canComment'];
												?>
												<button id="frm-postComment-btn" class="btn btn-primary float-right" type="button" style="margin-top: 5px;" <?php echo ($result == 0 ? 'disabled=disabled' : ''); ?>>Comment</button>
											</div>
                                        </form>
                                    </div>
                                </div>
                                <div id="disp-comments" class="display-comment">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="add-edit-location" class="hidden" style="background-color: #eee;margin: 0px;padding: 5px;border-radius: 10px;height: 100%;overflow-y: auto;">
                    <div class="row" style="margin: 0px;padding: 0px;">
                        <div class="col" style="padding: 5px;">
							<a id="link-addedit-back" class="float-right" href="#" style="color: rgb(0,25,255);">[Go Back]</a>
                            <h1 id="addedit-header">Add / Edit Location</h1>
                            <form id="addedit-form" name="addedit-form" method="post" style="overflow: hidden;">
								<div class="d-flex flex-column" id="edit-location">
									<div class="d-flex flex-row">
										<div class="d-flex flex-column flex-grow-1" id="edit-location-topgroup" style="margin-right: 5px;width: 50%;">
											<div class="d-flex flex-grow-0">
												<div class="form-group d-flex float-left flex-column" style="width: 150px;margin-right: 5px;">
													<label style="margin: 0px;padding: 0px;"><strong>Main Photo</strong></label>
													<img id="edit-location-img" src="" style="margin-bottom: 5px;height: 150px;width:150px">
													<label for="location-fileToUpload">
														<input type="file" name="location-fileToUpload" id="location-fileToUpload" style="display:none;" onchange="readURL(this, 'edit-location-img', 'formTB-locFileChanged');"></input>
														<span class="btn btn-primary" id="edit-location-upload-photo" type="button" style="padding: 0px;">Upload Photo</span>
														<input class="form-control" type="hidden" id="formTB-locFileChanged" name="formTB-locFileChanged" value="">
													<label>
												</div>
												<div class="d-flex float-left flex-column" id="edit-location-misc" style="margin-left: 5px;">
													<div class="form-group float-left" style="margin: 0px;">
														<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Location Name *</strong></label>
														<input class="form-control" type="text" id="formTB-locName" name="formTB-locName" value="" required>
														<input class="form-control" type="hidden" id="formTB-locID" name="formTB-locID" value="">
													</div>
													<div class="form-group float-left" style="margin: 0px;">
														<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Location Type *</strong></label>
														<select class="form-control" id="formTB-locTypeID" name="formTB-locTypeID" required>
															<?php
																foreach ($locationTypeList as $key => $value) {
																	echo "<option value='".$value['id']."'>".$value['description']."</option>";
																}
															?>
														</select>
													</div>
													<div class="form-group float-left" style="margin: 0px;">
														<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Parking Description</strong></label>
														<input class="form-control" type="text" id="formTB-parkDesc" name="formTB-parkDesc" value="">
													</div>
													<div class="d-flex">
														<div class="form-group float-left flex-grow-1" style="margin-right: 5px;margin-bottom: 0px;">
															<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Hike Miles</strong></label>
															<input class="form-control" type="text" id="formTB-hikeMiles" name="formTB-hikeMiles" value="">
														</div>
														<div class="form-group float-left flex-grow-1" style="margin-left: 5px;margin-bottom: 0px;">
															<label class="text-nowrap" style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Difficulty</strong><br></label>
															<input class="form-control" type="text" id="formTB-hikeDiff" name="formTB-hikeDiff" value="">
														</div>
													</div>
													<div class="form-group float-left" style="margin-bottom: 0px;">
														<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Other Info</strong></label>
														<input class="form-control" type="text" id="formTB-other" name="formTB-other" value="">
													</div>
												</div>
											</div>
											<div class="d-flex d-xl-flex flex-row flex-wrap" id="edit-location-topcheckboxes">
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-pubic" name="formCB-pubic">
													<label class="form-check-label" for="formCheck-3"><strong>Public Location</strong></label>
												</div>
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-paid" name="formCB-paid">
													<label class="form-check-label" for="formCheck-3"><strong>Paid Entrance</strong></label>
												</div>
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-open" name="formCB-open">
													<label class="form-check-label" for="formCheck-3"><strong>Open</strong></label>
												</div>
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-parking" name="formCB-parking">
													<label class="form-check-label" for="formCheck-3"><strong>Has Parking</strong></label>
												</div>
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-local" name="formCB-local">
													<label class="form-check-label" for="formCheck-3"><strong>Locals Only (No Tourists)</strong></label>
												</div>
											</div>
										</div>
										<div class="d-flex flex-column flex-grow-1" id="edit-location-topright" style="margin-left: 5px;">
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Address</strong></label>
												<input class="form-control" type="text" id="formTB-addr" name="formTB-addr" value="">
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>City</strong></label>
												<input class="form-control" type="text" id="formTB-city" name="formTB-city" value="">
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>State</strong></label>
												<input class="form-control" type="text" maxlength="3" id="formTB-state" name="formTB-state" value="">
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Zip</strong></label>
												<input class="form-control" type="text" id="formTB-zip" name="formTB-zip" value="">
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Country</strong></label>
												<input class="form-control" type="text" id="formTB-country" name="formTB-country" value="">
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Latitude *</strong></label>
												<input class="form-control" type="text" id="formTB-lat" name="formTB-lat" value="" required>
											</div>
											<div class="form-group float-left" style="margin-bottom: 0px;">
												<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Longitude *</strong></label>
												<input class="form-control" type="text" id="formTB-long" name="formTB-long" value="" required>
											</div>
										</div>
									</div>
									<button class="btn btn-primary align-self-end" type="button" onclick="postEditLocation();" style="margin-top: 10px;width: 30%;min-width: 130px;">Save Changes</button>
								</div>
							</form>
                            <hr>
							<form id="addeditSpot-form" name="addeditSpot-form" method="post">
                                <div class="d-flex flex-column" id="edit-spot" style="margin-top: 10px;">
									<div class="d-flex">
										<div class="d-flex flex-column flex-grow-1" id="edit-spot-left" style="width: 50%;margin: 0px;margin-right: 5px;background-color: #ddd;border-radius: 5px;padding: 5px;position: relative;">
											<h2>Spot List (Select to Edit)</h2>
											<ul class="list-group" id="edit-spot-listview" style="margin-top: 10px;">
											</ul>
										</div>
										<div class="d-flex flex-grow-1" id="edit-spot-right" style="width: 50%;margin: 0px;margin-left: 5px;">
											<div class="flex-grow-1" id="edit-spot-right-left" style="/*width: 50%;*/margin-right: 5px;">
												<div class="form-group d-flex float-left flex-column" style="width: 150px;">
													<label style="margin: 0px;padding: 0px;"><strong>Spot Photo</strong></label>
													<img id="edit-spot-img" src="assets/img/defaultLocation.png" style="margin-bottom: 5px;  width: 140px;border: solid;border-width: 1px;">
													<label for="spot-fileToUpload">
														<input type="file" name="spot-fileToUpload" id="spot-fileToUpload" style="display:none;" onchange="readURL(this, 'edit-spot-img', 'formTB-spotFileChanged');"></input>
														<span class="btn btn-primary" id="edit-spot-upload-photo" type="button" style="padding: 0px;width:80%;height:30%;">Upload Photo</span>
														<input class="form-control" type="hidden" id="formTB-spotFileChanged" name="formTB-spotFileChanged" value="">
													</label>
												</div>
											</div>
											<div class="d-flex flex-column flex-grow-1" id="edit-spot-right-right" style="width: 50%;margin-left: 5px;">
												<div class="form-group float-left" style="margin: 0px;">
													<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Spot Name *</strong></label>
													<input class="form-control" type="text" id="formTB-spotName" name="formTB-spotName">
													<input class="form-control" type="hidden" id="formTB-spotID" name="formTB-spotID" value="">
													<input class="form-control" type="hidden" id="formTB-spotLocID" name="formTB-spotLocID" value="">
												</div>
												<div class="form-group float-left" style="margin: 0px;">
													<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Spot Type</strong></label>
													<select class="form-control" id="formTB-spotTypeID" name="formTB-spotTypeID">
														<?php
															foreach ($spotTypeList as $key => $value) {
																echo "<option value='".$value['id']."'>".$value['description']."</option>";
															}
														?>
													</select>
												</div>
												<div class="form-group float-left" style="margin: 0px;">
													<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Height FT(min-max)</strong></label>
													<input class="form-control" type="text" id="formTB-spotHeight" name="formTB-spotHeight">
												</div>
												<div class="form-group float-left" style="margin: 0px;">
													<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Pool Depth FT(min-max)</strong></label>
													<input class="form-control" type="text" id="formTB-spotDepth" name="formTB-spotDepth">
												</div>
												<div class="form-group float-left" style="margin: 0px;">
													<label style="margin: 0px;margin-top: 0px;padding: 0px;"><strong>Gap FT(min-max)</strong></label>
													<input class="form-control" type="text" id="formTB-spotGap" name="formTB-spotGap">
												</div>
												<div class="form-check text-nowrap" style="margin: 5px;">
													<input class="form-check-input" type="checkbox" id="formCB-run" name="formCB-run">
													<label class="form-check-label" for="formCheck-3"><strong>Runnable</strong></label>
												</div>
											</div>
										</div>
									</div>
									<div class="d-flex flex-grow-1 justify-content-between align-items-end">
										<button id="spot-delete-btn" class="btn btn-primary text-left float-right" type="button" name="btnDelete" onclick="editSpot(true);" style="padding: 0px;padding-top: 0px;padding-left: 10px;padding-right: 10px;margin-top: 5px;" disabled>Delete Spot</button>
										<button id="spot-addEdit-btn" class="btn btn-primary text-center d-none d-xl-flex align-items-xl-center" type="button" name="btnAdd" onclick="editSpot(false);" style="padding: 0px;padding-top: 0px;padding-left: 10px;padding-right: 10px;margin-top: 5px;">Add New Spot</button>
									</div>
                                </div>
							</form>
                        </div>
                    </div>
                </div>
                <div id="location-changelog" class="flex-column hidden" style="background-color: #eee;border-radius: 10px;padding: 5px;overflow: auto;overflow-y: auto;">
					<a id="link-changelog-back" class="float-right" href="#" style="color: rgb(0,25,255);">[Go Back]</a>
                    <h1>Changelog</h1>
                    <p id="changelog-count" style="font-size: 10px;margin: 0px;padding: 0px;"></p>
                    <div id="changelog-container" class="d-flex flex-column" style="height: 100%;padding: 0px;padding-left: 5px;padding-right: 5px;">
                    </div>
                </div>
				<div id="no-location-selected" style="background-color: #eee;/*height: 100%;*/border-radius: 10px;padding: 5px;">
					<h1 style="margin: 0px;">No location selected...</h1>
					<p>To select a location, click a pin on the map.</p>
				</div>
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
	<script type="text/javascript" src="assets/slick/slick.min.js"></script>
</body>
</html>