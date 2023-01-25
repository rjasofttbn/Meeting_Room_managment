<?php
// Database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mrbs";
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set("Asia/Dhaka");
// Set session
session_start();
if (isset($_POST['records-limit'])) {
  $_SESSION['records-limit'] = $_POST['records-limit'];
}


$result = $conn->prepare("SELECT `id`,  `create_by`, `name`, `description`, `start_time`, `end_time`, `timestamp`,`status` FROM `mrbs_entry` order by id desc");
$result->execute();

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
  // $dat = $_GET['request_date'];
  // $date = $dat.' 11:59:59';

  $result = $conn->prepare("SELECT `id`,  `create_by`, `name`, `description`, `start_time`, `end_time`, `timestamp`,`status` FROM `mrbs_entry` where create_by like '%$name%' or  name like '%$name%'");
  // print_r($date);
  // $todaysDate = date('Y-m-d');
  // $result = $conn->prepare("SELECT `id`,  `create_by`, `name`, `description`, `start_time`, `end_time`, `timestamp`,`status` FROM `mrbs_entry` where timestamp = date_format('%Y-%m-%d H:i:s',$date)");
  // print_r($result);
  // date_format(timestamp,'%Y-%m-%d') = $date");
  // create_by like '%$name%' or  name like '%$name%' or
  // or  datetime LIKE '%$todaysDate%'");
  // =date('Y-m-d', strtotime($date))
  $result->execute();
}

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300">
  <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@7.12.15/dist/sweetalert2.min.css"> -->

  <!-- jQuery -->

  <title>PHP</title>
  <style>
    /* .container {
      max-width: 1000px
    }

    .custom-select {
      max-width: 150px
    } */
    body {
      margin: 20px auto;
      font-family: 'Lato';
      font-weight: 300;
      width: 85%;
      font: 1em sans-serif;
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
  </style>
</head>

<body>
  <?php if (isset($_SESSION['message'])) : ?>
    <div class="msg">
      <?php
      echo $_SESSION['message'];
      unset($_SESSION['message']);
      ?>
    </div>
  <?php endif ?>
  <div class="card">
    <h5 class="card-header">Meeting room request manage</h5>
    <div class="card-body">
      <div style=" text-align: center;">
        <!-- <form action="help.php?search; ?>">
          <input type="text" name="serarch">
          <button>Search</button>
        </form> -->

        <!-- <form action="help.php?search" method="POST">
        Search: <input type="text" name="term" /><br />
      <input type="submit" name="search" value="search" />
      </form> -->
        <form class="form-inline" method="GET" action="help.php">
          <input type="text" name="name" class="form-control" placeholder="Search name..">
          <!-- &nbsp; -->
          <!-- <input type="date" class="form-control datepicker" name="request_date"> -->

          <button type="submit" name="search" value="search" class="btn btn-success">Search</button>
        </form>
      </div><br />
      <!-- Datatable -->
      <table class="table table-bordered mb-5">
        <thead>
          <tr class="table-success">
            <th>No.</th>
            <!-- <th>ID</th> -->
            <th>Request by</th>
            <th>Program name</th>
            <th>Start time</th>
            <th>End time</th>
            <!-- <th>Create Date</th> -->
            <th>Status</th>
            <th style="text-align: center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          for ($i = 0; $row = $result->fetch(); $i++) {
          ?>
            <tr style="text-align: left;">
              <input type="hidden" id="data_id" name="data_id" value="$row['id']">
              <td><?php echo $i + 1; ?></td>
              <input type="hidden" value="update" name="type">
              <!-- <td><?php echo $row['id']; ?></td> -->
              <td><?php echo $row['create_by']; ?></td>
              <td><?php echo $row['name']; ?></td>
              <td>
                <?php echo date('d/m/Y H:i a', $row['start_time']); ?>
              </td>
              <td><?php echo date('d/m/Y H:i a', $row['end_time']); ?>
              </td>
              <!-- <td><?php echo $row['timestamp']; ?></td> -->
              <td>
                <?php
                if ($row['status'] == 0) {
                  echo 'Requested';
                } elseif ($row['status'] == 1) {
                  echo 'Approved';
                } elseif ($row['status'] == 2) {
                  echo 'Reject';
                }
                ?>
              </td>
              <td style="text-align: center;">
                <a class="btn btn-success" Onclick="return ConfirmApprove();" href="help.php?approve=<?php echo $row['id']; ?>" role="button">Approve</a>
                <!-- <a class="btn btn-success " onclick="confirm('Are you sure?')" href="help.php?approve=<?php echo $row['id']; ?>" role="button">Approve</a> -->
                <!-- <button action="submit" role="button" src="help.php?approve=<?php echo $row['id']; ?>" class="btn btn-success first">First Alert</button> -->
                <a class="btn btn-danger" Onclick="return ConfirmReject();" href="help.php?reject=<?php echo $row['id']; ?>" role="button">Rject</a>

              </td>
            </tr>
          <?php } ?>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <!-- jQuery + Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</body>
<!-- Script -->
<script type="text/javascript">
  function ConfirmApprove() {
    return confirm("Are you sure you want to approve?");
  }

  function ConfirmReject() {
    return confirm("Are you sure you want to reject?");
  }

</script>
<script>
$('.datepicker').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: new Date() // disabling past dates
});
</script>
</html>