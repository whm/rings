<?PHP
// -------------------------------------------------------------
// picture_maint.php
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

if (!session_is_registered('s_msg')) {
    session_register('s_msg');
    $_SESSION['s_msg'] = '';
}

require('inc_dbs.php');

// connect to the database
$conn = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$conn) {
    $msg = $msg . "<br>Error connecting to MySQL host $mysql_host";
    echo "$msg";
    exit;
}
$cnx = mysql_select_db($mysql_db);
if (!$cnx) {
    $msg = $msg . "<br>Error connecting to MySQL db $mysql_db";
    echo "$msg";
    exit;
}
if (isset($in_pid)) {
    if ($in_pid=='CLEARFORM') {
        $add_flag = 1;
        $in_pid = '';
    }
} else {
    $in_pid = '';
}

if (strlen($btn_next)>0) {
    $sel = "SELECT * ";
    $sel .= "FROM pictures ";
    $sel .= "WHERE pid = '$in_pid' ";
    $result = mysql_query ($sel);
    if ($result) {
        $row = mysql_fetch_array($result);
        if (strlen($row['pid'])>0) {
            $last_datetime = $row['date_taken'];
        }
    }
    $in_pid++;
}

if ((strlen($last_datetime) == 0) 
    && (strlen($_SESSION['sess_date_taken'])>0)) {
    $last_datetime = $_SESSION['sess_date_taken'];
}

$pat = "/(\d+\-\d+\-\d+)\s+(\d+)\:(\d+)/";
if (preg_match($pat, $last_datetime, $mat)) {
    $last_date = $mat[1];
    $last_hour = $mat[2];
    $last_minute = $mat[3];
}

$sel = "SELECT * ";
$sel .= "FROM pictures ";
$sel .= "WHERE pid = '$in_pid' ";
$result = mysql_query ($sel);
if ($result) {
    $row = mysql_fetch_array($result);
    $this_type = trim($row["picture_type"]);
    if (strlen($row['pid'])>0) {
        foreach ($row as $fld => $val) {$row[$fld] = trim($val);}
    }
}
if ( (strlen($in_pid)>0) && (strlen(trim($row["pid"]))==0) ) {
    $_SESSION['s_msg'] .= "Picture '$in_pid' not found.\n";
}

// some reasonable defaults
if ($row["key_words"]=='NEW' && isset($session_key_words)) {
    $row["key_words"] = $session_key_words;
}
if ($row["date_taken"]=='UNKNOWN' && isset($session_date_taken)) {
    $row["date_taken"] = $session_date_taken;
}
if (strlen($row["taken_by"])==0 && isset($session_taken_by)) {
    $row["taken_by"] = $session_taken_by;
}
?>

<html>
<head>
<title>Picture Maintenance</title>

<script language="JavaScript">

function incrementDate() {
  var f;
  f = document.picture_data;

  var m = 1*f.last_minute.value + 1;
  var h = 1*f.last_hour.value;
  if (m > 59) {
    m = "00";
    h = h + 1;
  }

  if (m < 9) {m = "0"+m;}
  if (h < 9) {h = "0"+h;}

  f.in_date_taken.value = f.last_date.value + " " + h + ":" + m;
  f.set_date.checked = false;
  f.in_key_words.value = '';

  return false;

}

</script>

<?php require('inc_select_search.php'); ?>

</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Update Ring Pictures';
require ('page_top.php');
?>

<div align="center">
<form name="find_picture" 
      action="<?php print $PHP_SELF;?>" 
      method="post">
<table border="1">
<tr>
  <td align="right">Picture's ID:</td>
  <td><input type="text" name="in_pid" value="<?php print $in_pid;?>">
  </td>
</tr>
<tr>
  <td align="center" colspan="2">
  <input type="submit" name="btn_find" value="Find">
  <input type="submit" name="btn_next" value="Next">
  </td>
</tr>
<?php 
if (isset($_SESSION['s_msg'])) { 
  if (strlen($_SESSION['s_msg'])>0) { 
?>
<tr><td bgcolor="#ffffff" align="center" colspan="2">
    <font color="#ff0000"><?php print $s_msg;?></font>
    </td>
</tr>
<?php 
    $_SESSION['s_msg'] = '';
  }
} 
?>
</table>
</form>

<p> 

<form name="picture_data" 
      action="picture_maint_action" 
      onsubmit="return verifyInput()"
      method="post">
<table border="0">
<tr>

<td align="center" valign="top">

<table border="1" cellpadding="2">
<tr>
 <td colspan="2">
 <table border="0" width="100%">
 <tr>
   <td> 
    &nbsp;
   </td>
   <td align="right">
    <a href="picture_maint.php?in_pid=CLEARFORM">Clear Form</a>
   </td>
  </tr>
  </table>
  </td>
</tr>
<tr>
 <td align="right">Picture ID:</td>
 <td><?php print $row["pid"].'&nbsp;'.$row['file_name']; ?>
    <input type="hidden" name="in_pid" value="<?php print $in_pid;?>">
 </td>
</tr>
<tr>
 <td align="right">Picture Type:</td>
 <td><?php print $this_type;?></td>
</tr>
<tr>
 <td align="right">Keywords:</td>
 <td> <input type="text" name="in_key_words"
             value="<?php print $row["key_words"]; ?>">
 </td>
</tr>
<tr>
 <td align="right">Date Taken:</td>
 <td> <input type="text" name="in_date_taken"
             value="<?php print $row["date_taken"]; ?>">
      &nbsp;<?php echo $last_datetime."\n";?>
      <input type="hidden" 
             name="last_date" 
             value="<?php echo $last_date;?>"> 
      <input type="hidden" 
             name="last_hour" 
             value="<?php echo $last_hour;?>"> 
      <input type="hidden" 
             name="last_minute" 
             value="<?php echo $last_minute;?>">
<?php if (strlen($last_datetime)>0) {?>
      <br>
      <input type="checkbox"
             name="set_date"
             onClick="incrementDate()">Increment Date
<?php } ?>
 </td>
</tr>
<tr>
 <td align="right">Taken By:</td>
 <td> <input type="text" name="in_taken_by" size="16" maxlength="32"
             value="<?php print $row["taken_by"]; ?>"
 </td>
</tr>
<tr>
 <td align="right">Description:</td>
 <td>
<TEXTAREA name="in_description" rows="2" cols="40">
<?php print $row["description"];?>
</TEXTAREA>
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
</table>
<p>

<p>
<table border="1">
<tr>
  <th colspan="2">People and Places in Picture</th>
</tr>
<tr>
  <th>Person or Place</th>
  <th>Delete?</th>
</tr>
<?php
$thisID = $row["pid"];
$people_cnt = 0;
if (strlen($thisID) > 0) {
  $cmd = "SELECT det.uid uid, p.display_name display_name ";
  $cmd .= "FROM picture_details det, people_or_places p ";
  $cmd .= "WHERE det.pid=$thisID ";
  $cmd .= "AND det.uid = p.uid ";
  $cmd .= "ORDER BY p.display_name ";
  $result = mysql_query ($cmd);
  if ($result) {
    while ($link_row = mysql_fetch_array($result)) {
      $a_uid = $link_row["uid"];
      $a_name = $link_row["display_name"];
      $found["$a_uid"] = 1;
      echo "<tr>\n";
      echo " <td>$a_name</td>\n";
      echo " <td align=\"center\">\n";
      echo "   <input type=\"checkbox\" name=\"del_$people_cnt\" ";
      echo           "value=\"delete\">\n";
      echo "   <input type=\"hidden\" name=\"del_uid_$people_cnt\" ";
      echo           "value=\"$a_uid\">\n";
      echo " </td>\n";
      echo "</tr>\n";
      $people_cnt++;
    }
  }  
}
?>
</table>
<p>

<?php
// Get a list of folks to add to the picture
$cmd = "SELECT uid,display_name ";
$cmd .= "FROM people_or_places ";
$cmd .= "ORDER BY display_name ";
$result = mysql_query ($cmd);
if ($result) {
  while ($person_row = mysql_fetch_array($result)) {
    $a_uid = $person_row["uid"];
    if (isset($found["$a_uid"])) {continue;}
    $uid_list[$a_uid] = $person_row['display_name'];
    $thisWeight = 32767;
    if ($_SESSION['s_uid_weight'][$a_uid]>0) {
      $thisWeight = 100 
                  * intval ((32000-$_SESSION['s_uid_weight'][$a_uid]) / 100);
    }
    $sort_uid = 'a'.sprintf("%05d", $thisWeight)
             . $person_row['display_name'];
    $uid_sort[$sort_uid] = $a_uid;
  }
  ksort($uid_sort);
}

?>

<table border="1">
<tr><th colspan="2">People to Add to Picture</th></tr>
<tr>
 <td align="right">People to Add:</td>
 <td>
    <script language="javascript" type="text/javascript">
      var in_ppe_values  = new Array();
      var in_ppe_display = new Array();
   </script>
   <input type="text" 
          name="in_group_search" 
          onkeyup="find_select_items(this, this.form.elements['in_newuids[]'], in_ppe_values, in_ppe_display);">
   <br>
<?php
$add_cnt = 0;
if (is_array($uid_sort)) {
  foreach ($uid_sort as $sort_key => $a_uid) {
    $a_name = $uid_list[$a_uid];
    if ($add_cnt == 0) {
      echo "  <select name=\"in_newuids[]\" multiple>\n";
    }
    echo "   <option value=\"$a_uid\">$a_name\n";
    $add_cnt++;
  }
  if ($add_cnt > 0) {
    echo "</select>\n";
  }
}
?>
 </td>
<tr>
</table>

<p>

<table border="0" width="75%">
<tr>
  <td><input type="submit" name="btn_del" value="Delete"></td>
  <td align="right"><input type="submit" name="btn_update" value="Update"></td>
</tr>
</table>

<input type="hidden" name="del_cnt" value="<?php print $people_cnt;?>">
<input type="hidden" name="add_cnt" value="<?php print $add_cnt;?>">

 </td>
 <td>
<?php
  if (strlen($row["picture"]) > 0) {
?>
  <td colspan="2" align="center" valign="top">
   <img src="/rings/display.php?in_pid=<?php print $row["pid"];?>&in_size=large">
  </td>
<?php } else { ?>
  &nbsp;</td>
<?php } ?>
</tr>
</table>

</form>
<?php require('page_bottom.php'); ?>
</body>
</html>
