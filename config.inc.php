<?php // -*-mode: PHP; coding:utf-8;-*-
namespace MRBS;

namespace MRBS\Session;

use MRBS\Form\FieldDiv;
use MRBS\Form\Form;
use MRBS\Form\ElementA;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\User;

$form = array();

// Get non-standard form variables
// foreach (array('action', 'username', 'password', 'target_url', 'returl') as $var) {
//    $this->form[$var] = \MRBS\get_form_var($var, 'string', null, INPUT_POST);
// }
// print_r($this->form['username']); exit;
//    if (isset($this->form['username'])) {
//       // It's easy for extra spaces to appear, especially on a mobile device
//       $this->form['username'] = trim($this->form['username']);
//    }


// use PDO;
// // Database
// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "mrbs";
// $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
// $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// date_default_timezone_set("Asia/Dhaka");


/**************************************************************************
 *   MRBS Configuration File
 *   Configure this file for your site.
 *   You shouldn't have to modify anything outside this file.
 *
 *   This file has already been populated with the minimum set of configuration
 *   variables that you will need to change to get your system up and running.
 *   If you want to change any of the other settings in systemdefaults.inc.php
 *   or areadefaults.inc.php, then copy the relevant lines into this file
 *   and edit them here.   This file will override the default settings and
 *   when you upgrade to a new version of MRBS the config file is preserved.
 *
 *   NOTE: if you include or require other files from this file, for example
 *   to store your database details in a separate location, then you should
 *   use an absolute and not a relative pathname.
 **************************************************************************/

/**********
 * Timezone
 **********/

// The timezone your meeting rooms run in. It is especially important
// to set this if you're using PHP 5 on Linux. In this configuration
// if you don't, meetings in a different DST than you are currently
// in are offset by the DST offset incorrectly.
//
// Note that timezones can be set on a per-area basis, so strictly speaking this
// setting should be in areadefaults.inc.php, but as it is so important to set
// the right timezone it is included here.
//
// When upgrading an existing installation, this should be set to the
// timezone the web server runs in.  See the INSTALL document for more information.
//
// A list of valid timezones can be found at http://php.net/manual/timezones.php
// The following line must be uncommented by removing the '//' at the beginning
$timezone = "Asia/Dhaka";


/*******************
 * Database settings
 ******************/
// Which database system: "pgsql"=PostgreSQL, "mysql"=MySQL
$dbsys = "mysql";
// Hostname of database server. For pgsql, can use "" instead of localhost
// to use Unix Domain Sockets instead of TCP/IP. For mysql "localhost"
// tells the system to use Unix Domain Sockets, and $db_port will be ignored;
// if you want to force TCP connection you can use "127.0.0.1".
$db_host = "localhost";
//$db_host = "127.0.0.1";
// If you need to use a non standard port for the database connection you
// can uncomment the following line and specify the port number
$db_port = 3306;
// $db_port = 1234;
// Database name:
$db_database = "mrbs";
// Schema name.  This only applies to PostgreSQL and is only necessary if you have more
// than one schema in your database and also you are using the same MRBS table names in
// multiple schemas.
//$db_schema = "public";
// Database login user name:
$db_login = "root";
// Database login password:
$db_password = '';
// Prefix for table names.  This will allow multiple installations where only
// one database is available
$db_tbl_prefix = "mrbs_";
// Set $db_persist to TRUE to use PHP persistent (pooled) database connections.  Note
// that persistent connections are not recommended unless your system suffers significant
// performance problems without them.   They can cause problems with transactions and
// locks (see http://php.net/manual/en/features.persistent-connections.php) and although
// MRBS tries to avoid those problems, it is generally better not to use persistent
// connections if you can.
$db_persist = FALSE;

/* Add lines from systemdefaults.inc.php and areadefaults.inc.php below here
   to change the default configuration. Do _NOT_ modify systemdefaults.inc.php
   or areadefaults.inc.php.  */



// if (isset($_GET['signin'])) {
//    // print_r('ll');
//    // exit;
//    $result = $conn->prepare("SELECT id, `name`,`role`, `email`,password_hash from mrbs_users 
// where name ='Meher' ");

//    $result->execute();
//    $row = $result->fetch();
//    $name = $row['name'];
//    $auth["session"] = "php";
//    $auth["type"] = "config";

//    if ($row['role'] == 'admin') {
//       $auth["admin"][] = $name;
//       $auth["user"]["$name"] = $name;
//    } elseif ($row['role'] == 'user') {
//       $auth["user"]["$name"] = $name;
//       $auth["user"][] = $name;
//    }
// }



//  if (isset($this->form['username'])) {
//    // It's easy for extra spaces to appear, especially on a mobile device
//    $this->form['username'] = trim($this->form['username']);
//  }
//  print_r('00'); exit; 
// if (isset($this->form['action'])) {
 
// }


// $auth["admin"][] = "Shahin";
// //$auth["admin"][] = "Omar";
// $auth["session"] = "php";
// $auth["type"] = "config";
// $auth["user"]["Shahin"] = "Shahin";
// $auth["user"]["Omar"] = "Omar";
// $auth["user"]["Meher"] = "Meher";
