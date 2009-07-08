<?php

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
    $vals = $vals . $c . sql_quote($a_val, $in_type);
  } else {
    $vals = $vals . $c . $a_val;
  }
  return;
}

//-------------------------------------------------------------
// quote a value for storage
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function sql_quote ($a_val, $in_type) {

  $ret = trim ($a_val);
  if ( $in_type != "n" ) {
    $ret = "'" . str_replace("'", "\'", $ret) . "'";
  }
  return $ret;

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
$in_uid = ereg_replace (" ","",$in_uid);

// how to get back
$next_url = "people_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";

$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete

$update_flag = $add_flag = 0;

if ( isset($btn_update) ) {

  // Try and get the old user record
  $sel = "SELECT * FROM people_or_places WHERE uid='$in_uid'";
  $result = mysql_query ($sel,$cnx);
  if ($result) {
    $row = mysql_fetch_array ($result);
    $this_user = $row['uid'];
    $fld_cnt = mysql_num_fields($result);
  }

  $update_flag = 1;
  $add_flag = 0;
  if (!isset($this_user)) {
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
    if ($db_fld == "date_of_birth") {continue;}
    if ($db_fld == "date_added")    {continue;}
    $in_fld = "in_$db_fld";
    $in_val = trim($$in_fld);
    if ( get_magic_quotes_gpc() ) {$in_val = stripslashes($in_val);}
    if (trim($in_val) != trim($row[$db_fld])) {
      $in_val = str_replace ("'", '\'', $in_val);
      $cmd .= "$comma $db_fld=".sql_quote($in_val,'s')." ";
      $comma = ',';
      $update_cnt++;
      $up_msg .= "<font $ok>$db_fld updated.</font><br>";
    }
  }

  if ($update_cnt>1) {
    // Make the changes
    $sql_cmd = "UPDATE people_or_places SET $cmd ";
    $sql_cmd .= "WHERE uid = '$this_user'";
    $result = mysql_query ($sql_cmd,$cnx);
    $_SESSION['s_msg'] .= $up_msg;
  }
  $next_uid = $in_uid;
  
} elseif ( $add_flag || (isset($btn_add)) ) {

  // -- Add a new record -------------------------------

  $sel = "SELECT uid FROM people_or_places WHERE uid='$in_uid'";
  $result = mysql_query ($sel,$cnx);
  if ($result) {
    $row = mysql_fetch_array ($result);
    $this_user = $row['uid'];
  }

  if ( strlen($this_user) > 0) {
    $_SESSION['s_msg'] .= "Person already exists!<br>New entry NOT Added.<br>";
  } else {
    $flds = '';
    $vals = '';
    $result = mysql_list_fields ($mysql_db,'people_or_places',$cnx);
    $fld_cnt = mysql_num_fields ($result);
    for ($i=0; $i<$fld_cnt; $i++) {
      $db_fld = mysql_field_name ($result, $i);
      $form_fld = "in_$db_fld";
      $in_val = trim($$form_fld);
      if ( get_magic_quotes_gpc() ) {$in_val = stripslashes($in_val);}
      mkin ($db_fld, $in_val, 's');
    }

    $sql_cmd = "INSERT INTO people_or_places ($flds) VALUES ($vals)";
    $result = mysql_query ($sql_cmd,$cnx);
    $_SESSION['s_msg'] .= "<font $ok>Person '$in_uid' added ";
    $_SESSION['s_msg'] .= "to people.</font><br>";

  }
  $next_uid = $in_uid;

} elseif ( isset($btn_delete) ) {

  // -- Delete a record -------------------------------

  $sql_cmd = "DELETE FROM people_or_places WHERE uid='$in_uid'";
  $result = mysql_query ($sql_cmd,$cnx);
  if ($result) {
    $_SESSION['s_msg'] .= "<font $ok>Person '$in_uid' dropped ";
    $_SESSION['s_msg'] .= "from people.</font><br>";
  } else {
    $_SESSION['s_msg'] .= "Problem deleting $in_uid<br>";
    $_SESSION['s_msg'] .= "Problem SQL: $sql_cmd<br>";
  }
  $next_uid = 'CLEARFORM';

} else {

  echo "Ooops, this should never happen!<br>\n";

}

mysql_close ($cnx);

header ("$next_header?in_uid=$next_uid");

?>
