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
</head>

<body class="d-flex flex-column" style="height: 100%;margin: 0px;padding: 5px;">
    <nav class="navbar navbar-light navbar-expand-md navigation-clean-search" style="padding-right: 0px;padding-left: 0px;">
        <div class="container"><a class="navbar-brand" href="#"><img id="logo" src="assets/img/logo.png"></a><button data-toggle="collapse" class="navbar-toggler" data-target="#navcol-1"><span class="sr-only">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
            <div
                class="collapse navbar-collapse" id="navcol-1">
                <form class="form-inline mx-auto" target="_self" style="width: 50%;">
                    <div class="form-group" style="width: 100%;"><input class="form-control search-field" type="search" id="search-field" name="search" placeholder="Search Locations or People" style="width: 90%;margin: 0px;color: rgb(0,0,0);"><label for="search-field" style="width: 10%;"><i class="fa fa-search"></i></label></div>
                </form>
                <ul class="nav navbar-nav">
                    <li class="nav-item dropdown"><a class="dropdown-toggle nav-link" data-toggle="dropdown" aria-expanded="false" href="#">Username<img id="profile-picture" src="assets/img/defaultProfile.jpg"></a>
                        <div class="dropdown-menu" role="menu"><a class="dropdown-item" role="presentation" href="#">Edit Profile</a><a class="dropdown-item" role="presentation" href="#">Admin Dashboard</a><a class="dropdown-item" role="presentation" href="#">Logout</a></div>
                    </li>
                </ul>
        </div>
        </div>
    </nav>
    <div class="d-flex flex-grow-1 flex-fill" id="page-contents" style="overflow: hidden;">
        <div class="d-flex flex-column flex-grow-0" id="profile-info-bar" style="background-color: #eee;margin: 0px;padding: 5px;width: 20%;position: relative;margin-right: 10px;border-radius: 10px;">
            <div class="dropdown d-inline float-right" style="margin-left: 5px;position: absolute;top: 5px;right: 5px;"><button class="btn btn-primary dropdown-toggle float-right" data-toggle="dropdown" aria-expanded="false" type="button" style="background-color: rgba(0,0,0,0);"><i class="fa fa-align-justify"></i></button>
                <div class="dropdown-menu" role="menu"><a class="dropdown-item" role="presentation" href="#">Send Message</a><a class="dropdown-item" role="presentation" href="#">Report</a></div>
            </div><img src="assets/img/defaultProfile.jpg" style="margin: 0 auto;">
            <h2 style="text-align: center;font-size: 25px;">SomeUser</h2>
            <p style="text-align: center;"><strong>Full Name</strong></p>
            <div class="flex-grow-1" style="background-color: #ddd;margin-top: 10px;margin-bottom: 10px;padding: 5px;margin-right: 5px;margin-left: 5px;">
                <p style="width: 100%;height: 100%;">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis
                    aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.<br></p>
            </div>
            <div class="d-flex flex-column" style="height: auto;">
                <p style="text-align: center;font-size: 12px;">Member Since: 1/1/2020</p><a class="text-center" href="#" style="color: rgb(36,0,255);">www.google.com</a></div>
        </div>
        <div class="d-flex flex-column flex-grow-1" id="profile-contributions" style="background-color: #eee;padding: 5px;width: 50%;margin-left: 10px;border-radius: 10px;">
            <h1>Contributions:</h1>
            <div class="flex-grow-1" id="contribution-container" style="padding: 5px;overflow-y: auto;">
                <div class="contribution-item" style="background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="float-left"><strong>Commented on&nbsp;</strong></p><a href="#" style="color: rgb(0,25,255);font-style: italic;"><strong>Twisted Falls:</strong></a></div>
                        <p class="flex-grow-1" style="text-align: right;font-size: 12px;">3/1/2020 11:59 PM</p>
                    </div>
                    <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat."<br></p>
                </div>
                <div class="contribution-item" style="background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="float-left"><strong>Uploaded Photos to&nbsp;</strong></p><a href="#" style="color: rgb(0,25,255);font-style: italic;"><strong>Twisted Falls:</strong></a></div>
                        <p class="flex-grow-1" style="text-align: right;font-size: 12px;">3/1/2020 11:59 PM</p>
                    </div><img src="assets/img/defaultLocation.png"><img src="assets/img/defaultLocation.png"></div>
                <div class="contribution-item" style="background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="float-left"><strong>Edited&nbsp;</strong></p><a href="#" style="color: rgb(0,25,255);font-style: italic;"><strong>Twisted Falls:</strong></a></div>
                        <p class="flex-grow-1" style="text-align: right;font-size: 12px;">3/1/2020 11:59 PM</p>
                    </div>
                    <p>Changed Field <strong>Address </strong>from: <em>"123 addr st."</em> to <em>"456 addr st."</em><br></p>
                </div>
                <div class="flex-column contribution-item" style="background-color: #ddd;padding: 5px;border-radius: 5px;margin-top: 5px;margin-bottom: 5px;overflow: auto;">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="float-left"><strong>Added new Location&nbsp;</strong></p><a href="#" style="color: rgb(0,25,255);font-style: italic;"><strong>Twisted Falls:</strong></a></div>
                        <p class="flex-grow-1" style="text-align: right;font-size: 12px;">3/1/2020 11:59 PM</p>
                    </div>
                    <p class="float-left">Address: 1234 Addr St, NC 27040 USA<br>Coordinates: 12.452N, -5.674W<br>Location Type: Lake<br># of Spots: 4</p><img class="float-right" src="assets/img/defaultLocation.png"></div>
            </div>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/js/multirange.js"></script>
    <script src="assets/js/Toggle-Checkbox.js"></script>
</body>

</html>