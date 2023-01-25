<?php

namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldInputDate;
use MRBS\Form\FieldInputSearch;
use MRBS\Form\FieldInputSubmit;
use PDO;
// Database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mrbs";
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set("Asia/Dhaka");
require "defaultincludes.inc";
// Set session
session_start();
if (isset($_POST['records-limit'])) {
  $_SESSION['records-limit'] = $_POST['records-limit'];
}
if (isset($_SESSION['user'])) {
  $username = $_SESSION['user']->username;
  $result = $conn->prepare("SELECT `mrbs_entry`.`id`, `mrbs_entry`.`create_by`, `mrbs_entry`.`name`,`mrbs_room`.`room_name`, `mrbs_area`.`area_name`,`mrbs_entry`.`description`, `mrbs_entry`.`start_time`, `mrbs_entry`.`end_time`, `mrbs_entry`.`timestamp`,`mrbs_entry`.`status`,`mrbs_entry_detail`.`updated_at` FROM `mrbs_entry` 
  left join `mrbs_entry_detail` on `mrbs_entry_detail`.`mrbs_entry_id` = `mrbs_entry`.`id` 
  left join `mrbs_room` on `mrbs_room`.`id` = `mrbs_entry`.`room_id` 
  left join `mrbs_area` on `mrbs_area`.`id` = `mrbs_room`.`area_id` 
  where `mrbs_entry`.`create_by` ='$username' order by id desc");

  $result->execute();
}
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $conn->exec("DELETE FROM mrbs_entry WHERE id = $id");
  $_SESSION['message'] = "The record is deleted!";
  header('location: requestStatus.php');
}


if (isset($_GET['search'])) {
  $id = $_GET['search'];
  $start = $_GET['date'] . ' 00:01:01';
  $end = $_GET['date'] . ' 23:59:59';
  if ($start == ' 00:01:01' && $end == ' 23:59:59') {
    $result = $conn->prepare("SELECT `mrbs_entry`.`id`, `mrbs_entry`.`create_by`, `mrbs_entry`.`name`,`mrbs_room`.`room_name`, `mrbs_area`.`area_name`,`mrbs_entry`.`description`, `mrbs_entry`.`start_time`, `mrbs_entry`.`end_time`, `mrbs_entry`.`timestamp`,`mrbs_entry`.`status`,`mrbs_entry_detail`.`updated_at` FROM `mrbs_entry` 
        left join `mrbs_entry_detail` on `mrbs_entry_detail`.`mrbs_entry_id` = `mrbs_entry`.`id` 
        left join `mrbs_room` on `mrbs_room`.`id` = `mrbs_entry`.`room_id` 
        left join `mrbs_area` on `mrbs_area`.`id` = `mrbs_room`.`area_id` 
        order by `mrbs_entry`.`id` desc");
  } else {

    $result = $conn->prepare("SELECT `mrbs_entry`.`id`, `mrbs_entry`.`create_by`, `mrbs_entry`.`name`,`mrbs_room`.`room_name`, `mrbs_area`.`area_name`,`mrbs_entry`.`description`, `mrbs_entry`.`start_time`, `mrbs_entry`.`end_time`, `mrbs_entry`.`timestamp`,`mrbs_entry`.`status`,`mrbs_entry_detail`.`updated_at` FROM `mrbs_entry` 
        left join `mrbs_entry_detail` on `mrbs_entry_detail`.`mrbs_entry_id` = `mrbs_entry`.`id` 
        left join `mrbs_room` on `mrbs_room`.`id` = `mrbs_entry`.`room_id` 
        left join `mrbs_area` on `mrbs_area`.`id` = `mrbs_room`.`area_id` 
        where `mrbs_entry`.`timestamp` > '$start' and `mrbs_entry`.`timestamp` < '$end' order by `mrbs_entry`.`id` desc");
  }
  $result->execute();
}
?>
<?php
function get_location_nav($view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';

  $html .= "<nav class=\"location js_hidden\">\n";  // JavaScript will show it
  $html .= make_area_select_html($view, $year, $month, $day, $area);

  if ($view !== 'day') {
    $html .= make_room_select_html($view, $view_all, $year, $month, $day, $area, $room);
  }

  $html .= "</nav>\n";

  return $html;
}


function get_view_nav($current_view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';
  $html .= '<nav class="view">';
  $html .= '<div class="container">';  // helps the CSS
  $views = array(
    'day' => 'nav_day',
    'week' => 'nav_week',
    'month' => 'nav_month'
  );

  foreach ($views as $view => $token) {
    $this_view_all = (isset($view_all)) ? $view_all : 1;

    $vars = array(
      'view'      => $view,
      'view_all'  => $this_view_all,
      'page_date' => format_iso_date($year, $month, $day),
      'area'      => $area,
      'room'      => $room
    );

    $query = http_build_query($vars, '', '&');
    $href = multisite("index.php?$query");
    $html .= '<a';
    $html .= ($view == $current_view) ? ' class="selected"' : '';
    $html .= ' href="' . htmlspecialchars($href) . '">' . htmlspecialchars(get_vocab($token)) . '</a>';
  }

  $html .= '</div>';
  $html .= '</nav>';

  return $html;
}


function get_arrow_nav($view, $view_all, $year, $month, $day, $area, $room)
{
  $html = '';

  switch ($view) {
    case 'day':
      $title_prev = get_vocab('daybefore');
      $title_this = get_vocab('gototoday');
      $title_next = get_vocab('dayafter');
      break;
    case 'week':
      $title_prev = get_vocab('weekbefore');
      $title_this = get_vocab('gotothisweek');
      $title_next = get_vocab('weekafter');
      break;
    case 'month':
      $title_prev = get_vocab('monthbefore');
      $title_this = get_vocab('gotothismonth');
      $title_next = get_vocab('monthafter');
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $title_prev = htmlspecialchars($title_prev);
  $title_next = htmlspecialchars($title_next);

  $link_prev = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, false);
  $link_today = get_today_link($view, $view_all, $area, $room);
  $link_next = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, true);

  $link_prev = multisite($link_prev);
  $link_today = multisite($link_today);
  $link_next = multisite($link_next);

  $html .= "<nav class=\"arrow\">\n";
  $html .= "<a class=\"prev\" title=\"$title_prev\" aria-label=\"$title_prev\" href=\"" . htmlspecialchars($link_prev) . "\"></a>";  // Content will be filled in by CSS
  $html .= "<a title= \"$title_this\" aria-label=\"$title_this\" href=\"" . htmlspecialchars($link_today) . "\">" . get_vocab('today') . "</a>";
  $html .= "<a class=\"next\" title=\"$title_next\" aria-label=\"$title_next\" href=\"" . htmlspecialchars($link_next) . "\"></a>";  // Content will be filled in by CSS
  $html .= "</nav>";

  return $html;
}


function get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room, $hidden = false)
{
  $html = '';

  $html .= "<nav class=\"main_calendar" .
    (($hidden) ? ' js_hidden' : '') .
    "\">\n";

  $html .= get_arrow_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_location_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $view_all, $year, $month, $day, $area, $room);

  $html .= "</nav>\n";

  return $html;
}


function get_date_heading($view, $year, $month, $day)
{
  global $strftime_format, $display_timezone,
    $weekstarts, $mincals_week_numbers;

  $html = '';
  $time = mktime(12, 0, 0, $month, $day, $year);

  $html .= '<h2 class="date">';

  switch ($view) {
    case 'day':
      $html .= utf8_strftime($strftime_format['view_day'], $time);
      break;

    case 'week':
      // Display the week number if required, provided the week starts on Monday,
      // otherwise it's spanning two ISO weeks and doesn't make sense.
      if ($mincals_week_numbers && ($weekstarts == 1)) {
        $html .= '<span class="week_number">' .
          get_vocab('week_number', date('W', $time)) .
          '</span>';
      }
      // Then display the actual dates
      $day_of_week = date('w', $time);
      $our_day_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % DAYS_PER_WEEK;
      $start_of_week = mktime(12, 0, 0, $month, $day - $our_day_of_week, $year);
      $end_of_week = mktime(12, 0, 0, $month, $day + 6 - $our_day_of_week, $year);
      // We have to cater for three possible cases.  For example
      //    Years differ:                   26 Dec 2016 - 1 Jan 2017
      //    Years same, but months differ:  30 Jan - 5 Feb 2017
      //    Years and months the same:      6 - 12 Feb 2017
      if (date('Y', $start_of_week) != date('Y', $end_of_week)) {
        $start_format = $strftime_format['view_week_start_y'];
      } elseif (date('m', $start_of_week) != date('m', $end_of_week)) {
        $start_format = $strftime_format['view_week_start_m'];
      } else {
        $start_format = $strftime_format['view_week_start'];
      }
      $html .= utf8_strftime($start_format, $start_of_week) . '-' .
        utf8_strftime($strftime_format['view_week_end'], $end_of_week);
      break;

    case 'month':
      $html .= utf8_strftime($strftime_format['view_month'], $time);
      break;

    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $html .= '</h2>';

  if ($display_timezone) {
    $html .= '<span class="timezone">';
    $html .= get_vocab("timezone") . ": " . date('T', $time) . " (UTC" . date('O', $time) . ")";
    $html .= '</span>';
  }

  return $html;
}


// Get non-standard form variables
$refresh = get_form_var('refresh', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

// The room select uses a negative value of $room to signify that we want to view all
// rooms in an area.   The absolute value of $room is the current room.
if ($room < 0) {
  $room = abs($room);
  $view_all = 1;
}

$is_ajax = is_ajax();

// If we're using the 'db' authentication type, check to see if MRBS has just been installed
// and, if so, redirect to the edit_users page so that they can set up users.
if (($auth['type'] == 'db') && (count(auth()->getUsers()) == 0)) {
  location_header('edit_users.php');
}

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), $refresh)) {
  exit;
}

// switch ($view)
// {
//   case 'day':
//     $inner_html = day_table_innerhtml($view, $year, $month, $day, $area, $room, $timetohighlight);
//     break;
//   case 'week':
//     $inner_html = week_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room, $timetohighlight);
//     break;
//   case 'month':
//     $inner_html = month_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room);
//     break;
//   default:
//     throw new \Exception("Unknown view '$view'");
//     break;
// }

if ($refresh) {
  echo $inner_html;
  exit;
}

// print the page header
$context = array(
  'view'      => $view,
  'view_all'  => $view_all,
  'year'      => $year,
  'month'     => $month,
  'day'       => $day,
  'area'      => $area,
  'room'      => isset($room) ? $room : null
);

print_header($context);

// echo "<div class=\"minicalendars\">\n";
// echo "</div>\n";

// echo "<div class=\"view_container js_hidden\">\n";
// echo get_date_heading($view, $year, $month, $day);
// echo get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room);
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
  <script>
    $(document).ready(function() {
      $('#example').DataTable({
        initComplete: function() {
          this.api().columns().every(function() {
            var column = this;
            var select = $('<select><option value=""></option></select>')
              .appendTo($(column.footer()).empty())
              .on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex(
                  $(this).val()
                );

                column
                  .search(val ? '^' + val + '$' : '', true, false)
                  .draw();
              });

            column.data().unique().sort().each(function(d, j) {
              select.append('<option value="' + d + '">' + d + '</option>')
            });
          });
        }
      });
    });
  </script>

  <style>
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
  </style>
</head>

<body>
  <div class="card">
    <div class="container">
    </div>
    <div class="card-body">
      <!-- page content -->
      <div class="right_col" role="main">
        <div class="">
          <br />
          <br />
          <div class="page-title">
            <div class="row">
              <div class="col-md-2" style="text-align:left;">
                <p style="font-size: 20px; font-weight: bold; color:#1976D2;">My request manage</p>
              </div>
              <div class="col-md-10" style="text-align:right;">
                <a href="http://localhost/mrbs/index.php" style="text-align: right;" title="Click and go home"><i class="fas fa-home"></i></a>
              </div>
            </div>
            <div style="background-color: #ff0000;height: 2px">&nbsp;</div>
            <br />
          </div>
          <div class="card">
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-4"></div>
                <div class="col-md-6">
                  <form class="form-inline" method="GET" action="requestStatus.php">
                    <input type="date" name="date" class="form-control">
                    <button type="submit" name="search" value="search" class="btn btn-success" style="background-color: #1976D2;"><i class="fa fa-search"></i></button>
                  </form>
                </div>
                <div class="col-md-2"></div>
              </div>

            </div>
            <br />
          </div>
          <div class="clearfix"></div>
          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
              <!-- /Start Body page -->
              <table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                  <tr class="table-success" style="color:black">
                    <th>No.</th>
                    <!-- <th>ID</th> -->
                    <th>Request by</th>
                    <th>Program name</th>
                    <th>Room name</th>
                    <th>Floor name</th>
                    <th>Start time</th>
                    <th>End time</th>
                    <th>Request Date</th>
                    <th>Response Date</th>
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
                      <td style="color:black;"><?php echo $i + 1; ?></td>
                      <input type="hidden" value="update" name="type">
                      <!-- <td><?php echo $row['id']; ?></td> -->
                      <td><?php echo $row['create_by']; ?></td>
                      <td><?php echo $row['name']; ?></td>
                      <td><?php echo $row['room_name']; ?></td>
                      <td><?php echo $row['area_name']; ?></td>
                      <td>
                        <?php echo date('d/m/Y H:i a', $row['start_time']); ?>
                      </td>
                      <td><?php echo date('d/m/Y H:i a', $row['end_time']); ?>
                      </td>
                      <td><?php echo date("d/m/Y H:i:s a", strtotime($row['timestamp'])); ?></td>
                      <td><?php
                          $presentDateTime = strtotime(date('Y-m-d H:i:s'));
                          if ($row['start_time'] < $presentDateTime) {
                            echo '<b style="color: orange; font-size:10pt;">Date time is over</b>';
                          ?>
                        <?php   } elseif ($row['start_time'] > $presentDateTime) { ?>
                          <?php
                            if ($row['updated_at'] > 0) {
                              echo date("d/m/Y  H:i:s a", strtotime($row['updated_at'])) ?? ' ';
                            } elseif ($row['start_time'] > $presentDateTime && $row['status'] == 0) {
                              echo 'Waiting for admin response';
                            } else {
                              echo 'Waiting for admin response';
                            }
                          ?>
                        <?php  } ?>
                      </td>
                      <td>
                        <?php
                        if ($row['status'] == 0) {
                          echo  '<b style="font-size:10pt;">Requested</style=>';
                        } elseif ($row['status'] == 1) {
                          echo  '<b style="color: green; font-size:10pt;">Approved</b>';
                        } elseif ($row['status'] == 2) {
                          echo  '<b style="color: red; font-size:10pt;">Reject</b>';
                        } elseif ($row['status'] == 4) {
                          echo '<b style="color: blue; font-size:10pt;">Tentative</b>';
                        }
                        ?>
                      </td>
                      <td style="text-align: center;">
                        <?php
                        $presentDateTime = strtotime(date('Y-m-d H:i:s'));
                        if ($row['start_time'] > $presentDateTime) {
                          if ($row['status'] == 0 || $row['status'] == 4) { ?>
                            <a class="btn btn-danger" data-toggle="tooltip" title="Click for delete request!" Onclick="return ConfirmDelete();" href="requestStatus.php?delete=<?php echo $row['id']; ?>" role="button"><i class="fa fa-trash"></i></a>
                          <?php    } else { ?>
                            <?php

                            echo "<a disabled='disabled' class='btn btn-danger'style=' pointer-events: none;
                                   cursor: default;'  href='#'><i class='fa fa-trash'></i></a>";
                            ?>
                          <?php  } ?>
                        <?php   } else { ?>
                          <?php
                          echo "<a disabled='disabled' class='btn btn-danger' style=' pointer-events: none;
                                    cursor: default;' Onclick='return ConfirmDelete();' href='#' role='button'><i class='fa fa-trash'></i></a>";
                          ?>
                        <?php  } ?>
                      </td>
                    </tr>
                  <?php } ?>
                  </tr>
                </tbody>
              </table>
              <!-- /end Body page -->
            </div>
          </div>
        </div>
      </div>
      <!-- /page content -->
    </div>
  </div>

  <!-- footer content -->

  <!-- /footer content -->

  <!-- jQuery -->
  <script src="vendors/jquery/dist/jquery.min.js"></script>
  <!-- Bootstrap -->
  <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
  <!-- FastClick -->
  <script src="vendors/fastclick/lib/fastclick.js"></script>
  <!-- NProgress -->
  <script src="vendors/nprogress/nprogress.js"></script>
  <!-- Datatables -->
  <script src="vendors/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
  <script src="vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
  <script src="vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
  <script src="vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
  <script src="vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
  <script src="vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
  <script src="vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
  <script src="vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
  <script src="vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
  <script src="vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
  <script src="vendors/jszip/dist/jszip.min.js"></script>
  <script src="vendors/pdfmake/build/pdfmake.min.js"></script>
  <script src="vendors/pdfmake/build/vfs_fonts.js"></script>
  <!-- Custom Theme Scripts -->
  <script src="build/js/custom.min.js"></script>
  <!-- Script -->
  <script type="text/javascript">
    function ConfirmDelete() {
      return confirm("Are you sure to delete this request?");
    }
  </script>

  <script>
    $(document).ready(function() {
      $('[data-toggle="tooltip"]').tooltip();
    });
  </script>

</body>

</html>