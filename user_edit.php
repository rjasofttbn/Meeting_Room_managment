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

    // $result = $conn->prepare("SELECT `id`, `name`, `email`,`password_hash` FROM `mrbs_users` WHERE id=$id");
    // $result->execute();
    // $row = $result->fetch();
}

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->prepare("SELECT `id`, `name`,`display_name`, `email`,`password_hash`,`role` FROM `mrbs_users` WHERE id=$id");
    $result->execute();
    $row = $result->fetch();
}

if (isset($_GET['update'])) {

    // initialize variables
    $id = $_GET['id'];
    $_GET['edit'] = $id;




    if ($_GET['role'] == 'admin') {
        $level = 2;
    } else {
        $level = 1;
    };

    $name = $_GET['name'];
    $email = $_GET['email'];
    $display_name = $_GET['display_name'];
    $role = $_GET['role'];
    $level = $level;
    $password_hash = $_GET['password_hash'];

    if (strlen($password_hash) < 8) {
        $_SESSION['message_error'] = "Password must be at least 8 characters";
        header('Location: ' . $_SERVER['PHP_SELF'].'?edit='.$id);
        die;
    }

    $update = true;
    $sql = "UPDATE mrbs_users SET name='$name', display_name='$display_name',level='$level', role='$role',email='$email',password_hash='$password_hash' WHERE id=$id";
    $sth = $conn->prepare($sql);
    $sth->execute();

    $_SESSION['message'] = "User update successfully";
    // ======after update=====
    $result = $conn->prepare("SELECT `id`, `name`,`display_name`,`role`, `email`,`password_hash` FROM `mrbs_users` WHERE id=$id");
    $result->execute();
    $row = $result->fetch();
    // header('location:user_edit.php?update_page=<?php echo $id');
    // header('location: user.php');
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
    <script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />

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

        form i {
            margin-left: -30px;
            cursor: pointer;
        }

        /* =============== */
    </style>
</head>

<body>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="col-md-2 col-sm-2 col-xs-2">
        </div>
        <!-- /Start Body page -->
        <div class="col-md-8 col-sm-8 col-xs-8">
            <div class="card">
                <br />
                <div class="card-body">
                    <div>
                        <p style="font-size: 20px; font-weight: bold; color:#1976D2;">Update User</p>
                    </div>
                    <div style="background-color: #ff0000;height: 2px">&nbsp;</div>
                    <br />
                    <div class="card">
                        <br />
                        <div class="card-body">
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
                                <?php if (isset($_SESSION['message_error'])) : ?>
                                    <div class="alert alert-danger">
                                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                        <strong>Duplicate!</strong>
                                        <?php
                                        echo $_SESSION['message_error'];
                                        unset($_SESSION['message_error']);
                                        ?>
                                    </div>
                                <?php endif ?>
                            </div>
                            <form method="get" action="user_edit.php?update">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">

                                <div class="form-group row">
                                    <label for="display_name" class="col-sm-2 col-form-label">Name</label>
                                    <div class="col-sm-7">
                                        <input class="form-control" type="text" name="display_name" value="<?php echo $row['display_name']; ?>" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="email" class="col-sm-2 col-form-label">Email</label>
                                    <div class="col-sm-7">
                                        <input class="form-control" type="email" name="email" value="<?php echo $row['email']; ?>" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">User Name</label>
                                    <div class="col-sm-7">
                                        <input class="form-control" type="text" name="name" value="<?php echo $row['name']; ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="password_hash" class="col-sm-2 col-form-label">Password</label>
                                    <div class="col-sm-7">
                                        <input minlength="8" class="form-control" type="Password" name="password_hash" id="password" value="<?php echo $row['password_hash']; ?>" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="email" class="col-sm-2 col-form-label">Role</label>
                                    <div class="col-sm-7">
                                        <select class="form-control" name="role" required>
                                            <option value="">Select role</option>
                                            <option <?= ($row['role'] == 'admin' ? 'selected=""' : '') ?> value="admin">Admin</option>
                                            <option <?= ($row['role'] == 'user' ? 'selected=""' : '') ?> value="user">User</option>
                                        </select>
                                    </div>
                                </div>


                                <div class="form-group row" style="text-align: center;">
                                    <button class="btn" type="submit" Onclick="return ConfirmUpdate();" class="btn btn-primary" name="update">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- /end Body page -->
        <div class="col-md-2 col-sm-2 col-xs-2">
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
        function ConfirmUpdate() {
            return confirm("Are you sure to update this user information?");
        }
    </script>

    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function() {
            // toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);

            // toggle the icon
            this.classList.toggle("bi-eye");
        });

        // prevent form submit
        const form = document.querySelector("form");
        form.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    </script>

</body>

</html>