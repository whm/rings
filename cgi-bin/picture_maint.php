<?PHP
// -------------------------------------------------------------
// picture_maint.php
// author: Bill MacAllister
// date: December 31, 2001

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_pid                 = get_request('in_pid');
$in_button_find         = get_request('in_button_find');
$in_button_next         = get_request('in_button_next');
$in_button_prev         = get_request('in_button_prev');
$in_button_update       = get_request('in_button_update');
$in_button_rotate_left  = get_request('in_button_rotate_left');
$in_button_rotate_right = get_request('in_button_rotate_right');
$in_button_del          = get_request('in_button_del');

// Globals
//
$DATE_PATTERN = '/^(\d+)[-:](\d+)[-:](\d+)[\s-:](\d+)[-:](\d+)[-:](\d+)/';
$DATE_FORMAT  = '%04d-%02d-%02d %02d:%02d:%02d';

// -- Increment the time part of a datetime.  Don't do anything if we
//    need to goto the next day.
function increment_time ($a_datetime) {

    global $DATE_FORMAT;
    global $DATE_PATTERN;

    if (preg_match($DATE_PATTERN, $a_datetime, $matches)) {
        $a_year   = $matches[1];
        $a_month  = $matches[2];
        $a_day    = $matches[3];
        $a_hour   = $matches[4];
        $a_minute = $matches[5];
        $a_second = $matches[6];
        $a_second++;
        if ($a_second > 59) {
            $a_second = 0;
            $a_minute++;
        }
        if ($a_minute > 59) {
            $a_minute = 0;
            $a_hour++;
        }
        if ($a_hour < 24) {
            $return_datetime = sprintf($DATE_FORMAT,
                                       $a_year,
                                       $a_month,
                                       $a_day,
                                       $a_hour,
                                       $a_minute,
                                       $a_second);
        }
        return $return_datetime;
    }
    return;
}


//-------------------------------------------------------------
// Start of main processing for the page

if (!empty($in_pid)) {
    if ($in_pid=='CLEARFORM') {
        $add_flag = 1;
        $in_pid = '';
    }
} else {
    $in_pid = '';
}

// Find the next or the previous picture.
$sel_pid = empty($in_pid) ? 0 : $in_pid;
$base_sel = 'SELECT pid FROM pictures_information ';
$prev_sel = "WHERE pid < $sel_pid ORDER BY pid DESC ";
$next_sel = "WHERE pid > $sel_pid ORDER BY pid ";

$sel = "$base_sel $prev_sel LIMIT 0,1 ";
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if (!empty($row['pid'])) {
            $prev_pid = $row['pid'];
        }
    }
}
$sel = "$base_sel $next_sel LIMIT 0,1 ";
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if (!empty($row['pid'])) {
            $next_pid = $row['pid'];
        }
    }
}
if (!empty($in_button_next)) {
    $in_pid = $next_pid;
} elseif (!empty($in_button_prev)) {
    $in_pid = $prev_pid;
}

if (!empty($_SESSION['maint_last_datetime'])) {
    $next_datetime = increment_time($_SESSION['maint_last_datetime']);
}

$sel = "SELECT * ";
$sel .= "FROM pictures_information ";
$sel .= "WHERE pid = '$in_pid' ";
$result = $DBH->query ($sel);
if ($result) {
    $row = $result->fetch_array(MYSQLI_ASSOC);
    if (!isset($row['picture_date']) || strlen($row['picture_date']) == 0) {
        $row['picture_date'] = $row['date_taken'];
    }
    if (!empty($row['pid']) && strlen($row['pid'])>0) {
        foreach ($row as $fld => $val) {
            $row[$fld] = trim($val);
        }
    }
}
if (!empty($in_pid) && empty($row["pid"]) ) {
    $_SESSION['msg'] .= "Picture '$in_pid' not found.\n";
    $fld_names = get_fld_names('pictures_information');
    foreach ($fld_names as $db_fld) {
        $row[$db_fld] = '';
    }
}

// Check to see if the raw image exists
if (!empty($in_pid) && strlen($in_pid) > 0) {
    $sel = "SELECT pid ";
    $sel .= "FROM pictures_raw ";
    $sel .= "WHERE pid = '$in_pid' ";
    $result = $DBH->query ($sel);
    if ($result) {
        $raw_row = $result->fetch_array(MYSQLI_ASSOC);
        if (empty($raw_row['pid'])) {
            $_SESSION['msg'] .= "Raw image is missing for '$in_pid'.\n";
        }
    }
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

function setDatetime() {
  var f;
  f = document.picture_data;

  f.in_picture_date.value = f.next_datetime.value;
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
      action="<?php print $_SERVER['PHP_SELF'];?>"
      method="post">
<table border="1">
<tr>
  <td align="right">Picture ID:</td>
  <td><input type="text" name="in_pid" value="<?php print $in_pid;?>">
  </td>
</tr>
<tr>
  <td align="center" colspan="2">
<?php if (!empty($prev_pid)) { ?>
  <input type="submit" name="in_button_prev" value="Back">
<?php } ?>
  <input type="submit" name="in_button_find" value="Find">
<?php if (!empty($next_pid)) { ?>
  <input type="submit" name="in_button_next" value="Next">
<?php } ?>
  </td>
</tr>
</table>
<?php
if (!empty($in_pid)) {
    echo "<input type=\"hidden\" name=\"in_pid\" value=\"$in_pid\">\n";
}
?>
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
 <td><?php
    if (!empty($row['pid'])) {
        $pic_info = $row["pid"];
        if (!empty($row['file_name'])) {
            $pic_info .= ' File:' . $row['file_name'];
        }
        if (!empty($row['group_path'])) {
            $pic_info .= ' Group:' . $row['group_path'];
        }
        $pic_info .= ' <a href="picture_reload.php?in_pid=' . $in_pid . '" '
            . 'target="_blank">Reload</a>'
            . "\n";
        print $pic_info;
    }
    ?>
    <input type="hidden" name="in_pid" value="<?php print $in_pid;?>">
 </td>
</tr>
<tr>
 <td align="right">Old Date Taken:</td>
 <td><?php $row['date_taken']; ?></td>
</tr>
<tr>
 <td align="right">Picture Date:</td>
 <td> <input type="text" name="in_picture_date" size="30"
             value="<?php print $row["picture_date"]; ?>">

      <?php if (!empty($next_datetime)) { ?>
      <input type="hidden"
             name="next_datetime"
             value="<?php echo $next_datetime;?>">
      <br>
      <input type="checkbox"
             name="set_date"
             onClick="setDatetime()">
                Set Date to <?php echo $next_datetime; ?>
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
    $result = $DBH->query ($cmd);
    if ($result) {
        while ($link_row = $result->fetch_array(MYSQLI_ASSOC)) {
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
                . 'name="in_del_uid_' . $people_cnt . '" '
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
$result = $DBH->query ($cmd);
if ($result) {
    while ($person_row = $result->fetch_array(MYSQLI_ASSOC)) {
        $a_uid = $person_row["uid"];
        if (!empty($found["$a_uid"])) {continue;}
        $uid_list[$a_uid] = $person_row['display_name'];
        $thisWeight = 32767;
        if (!empty($_SESSION['s_uid_weight'][$a_uid])) {
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
if (!empty($_SESSION['msg'])) {
?>
<span bgcolor="#ffffff" align="center">
    <font color="#ff0000"><?php print $_SESSION['msg'];?></font>
    </span>

<?php
    $_SESSION['msg'] = '';
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

<?php
if (!empty($row['pid']) && $row["pid"]>0) {
    echo '<img src="display.php';
    echo '?in_pid=' . $row["pid"];
    echo '&in_size=' . $CONF['maint_size'];
    echo '">' . "\n";
    echo "<br/>\n";
}
?>

<?php
# Picture matching code
$sel = "SELECT tmp_matching.file_path, tmp_matching.signature ";
$sel .= "FROM tmp_matching ";
$sel .= "JOIN pictures_small ";
$sel .= "ON (pictures_small.size = tmp_matching.size ";
$sel .= "AND pictures_small.width = tmp_matching.width) ";
$sel .= "WHERE pictures_small.pid = $in_pid ";
$result = $DBH->query ($sel);
if ($result) {
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $this_path = $row['file_path'];
        $this_sig  = $row['signature'];
        echo '<img src="display_file.php?in_signature=';
        echo $this_sig;
        echo '">' . "\n";
        echo "<br/>\n";
        echo $row['file_path'] . "<br>\n";
    }
}
?>

 </td>

</tr>
</table>

<input type="hidden" name="in_del_cnt" value="<?php print $people_cnt;?>">
<input type="hidden" name="in_add_cnt" value="<?php print $add_cnt;?>">

</form>
<?php require('page_bottom.php'); ?>
</body>
</html>
