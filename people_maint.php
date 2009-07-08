<?PHP
// -------------------------------------------------------------
// people_maint.php
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

require ('/etc/whm/rings_dbs.php');

// connect to the database
$conn = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$conn) {
  $_SESSION['s_msg'] .= "Error connecting to MySQL host $mysql_host<br>";
}
$cnx = mysql_select_db($mysql_db);
if (!$cnx) {
  $_SESSION['s_msg'] .= "Error connecting to MySQL db $mysql_db<br>";
}
if (isset($in_uid)) {
  if ($in_uid=='CLEARFORM') {
    $add_flag = 1;
    $in_uid = '';
  }
} else {
  $in_uid = '';
}

$sel = "SELECT * ";
$sel .= "FROM people_or_places ";
$sel .= "WHERE uid = '$in_uid' ";
$result = mysql_query ($sel);
if ($result) {
  $row = mysql_fetch_array($result);
}
if ( isset($in_uid) && !isset($row["uid"]) ) {
   $_SESSION['s_msg'] .= "Person '$in_uid' not found.<br>\n";
}
?>

<html>
<head>
<title>People Maintenance</title>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Update Ring People';
require ('page_top.php');
?>

<div align="center">
<form name="find_person" action="<?php print $PHP_SELF;?>" method="post">
<table border="1">
<tr>
  <td align="right">Person's UID:</td>
  <td><input type="text" name="in_uid" value="<?php print $in_uid;?>">
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
  } 
  $_SESSION['s_msg'] = '';
}?>
</table>
</form>

<p> 

<form name="person_data" 
      action="people_maint_action" 
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
    <a href="people_maint.php?in_uid=CLEARFORM">Clear Form</a>
   </td>
  </tr>
  </table>
  </td>
</tr>
<tr>
 <td align="right">UID:</td>
 <td> <input type="text" name="in_uid"
             value="<?php print $row["uid"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Display Name:</td>
 <td> <input type="text" name="in_display_name"
             value="<?php print $row["display_name"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Description:</td>
 <td>
<TEXTAREA name="in_description" rows="5" cols="40">
<?php print $row["description"];?>
</TEXTAREA>
 </td>
</tr>
<tr>
 <td align="right">Public:</td>
 <td>
  <?php 
  $chk_pub_yes = 'CHECKED'; $chk_pub_no = '';
  if ($row["public_flag"] == 'N') {
    $chk_pub_yes = ''; $chk_pub_no = 'CHECKED';
  }
  ?>
  <input type="radio" name="in_public_flag" value="Y" <?php echo $chk_pub_yes;?>>Yes 
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_public_flag" value="N" <?php echo $chk_pub_no;?>>No 
 </td>
</tr>

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
