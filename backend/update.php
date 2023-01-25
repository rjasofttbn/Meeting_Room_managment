<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mrbs";
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// print_r('pp');


if (isset($_GET['approve'])) {
	$id = $_GET['approve'];
	$update = true;
  
	$sql = "UPDATE mrbs_entry SET status=1 WHERE id=$id";
	$sth = $conn->prepare($sql);
	$sth->execute();
	$_SESSION['message'] = "The record is approved!";
	header('location: help.php');
  }
  
  if (isset($_GET['reject'])) {
	$id = $_GET['reject'];
	$update = true;
	$sql = "UPDATE mrbs_entry SET status=2 WHERE id=$id";
	$sth = $conn->prepare($sql);
	$sth->execute();
	$_SESSION['message'] = "The record is rejected!";
	header('location: help.php');
  }

  if (isset($_GET['search'])) {
	
	$id = $_GET['search'];
	$name = $_GET['name'];
//   
	// $search = true;
	$result = $conn->prepare("SELECT `id`,  `create_by`, `name`, `description`, `start_time`, `end_time`, `timestamp`,`status` FROM `mrbs_entry` where create_by ='$name'");
	// print_r($result); exit;
	$result->execute();
	// print_r($id);
	header('location: help.php');
	// header("location:javascript://history.go(-1)");
  }

?>