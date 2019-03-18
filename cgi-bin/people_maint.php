<?PHP
// -------------------------------------------------------------
// people_maint.php
// author: Bill MacAllister
// date: December 31, 2001

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_uid           = get_request('in_uid');
$in_button_find   = get_request('in_button_find');
$in_button_add    = get_request('in_button_add');
$in_button_update = get_request('in_button_update');
$in_button_delete = get_request('in_button_delete');

//-------------------------------------------------------------
// Start of main processing for the page

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
$result = $DBH->query($sel);
if ($result) {
    $row = $result->fetch_array(MYSQLI_ASSOC);
}
if ( isset($in_uid) && !isset($row["uid"]) ) {
    sys_err("Person '$in_uid' not found.");
    $fld_names = get_fld_names('people_or_places');
    foreach ($fld_names as $db_fld) {
        $row[$db_fld] = '';
    }
}
?>

<html>
<head>
<title>People Maintenance</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Update Ring People';
require ('page_top.php');
?>

<div align="center">
<form name="find_person"
      action="<?php print $_SERVER['PHP_SELF'];?>"
      method="post">
<table border="1">
<tr>
  <td align="right">Person's UID:</td>
  <td><input type="text" name="in_uid" value="<?php print $in_uid;?>">
  </td>
</tr>
<tr>
  <td align="center" colspan="2">
  <input type="submit" name="in_button_find" value="Find">
  </td>
</tr>
</table>
</form>

<p>
<?php sys_display_msg(); ?>

<form name="person_data"
      action="people_maint_action.php"
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
 <td align="right">Authentication UID:</td>
 <td> <input type="text" name="in_auth_uid"
             value="<?php print $row["auth_uid"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Display Name:</td>
 <td> <input type="text" name="in_display_name"
             value="<?php print $row["display_name"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Common Name:</td>
 <td> <input type="text" name="in_cn"
             value="<?php print $row["cn"]; ?>"
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
  $chk_show = $chk_hide = $chk_invis = '';
  if     ($row['visibility'] == 'SHOW')   { $chk_show  = 'CHECKED'; }
  elseif ($row['visibility'] == 'HIDDEN') { $chk_hide  = 'CHECKED'; }
  else                                    { $chk_invis = 'CHECKED'; }
  ?>
  <input type="radio" name="in_visibility" value="SHOW" <?php echo $chk_show;?>>Show
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_visibility" value="HIDDEN" <?php echo $chk_hide;?>>Hidden
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_visibility" value="INVISIBLE" <?php echo $chk_invis;?>>Invisibile
 </td>
</tr>

<tr>
 <td align="right">Date Last Maint:</td>
 <td> <?php print $row["date_last_maint"]; ?> </td>
</tr>
<tr>
 <td align="right">Date Added:</td>
 <td> <?php print $row["date_added"]; ?> </td>
</tr>
<tr><td colspan="2">
  <table border="0" width="100%">
  <tr>
   <td>
      <input type="submit" name="in_button_add" value="Add">
   </td>
   <td align="center">
      <input type="submit" name="in_button_update" value="Update">
   </td>
   <td align="right">
      <input type="submit" name="in_button_delete" value="Delete">
   </td>
  </tr>
  </table>
</td></tr>

</table>
</form>

<?php require('page_bottom.php'); ?>
</body>
</html>
