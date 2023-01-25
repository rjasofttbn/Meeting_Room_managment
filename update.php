<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mrbs";
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// $stmt = $conn->prepare("SELECT id, area_name, sort_key, timezone FROM mrbs_area");
// $stmt->execute();
// $result = $conn->prepare("SELECT `id`,  `create_by`, `name`, `description`, `start_time`, `end_time`, `timestamp`,`status` FROM `mrbs_entry`");
// $result->execute();
// print_r($_POST);
// print_r('here');



if(count($_POST)>0){
	if($_POST['type']==1){
		print_r('pp');
		$id=$_POST['id'];
	$status=$_POST['status'];
	
	$sql = "UPDATE `mrbs_entry` SET `status`='$status', WHERE id = $id";
	if (mysqli_query($conn, $sql)) {
		echo json_encode(array("statusCode"=>200));
	} 
	else {
		echo json_encode(array("statusCode"=>201));
	}
	mysqli_close($conn);
	}
}
if(count($_POST)>0){
    
	if($_POST['type']=='update'){
		// print_r($_POST);exit;
		$id=$_POST['id'];
		
		$sql = "UPDATE `mrbs_entry` SET `status`='2', WHERE id = $id";
		
		if ($conn->prepare($sql) === TRUE) {
			echo "Record updated successfully";
		  } else {
			// echo "Error updating record: " . $conn->error;
		  }
		  
		//   $conn->close();
		// if (mysqli_query($conn, $sql)) {
		// 	echo json_encode(array("statusCode"=>200));
		// } 
		// else {
		// 	echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		// }
		// mysqli_close($conn);
	}
	return;
}
if(count($_POST)>0){
	if($_POST['type']==3){
		$id=$_POST['id'];
		$sql = "DELETE FROM `crud` WHERE id=$id ";
		if (mysqli_query($conn, $sql)) {
			echo $id;
		} 
		else {
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		}
		mysqli_close($conn);
	}
}
if(count($_POST)>0){
	if($_POST['type']==4){
		$id=$_POST['id'];
		$sql = "DELETE FROM crud WHERE id in ($id)";
		if (mysqli_query($conn, $sql)) {
			echo $id;
		} 
		else {
			echo "Error: " . $sql . "<br>" . mysqli_error($conn);
		}
		mysqli_close($conn);
	}
}

?>