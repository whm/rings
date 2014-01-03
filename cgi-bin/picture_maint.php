<?PHP
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_pid = $_REQUEST['in_pid'];
$in_button_next = $_REQUEST['in_button_next'];
$in_button_find = $_REQUEST['in_button_find'];
$in_button_update = $_REQUEST['in_button_update'];
$in_button_rotate_left = $_REQUEST['in_button_rotate_left'];
$in_button_rotate_right = $_REQUEST['in_button_rotate_right'];
$in_button_del = $_REQUEST['in_button_del'];
$in_last_date = $_REQUEST['in_last_date'];
$in_last_hour = $_REQUEST['in_last_hour'];
$in_last_minute = $_REQUEST['in_last_minute'];
$in_last_second = $_REQUEST['in_last_second'];
// ----------------------------------------------------------
//
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

require ('/etc/whm/rings_dbs.php');

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

if (isset($in_button_next)) {
    $sel = "SELECT * ";
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid = '$in_pid' ";
    $result = mysql_query ($sel);
    if ($result) {
        $row = mysql_fetch_array($result);
        if (strlen($row['pid'])>0) {
            $last_datetime = $row['picture_date'];
        }
    }
    $in_pid++;
}

if ((!isset($last_datetime)) 
    && (isset($_SESSION['sess_picture_date']))) {
    $last_datetime = $_SESSION['sess_picture_date'];
}

$pat = "/(\d+\-\d+\-\d+)\s+(\d+)\:(\d+)\:(\d+)/";
if (preg_match($pat, $last_datetime, $mat)) {
    $last_date = $mat[1];
    $last_hour = $mat[2];
    $last_minute = $mat[3];
    $last_second = $mat[4];
}

$sel = "SELECT * ";
$sel .= "FROM pictures_information ";
$sel .= "WHERE pid = '$in_pid' ";
$result = mysql_query ($sel);
if ($result) {
    $row = mysql_fetch_array($result);
    $this_type = trim($row["picture_type"]);
    if (strlen($row['picture_date']) == 0) {
        $row['picture_date'] = $row['date_taken'];
    }
    if (strlen($row['pid'])>0) {
        foreach ($row as $fld => $val) {$row[$fld] = trim($val);}
    }
}
if ( isset($in_pid) && !isset($row["pid"]) ) {
    $_SESSION['s_msg'] .= "Picture '$in_pid' not found.\n";
}

// some reasonable defaults
if ($row["key_words"]=='NEW' && isset($session_key_words)) {
    $row["key_words"] = $session_key_words;
}
if ($row["picture_date"]=='UNKNOWN' && isset($session_picture_date)) {
    $row["picture_date"] = $session_picture_date;
}
if (strlen($row["taken_by"])==0 && isset($session_taken_by)) {
    $row["taken_by"] = $session_taken_by;
}
?>

<html>
<head>
<title>Picture Maintenance</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">

<script language="JavaScript">

var click_update = 0;
var click_delete = 0;

function setUpdate() {
  var f = document.picture_data;
  click_update = 1;
  click_delete = 0;
}
function setDelete() {
  var f = document.picture_data;
  click_update = 0;
  click_delete = 1;
}

function incrementDate() {
  var f;
  f = document.picture_data;

  var s = 1*f.last_second.value + 1;
  var m = 1*f.last_minute.value;
  var h = 1*f.last_hour.value;
  if (s > 59) {
      s = '0';
      m = m + 1;
  }
  if (m > 59) {
    m = "0";
    h = h + 1;
  }

  if (s < 9) {s = "0"+s;}
  if (m < 9) {m = "0"+m;}
  if (h < 9) {h = "0"+h;}

  f.in_picture_date.value = f.last_date.value + " " + h + ":" + m + ":" + s;
  f.set_date.checked = false;
  return false;

}

/* --------------------- */
/* Verify the input form */
/* --------------------- */

function verifyInput() {

    var f = document.picture_data;

    if (click_delete != 0) {
        click_delete = 0;
        if (!confirm("Really delete this picture?")) {
            return false;
        }
    }

    return true;
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
  <td align="right">Picture ID:</td>
  <td><input type="text" name="in_pid" value="<?php print $in_pid;?>">
  </td>
</tr>
<tr>
  <td align="center" colspan="2">
  <input type="submit" name="in_button_find" value="Find">
  <input type="submit" name="in_button_next" value="Next">
  </td>
</tr>
</table>
</form>

<p> 

<form name="picture_data" 
      action="picture_maint_action.php" 
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
    <td><input type="submit" 
               onClick="setUpdate()"
               name="in_button_update" 
               value="Update">
    </td>
    <td align="center">
        <input type="submit" 
               name="in_button_rotate_left" 
               value="Rotate Left">
    </td>
    <td align="center">
        <input type="submit" 
               name="in_button_rotate_right" 
               value="Rotate Right">
    </td>
    <td align="center">
        <input type="submit" 
               onClick="setDelete()"
               name="in_button_del" 
               value="Delete">
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
 <td><?php print $row["pid"].'&nbsp;'.$row['file_name'].'&nbsp;'.$row['group_path']; ?>
    <input type="hidden" name="in_pid" value="<?php print $in_pid;?>">
 </td>
</tr>
<tr>
 <td align="right">Old Date Taken:</td>
 <td><?php print $row["date_taken"]; ?></td>
</tr>
<tr>
 <td align="right">Picture Date:</td>
 <td> <input type="text" name="in_picture_date" size="30"
             value="<?php print $row["picture_date"]; ?>">
      <input type="hidden" 
             name="in_last_date" 
             value="<?php echo $in_last_date;?>"> 
      <input type="hidden" 
             name="in_last_hour" 
             value="<?php echo $in_last_hour;?>"> 
      <input type="hidden" 
             name="in_last_minute" 
             value="<?php echo $in_last_minute;?>">
      <input type="hidden" 
             name="in_last_second" 
             value="<?php echo $in_last_second;?>">
<?php if (strlen($last_datetime)>0) {?>
      <br>
      Last Date: <?php echo $last_datetime."\n";?>
      <br>
      <input type="checkbox"
             name="set_date"
             onClick="incrementDate()">Increment Date
<?php } ?>
 </td>
</tr>
<tr>
 <td align="right">Picture Sequence:</td>
 <td> <input type="text" name="in_picture_sequence" size="4"
             value="<?php print $row["picture_sequence"]; ?>">
 </td>
</tr>
<tr>
 <td align="right">Taken By:</td>
 <td> <input type="text" name="in_taken_by" size="16" maxlength="32"
             value="<?php print $row["taken_by"]; ?>">
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
<?php
$chk_a = 'CHECKED';
$chk_b = '';
$chk_c = '';
if ($row['grade'] == 'B') {
    $chk_b = 'CHECKED';
    $chk_a = '';
} elseif ($row['grade'] == 'C') {
    $chk_c = 'CHECKED';
    $chk_a = '';
} 
?>
 <td align="right">Grade:</td>
 <td> <input type="radio" name="in_grade" 
             value="A" <?php echo $chk_a;?>>A &nbsp;&nbsp;
      <input type="radio" name="in_grade" 
             value="B" <?php echo $chk_b;?>>B &nbsp;&nbsp;
      <input type="radio" name="in_grade" 
             value="C" <?php echo $chk_c;?>>C 
 </td>
</tr>

<?php
$chk_y = 'CHECKED';
$chk_n = '';
if ($row['public'] == 'N') {
    $chk_n = 'CHECKED';
    $chk_y = '';
} 
?>
<tr>
 <td align="right">Public:</td>
 <td> <input type="radio" name="in_public" 
             value="Y" <?php echo $chk_y;?>>Yes &nbsp;&nbsp;
      <input type="radio" name="in_public" 
             value="N" <?php echo $chk_n;?>>No
 </td>
</tr>

</table>
<p>

<?php
# Generate table rows of people in the picture already
$picturePeople = '';
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
      $picturePeople .= "<tr>\n";
      $picturePeople .= " <td>$a_name</td>\n";
      $picturePeople .= " <td align=\"center\">\n";
      $picturePeople .= '   <input type="checkbox" '
          . 'name="in_del_' . $people_cnt . '" '
          . 'value="delete">' . "\n";
      $picturePeople .= '   <input type="hidden" '
          . 'name="in_del_uid_' . $people_cnt . ' '
          . 'value="' . $a_uid . '">' . "\n";
      $picturePeople .= " </td>\n";
      $picturePeople .= "</tr>\n";
      $people_cnt++;
    }
  }  
}

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
      $thisWeight = 30 
                  * intval ((32000-$_SESSION['s_uid_weight'][$a_uid]) / 30);
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
      echo "  <select name=\"in_newuids[]\" size=\"10\" multiple>\n";
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

 </td>

 <td colspan="2" align="center" valign="top">

<?php 
if (isset($_SESSION['s_msg'])) { 
  if (strlen($_SESSION['s_msg'])>0) { 
?>
<span bgcolor="#ffffff" align="center">
    <font color="#ff0000"><?php print $s_msg;?></font>
    </span>

<?php 
    $_SESSION['s_msg'] = '';
  }
} 
?>
<p>
<table border="1">
<tr>
  <th colspan="2">People and Places in Picture</th>
</tr>
<tr>
  <th>Person or Place</th>
  <th>Delete?</th>
</tr>
<?php echo $picturePeople; ?>
</table>

<p>

<?php if ( $row["pid"] > 0 ) { ?>
   <img src="/rings/display.php?in_pid=<?php print $row["pid"];?>&in_size=large">  <br>
<?php } ?>


 </td>

</tr>
</table>

<input type="hidden" name="in_del_cnt" value="<?php print $people_cnt;?>">
<input type="hidden" name="in_add_cnt" value="<?php print $add_cnt;?>">

</form>
<?php require('page_bottom.php'); ?>
</body>
</html>
