<?php

namespace MRBS;

use PDO;
// Database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mrbs";
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set("Asia/Dhaka");

if (isset($_POST['signin'])) {

  session_start();
  $message = "";
  if (count($_POST) > 0) {
    $txtName = $_POST['name'];
    $txtPassword_hash = $_POST['password_hash'];

    // print_r($_POST['name']); exit;
    // $con = mysqli_connect('127.0.0.1:3306', 'root', '', 'admin') or die('Unable To connect');
    // $result = mysqli_query($con, "SELECT * FROM login_user WHERE name='" . $_POST["name"] . "' and password = '" . $_POST["password"] . "'");
    // $row  = mysqli_fetch_array($result);



    $result = $conn->prepare("SELECT id, `name`,`role`, `email`,password_hash from mrbs_users 
    where name ='$txtName' and password_hash = '$txtPassword_hash'");

    $result->execute();
    $row = $result->fetch();
    $name = $row['name'];
    $password_hash = $row['password_hash'];

    // print_r($row); exit;
    //  $name = $row['name'];
    $auth["session"] = "php";
    $auth["type"] = "config";

    if ($row['role'] == 'admin') {
      $auth["admin"][] = $name;
      $auth["user"]["$name"] = $name;
    } elseif ($row['role'] == 'user') {
      $auth["user"]["$name"] = $name;
      $auth["user"][] = $name;
    }

    if (is_array($row)) {
      $_SESSION["id"] = $row['id'];
      $_SESSION["name"] = $row['name'];
    } else {
      $_SESSION['message'] = "Invalid Username or Password!";
    }
  }
  if (isset($_SESSION["id"])) {
    header("Location:index.php");
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Meeting Room Booking System</title>
  <!-- Bootstrap -->
  <link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <!-- NProgress -->
  <link href="vendors/nprogress/nprogress.css" rel="stylesheet">

  <!-- Custom Theme Style -->
  <link href="build/css/custom.min.css" rel="stylesheet">


  <link rel="stylesheet" type="text/css" href="assets/bootstrap/css/jquery.dataTables.min.css">
  <link rel="stylesheet" type="text/css" href="assets/bootstrap/css/dataTables.bootstrap.min.css">
  <!--<link rel="stylesheet" type="text/css" href="assets/bootstrap/css/bootstrap.min.css">-->
  <script src="assets/bootstrap/js/jquery-1.12.4.js" type="text/javascript"></script>
  <script src="assets/bootstrap/js/jquery.dataTables.min.js" type="text/javascript"></script>

  <script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js"></script>


  <!-- <style>
    body {
      /* margin: 20px auto; */
      font-family: 'Lato';
      font-weight: 300;
      /* width: 85%; */
      font: 1em sans-serif;
      background: white;
      font-size: 10pt;
      /* text-align: center; */
    }

    button {
      background: cornflowerblue;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      font-family: 'Lato';
      margin: 5px;
      text-transform: uppercase;
      cursor: pointer;
      outline: none;
    }

    button:hover {
      background: orange;
    }

    .msg {
      color: green;
    }
  </style> -->
</head>

<body>
  <div class="card">
    <div class="container">
    </div>
    <div class="card-body">
      <!-- page content -->
      <div class="right_col" role="main">
        <br />
        <br />
        <div class="page-title">
          <div>
            <p style="font-size: 20px; font-weight: bold; color:#1976D2;">User Manage</p>
          </div>
          <div style="background-color: #ff0000;height: 2px">&nbsp;</div>
          <br />
          <!-- <div class="title_left">
              <h4 class="card-header" style="color: black;">User Manage</h4>
            </div> -->
        </div>
        <div class="col-md-12">
          <div class="col-md-2"></div>
          <div class="col-md-6">
            <div class="card" style="text-align: center;">
              <?php if (isset($_SESSION['message'])) : ?>
                <div class="alert alert-success">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  <strong>Success!</strong>
                  <?php
                  echo $_SESSION['message'];
                  unset($_SESSION['message']);
                  ?>
                </div>
              <?php endif ?>
            </div>
          </div>

        </div>
        <div class="row">
          <div class="col-md-12 col-sm-12 col-xs-12">
            <!-- /Start Body page -->
            <form method="post" action="admin.php">
            <!-- <form method="post" action="config.inc.php?signin"> -->
              <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Name</label>
                <div class="col-sm-7">
                  <input class="form-control" type="text" name="name" value="" required>
                </div>
              </div>

              <div class="form-group row">
                <label for="password" class="col-sm-2 col-form-label">Password</label>
                <div class="col-sm-7">
                  <input class="form-control" type="text" name="password_hash" value="" required>
                </div>
              </div>
              <div class="form-group row" style="text-align: center;">
                <button class="btn" type="submit" class="btn btn-primary" name="signin">Sign In</button>
              </div>
            </form>
            <!-- /end Body page -->
          </div>
        </div>
      </div>
      <!-- /page content -->
    </div>
  </div>
</body>

</html>