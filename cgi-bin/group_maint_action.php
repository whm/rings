<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_fld  = $_REQUEST['in_fld'];
$in_val  = $_REQUEST['in_val'];
$in_group_id  = $_REQUEST['in_group_id'];
$in_date_added  = $_REQUEST['in_date_added'];
$in_type  = $_REQUEST['in_type'];
$in_date_last_maint  = $_REQUEST['in_date_last_maint'];
$in_group_uid  = $_REQUEST['in_group_uid'];
$in_deluids  = $_REQUEST['in_deluids'];
$in_uid  = $_REQUEST['in_uid'];
$in_newuids  = $_REQUEST['in_newuids'];
$in_button_add = $_REQUEST['in_button_add'];
$in_button_update = $_REQUEST['in_button_update'];
$in_button_delete = $_REQUEST['in_button_delete'];
// ----------------------------------------------------------
//

// File: people_maint_action.php
// Author: Bill MacAllister
// Date: 31-Dec-2001

require ('inc_page_open.php');

//-------------------------------------------------------------
// construct flds and vals for an insert
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function mkin ($a_fld, $a_val, $in_type) {

  global $flds, $vals;

  $a_val = trim ($a_val);
  $c = "";
  if (strlen($flds) > 0) {$c = ",";}
  $flds = $flds . $c . $a_fld;
  if ( $in_type != "n" ) {
    $a_val = str_replace ("'", "\\'", $a_val);
    $vals = $vals . $c . "'$a_val'";
  } else {
    $vals = $vals . $c . $a_val;
  }
  return;
}

// ----------------------------------------------------
// Main Routine

require ('/etc/whm/rings_dbs.php');

$now = date ('Y-m-d H:i:s');
$in_date_last_maint = $now;
$in_date_added = $now;
$msg = '';

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
  $msg .= $msg . "<br>Error connecting to MySQL host $mysql_host";
  echo "$msg";
}
$result = mysql_select_db($mysql_db);
if (!$result) {
  $msg .= $msg . "<br>Error connecting to MySQL db $mysql_db";
  echo "$msg";
}

// No spaces allowed in the identifier
$in_uid = pref_replace ('/\s+/', '', $in_uid);

// how to get back
$next_url = "group_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";
$next_group_id = $in_group_id;

// set update message area
$_SESSION['s_msg'] = '';
$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete

$update_flag = $add_flag = 0;

if ( isset($in_button_update) ) {

  // Try and get the old user record
  $sel = "SELECT * FROM groups WHERE group_id='$in_group_id'";
  $result = mysql_query ($sel,$cnx);
  if ($result) {
    $row = mysql_fetch_array ($result);
    $this_group = $row['group_id'];
    $fld_cnt = mysql_num_fields($result);
  }

  $update_flag = 1;
  $add_flag = 0;
  if (!isset($this_group)) {
    // no old record, they must want a new one for this id
    $add_flag = 1;
    $update_flag = 0;
  }
}

if ( $update_flag ) {

  // -- Change an Existing record ----------------------

  $comma = '';
  $cmd = '';
  $update_cnt = 0;

  $up_msg = '';
  for ($i=0; $i<$fld_cnt; $i++) {
    $db_fld = mysql_field_name ($result, $i);
    if ($db_fld == "date_added") {continue;}
    $in_fld = "in_$db_fld";
    $in_val = trim($$in_fld);
    if (trim($in_val) != trim($row[$db_fld])) {
      $in_val = str_replace ("'", "\\'", $in_val);
      $cmd .= "$comma $db_fld='$in_val' ";
      $comma = ',';
      $update_cnt++;
      $up_msg .= "<font $ok>$db_fld updated.</font><br>";
    }
  }

  if ($update_cnt>1) {
    // Make the changes
    $sql_cmd = "UPDATE groups SET $cmd ";
    $sql_cmd .= "WHERE group_id = '$this_group'";
    $result = mysql_query ($sql_cmd,$cnx);
    $_SESSION['s_msg'] .= $up_msg;
  }
  
  // -- add people to group 

  if (is_array($in_newuids)) {
    foreach ($in_newuids as $i => $a_uid) {
      $flds = '';
      $vals = '';
      mkin ('group_id',        $in_group_id,        's');
      mkin ('uid',             $a_uid,              's');
      mkin ('date_last_maint', $in_date_last_maint, 's');
      mkin ('date_added',      $in_date_added,      's');
      $sql_cmd = "INSERT INTO picture_groups ($flds) VALUES ($vals)";
      $result = mysql_query ($sql_cmd,$cnx);
      $_SESSION['s_msg'] .= "<font $ok>'$a_uid' added </font><br>";
    }
  }

  // -- delete people from group 

  if (is_array($in_deluids)) {
    foreach ($in_deluids as $i => $a_uid) {
      $sql_cmd = "DELETE FROM picture_groups ";
      $sql_cmd .= "WHERE group_id='$in_group_id' ";
      $sql_cmd .= "AND uid='$a_uid' ";
      $result = mysql_query ($sql_cmd,$cnx);
      $_SESSION['s_msg'] .= "<font $ok>'$a_uid' removed </font><br>";
    }
  }

} elseif ( $add_flag || (isset($in_button_add)) ) {

  // -- Add a new record -------------------------------

  $sel = "SELECT group_id FROM groups WHERE group_id='$in_group_uid'";
  $result = mysql_query ($sel,$cnx);
  if ($result) {
    $row = mysql_fetch_array ($result);
    $this_group = $row['group_id'];
  }

  if ( strlen($this_group) > 0) {
    $_SESSION['s_msg'] .= "Group already exists!<br>New entry NOT Added.<br>";
  } else {

    // -- create the group
    $flds = '';
    $vals = '';
    $result = mysql_list_fields ($mysql_db ,'groups');
    $fld_cnt = mysql_num_fields ($result);
    for ($i=0; $i<$fld_cnt; $i++) {
      $db_fld = mysql_field_name ($result, $i);
      $form_fld = "in_$db_fld";
      $in_val = trim($$form_fld);
      mkin ($db_fld, $in_val, 's');
    }
    $sql_cmd = "INSERT INTO groups ($flds) VALUES ($vals)";
    $result = mysql_query ($sql_cmd,$cnx);
    $_SESSION['s_msg'] .= "<font $ok>Group '$in_group_id' added </font><br>";

    // -- add people to group 

    if (is_array($in_newuids)) {
      foreach ($in_newuids as $i => $a_uid) {
        $flds = '';
        $vals = '';
        mkin ('group_id',        $in_group_id,        's');
        mkin ('uid',             $a_uid,              's');
        mkin ('date_last_maint', $in_date_last_maint, 's');
        mkin ('date_added',      $in_date_added,      's');
        $sql_cmd = "INSERT INTO picture_groups ($flds) VALUES ($vals)";
        $result = mysql_query ($sql_cmd,$cnx);
        $_SESSION['s_msg'] .= "<font $ok>'$a_uid' added </font><br>";
      }
    }
  }
  $next_uid = $in_uid;

} elseif ( isset($in_button_delete) ) {

  // -- Delete a record -------------------------------

  $sql_cmd = "DELETE FROM groups WHERE group_id='$in_group_id'";
  $result = mysql_query ($sql_cmd,$cnx);
  if ($result) {
    $_SESSION['s_msg'] .= "<font $ok>Group '$in_group_id' dropped ";
    $_SESSION['s_msg'] .= "from people.</font><br>";
  } else {
    $_SESSION['s_msg'] .= "Problem deleting $in_group_id<br>";
    $_SESSION['s_msg'] .= "Problem SQL: $sql_cmd<br>";
  }
  $sql_cmd = "DELETE FROM picture_groups WHERE group_id='$in_group_id'";
  $result = mysql_query ($sql_cmd,$cnx);
  if ($result) {
    $_SESSION['s_msg'] .= "<font $ok>Picture references dropped ";
    $_SESSION['s_msg'] .= "from people.</font><br>";
  } else {
    $_SESSION['s_msg'] .= "Problem deleting $in_group_id<br>";
    $_SESSION['s_msg'] .= "Problem SQL: $sql_cmd<br>";
  }
  $next_group_id = 'CLEARFORM';

} else {

  echo "Ooops, this should never happen!<br>\n";

}

mysql_close ($cnx);

header ("$next_header?in_group_id=$next_group_id");

?>
