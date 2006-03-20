<?PHP
// -------------------------------------------------------------
// group_maint.php
// author: Bill MacAllister
// date: December 31, 2001
//

require ('inc_page_open.php');

// -- Print a space or the field
function prt ($fld) {
  $str = trim ($fld);
  if (strlen($str) == 0) {
    $str = "&nbsp;";
  } 
  return $str;
}

//-------------------------------------------------------------
// Start of main processing for the page

require('mysql.php');

// connect to the database
$conn = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$conn) {
  $_SESSION['s_msg'] .= "Error connecting to MySQL host $mysql_host<br>";
}
$cnx = mysql_select_db($mysql_db);
if (!$cnx) {
  $_SESSION['s_msg'] .= "Error connecting to MySQL db $mysql_db<br>";
}
if (isset($in_group_id)) {
  if ($in_group_id=='CLEARFORM') {
    $add_flag = 1;
    unset ($in_group_id);
  }
} else {
  $in_group_id = '';
}

$sel = "SELECT * ";
$sel .= "FROM groups ";
$sel .= "WHERE group_id = '$in_group_id' ";
$result = mysql_query ($sel);
if ($result) {
  $row = mysql_fetch_array($result);
}
if ( isset($in_group_id) && !isset($row["group_id"]) ) {
   $_SESSION['s_msg'] .= "Group '$in_group_id' not found.<br>\n";
}
?>

<html>
<head>
<title>Group Maintenance</title>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Update Group';
require ('page_top.php');
?>

<div align="center">
<form name="find_group" action="<?php print $PHP_SELF;?>" method="post">
<table border="1">
<tr>
  <td align="right">Group ID:</td>
  <td><input type="text" name="in_group_id" value="<?php print $in_group_id;?>">
  </td>
</tr>
<tr>
  <td align="center" colspan="2">
  <input type="submit" name="btn_find" value="Find">
  </td>
</tr>
<?php 
if (isset($_SESSION['s_msg'])) { 
  if (strlen($_SESSION['s_msg'])>0) { 
?>
<tr><td bgcolor="#ffffff" align="center" colspan="2">
    <font color="#ff0000"><?php print $_SESSION['s_msg'];?></font>
    </td>
</tr>
<?php 
  $_SESSION['s_msg'] = '';
  } 
}?>
</table>
</form>

<p> 

<form name="group_data" 
      action="group_maint_action" 
      method="post">
<table border="1">
<tr>
 <td colspan="2">
 <table border="0" width="100%">
 <tr>
   <td> 
    &nbsp;
   </td>
   <td align="right">
    <a href="group_maint.php?in_group_id=CLEARFORM">Clear Form</a>
   </td>
  </tr>
  </table>
  </td>
</tr>
<tr>
 <td align="right">Group ID:</td>
 <td> <input type="text" name="in_group_id"
             value="<?php print $row["group_id"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Group Name:</td>
 <td> <input type="text" name="in_group_name"
             value="<?php print $row["group_name"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Description:</td>
 <td>
<TEXTAREA name="in_group_description" rows="5" cols="40">
<?php print $row["group_description"];?>
</TEXTAREA>
 </td>
</tr>
</table>
<p>
<table border="1">

<?php

$people_cnt = 0;
if (strlen($in_group_id) > 0) {
  $cmd = "SELECT g.uid uid, ";
  $cmd .= "p.display_name name ";
  $cmd .= "FROM picture_groups g ";
  $cmd .= "LEFT OUTER JOIN people_or_places p ";
  $cmd .= "ON (g.uid = p.uid) "; 
  $cmd .= "WHERE g.group_id = '$in_group_id' ";
  $cmd .= "ORDER BY g.uid ";
  $result = mysql_query ($cmd);
  if ($result) {
    while ($link_row = mysql_fetch_array($result)) {
      $a_uid = $link_row["uid"];
      $a_name = $link_row["name"];
      $found["$a_uid"] = 1;
      if ($people_cnt == 0) {
        echo "<tr><th colspan=\"2\">People to Remove from Group</th></tr>\n";
        echo "<tr>\n";
        echo " <td align=\"right\">People to Remove:</td>\n";
        echo " <td>\n";
        echo "  <select name=\"in_deluids[]\" multiple>\n";
      }
      $people_cnt++;
      echo "   <option value=\"$a_uid\">$a_name\n";
    }
    if ($people_cnt > 0) {
      echo "</select>\n";
      echo " </td>\n";
      echo "<tr>\n";
    }
  }  
}
?>

<tr><th colspan="2">People to Add to Group</th></tr>
<?php
$cmd = "SELECT uid,display_name ";
$cmd .= "FROM people_or_places ";
$cmd .= "ORDER BY display_name ";
$add_cnt = 0;
$result = mysql_query ($cmd);
if ($result) {
  while ($person_row = mysql_fetch_array($result)) {
    $a_uid = trim($person_row["uid"]);
    if (isset($found["$a_uid"])) {continue;}
    $a_name = $person_row["display_name"];
    if ($add_cnt == 0) {
      echo "<tr>\n";
      echo " <td align=\"right\">People to Add:</td>\n";
      echo " <td>\n";
      echo "  <select name=\"in_newuids[]\" multiple>\n";
    }
    $add_cnt++;
    echo "   <option value=\"$a_uid\">$a_name\n";
  }
  if ($add_cnt > 0) {
    echo "</select>\n";
    echo " </td>\n";
    echo "<tr>\n";
  }
}
?>

<tr>
 <td align="right">Date Last Maint:</td>
 <td> <?php print $row["date_last_maint"]; ?> </td>
</tr>
<tr>
 <td align="right">Date Last Added:</td>
 <td> <?php print $row["date_added"]; ?> </td>
</tr>
<tr><td colspan="2">
  <table border="0" width="100%">
  <tr>
   <td>
      <input type="submit" name="btn_add" value="Add">
   </td>
   <td align="center">
      <input type="submit" name="btn_update" value="Update">
   </td>
   <td align="right">
      <input type="submit" name="btn_delete" value="Delete">
   </td>
  </tr>
  </table>
</td></tr>

</table>
</form>

<?php require('page_bottom.php'); ?>
</body>
</html>
