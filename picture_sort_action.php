<?php

// File: picture_sort_action.php
// Author: Bill MacAllister
// Date: October 2002

require ('inc_page_open.php');

// ----------------------------------------------------
// Main Routine

require('mysql.php');

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
  $msg = $msg . "<br>Error connecting to MySQL host $mysql_host";
  echo "$msg";
  exit;
}
$result = mysql_select_db($mysql_db);
if (!$result) {
  $msg = $msg . "<br>Error connecting to MySQL db $mysql_db";
  echo "$msg";
  exit;
}

$now = date ('Y-m-d H:i:s');
$up_date_last_maint = $now;

// set update message area
if (!session_is_registered('s_msg')) {session_register('s_msg');}
$_SESSION['s_msg'] = '';
$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for updates only

if ( strlen($btn_update)>0 ) {

  $flds['description'] = 's';
  $flds['date_taken']  = 's';
  $flds['key_words']   = 's';
  $flds['taken_by']    = 's';

  for ($i=0; $i<$up_picture_cnt; $i++) {

    $cmd = "date_last_maint='$up_date_last_maint'";
    $update_cnt = 0;

    $up_name = "up_pid_$i"; $up_pid = $$up_name;

    // Try and get the old user record
    $sel = "SELECT date_taken, key_words, taken_by ";
    $sel .= "FROM pictures WHERE pid=$up_pid ";
    $result = mysql_query ($sel,$cnx);
    if ($result) {
      $row = mysql_fetch_array($result);
      foreach ($flds as $fld => $type) {
        $up_name = "up_${fld}_${i}"; 
        $up_val = trim($$up_name);
        $db_val = trim($row[$fld]);
        if ("$up_val" != "$db_val") {
          $cmd .= ", $fld='$up_val' ";
          $update_cnt++;
          $_SESSION['s_msg'] .= "<font $ok>$fld updated.</font><br>";
        }
      }
    }

    if ($update_cnt>0) {
      // Make the changes to pride_webrpt_users
      $sql_cmd = "UPDATE pictures SET $cmd ";
      $sql_cmd .= "WHERE pid = $up_pid ";
      $result = mysql_query ($sql_cmd,$cnx);
      $_SESSION['s_msg'] .= "<font $ok>Update complete for $up_pid</font><br>\n";
    }
  }

} else {

  echo "Ooops, this should never happen!<br>\n";

}

mysql_close ($cnx);

header ("REFRESH: 0; URL=picture_sort.php");

?>
