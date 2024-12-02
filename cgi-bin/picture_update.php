<?PHP
// -------------------------------------------------------------
// picture_update.php
// author: Bill MacAllister
// date: December 1, 2024

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_pid                 = get_request('in_pid');
$in_reset_new           = get_request('in_reset_new');
$in_button_find         = get_request('in_button_find');
$in_button_next         = get_request('in_button_next');
$in_button_prev         = get_request('in_button_prev');
$in_button_update       = get_request('in_button_update');
$in_button_rotate_180   = get_request('in_button_rotate_180');
$in_button_rotate_left  = get_request('in_button_rotate_left');
$in_button_rotate_right = get_request('in_button_rotate_right');
$in_clear_cache         = get_request('in_clear_cache');

// Globals
//
$DATE_PATTERN = '/^(\d+)[-:](\d+)[-:](\d+)(\s|-|:)(\d+)[-:](\d+)[-:](\d+)/';
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
        $a_hour   = $matches[5];
        $a_minute = $matches[6];
        $a_second = $matches[7];
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
        $add_flag    = 1;
        $this_pid    = 0;
        $display_pid = '';
    }
    if ($in_pid < 1) {
        sys_msg("Invalid PID entered $in_pid");
        $this_pid    = 0;
        $display_pid = '';
    } else {
        $this_pid    = $in_pid;
        $display_pid = $in_pid;
    }
} else {
    $this_pid = 0;
}

// SQL for selecting next and previous PIDs
$base_sel = 'SELECT pid FROM pictures_information ';
$prev_sel = 'WHERE pid < ? ORDER BY pid DESC ';
$next_sel = 'WHERE pid > ? ORDER BY pid ';

// Find previous PID
$sel = "$base_sel $prev_sel LIMIT 0,1 ";
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
if (!$stmt = $DBH->prepare($sel)) {
    sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
}
$stmt->bind_param('i', $this_pid);
$stmt->execute();
$stmt->bind_result($p1);
if ($stmt->fetch()) {
    $prev_pid = $p1;
}
$stmt->close();

// Find next PID
$sel = "$base_sel $next_sel LIMIT 0,1 ";
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
if (!$stmt = $DBH->prepare($sel)) {
    sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
}
$stmt->bind_param('i', $this_pid);
$stmt->execute();
$stmt->bind_result($p1);
if ($stmt->fetch()) {
    $next_pid = $p1;
}
$stmt->close();

// Set PID selections if next or previous was selected
if (!empty($in_button_next)) {
    $this_pid    = $next_pid;
    $display_pid = $this_pid;
} elseif (!empty($in_button_prev)) {
    $this_pid    = $prev_pid;
    $display_pid = $this_pid;
}

if (!empty($_SESSION['maint_last_datetime'])) {
    $next_datetime = increment_time($_SESSION['maint_last_datetime']);
}

$sel = 'SELECT pic.pid, ';
$sel .= 'pic.picture_date picture_date, ';
$sel .= 'pic.picture_sequence picture_sequence, ';
$sel .= 'pic.picture_lot picture_lot, ';
$sel .= 'pic.file_name file_name, ';
$sel .= 'pic_cg.comment comment, ';
$sel .= 'pic_cg.grade grade ';
$sel .= 'FROM pictures_information pic ';
$sel .= 'LEFT OUTER JOIN picture_comments_grades pic_cg ';
$sel .= "ON (pic_cg.pid = pic.pid AND pic_cg.uid = '$ring_user') ";
$sel .= "WHERE pic.pid = $this_pid ";
$result = $DBH->query ($sel);
if ($result) {
    $row = $result->fetch_array(MYSQLI_ASSOC);
    if (!empty($row['pid'])) {
        foreach ($row as $fld => $val) {
            $row[$fld] = trim($val);
        }
    }
}
if ($this_pid > 0 && empty($row['pid']) ) {
    sys_msg("Picture '$in_pid' not found.");
    $fld_names = get_fld_names('pictures_information');
    foreach ($fld_names as $db_fld) {
        $row[$db_fld] = '';
    }
    if (!empty($row['picture_date'])) {
        $_SESSION['maint_last_datetime'] = $row['picture_date'];
    }
}

// Check to see if the raw image exists
if ($this_pid > 0) {
    $sel = 'SELECT pid,date_last_maint ';
    $sel .= 'FROM picture_details ';
    $sel .= "WHERE pid = $this_pid ";
    $sel .= "AND size_id = 'raw' ";
    $result = $DBH->query ($sel);
    if ($result) {
        $raw_row = $result->fetch_array(MYSQLI_ASSOC);
        if (empty($raw_row['pid'])) {
            sys_msg("Raw image is missing for $this_pid.");
        } else {
            $date_last_maint = $raw_row['date_last_maint'];
        }
    }
}

// Calculate the picture grade
$grade_average = calculate_picture_grade($this_pid);
?>
<html>
<head>
<title>Picture Update</title>
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
        if (!confirm('Really delete this picture?')) {
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
$thisTitle = 'Update a Picture';
require ('page_top.php');
?>

<form name="find_picture"
      action="<?php print $_SERVER['PHP_SELF'];?>"
      method="post">
<table border="1">
<tr>
  <td align="right">Picture ID:</td>
  <td><input type="text" name="in_pid" value="<?php print $display_pid;?>">
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
if ($this_pid > 0) {
    echo '<input type="hidden" name="old_pid" '
        . 'value="' . $this_pid . '">' . "\n";
}
?>
</form>

<p>

<form name="picture_data"
      action="picture_update_action.php"
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
               name="in_button_rotate_180"
               value="Rotate 180">
    </td>
    <td align="center">
        <input type="submit"
               name="in_button_rotate_right"
               value="Rotate Right">
    </td>
    </tr>
    </table>
 </td>
</tr>
<tr>
 <td align="right">Picture ID:</td>
 <td>
 <?php
    if ( !empty($row['pid']) ) {
        if (empty($row['picture_sequence']) || $row['picture_sequence'] < 1) {
            $row['picture_sequence'] = 1;
        }
        $pic_info = $row['pid'];
        $pic_info .= '<br/> Lot:' . $row['picture_lot'];
        if (!empty($row['file_name'])) {
            $pic_info .= '<br/> File:' . $row['file_name'];
        }
        $pic_info .= '<br/>'
            . '<a href="picture_reload.php?in_pid=' . $display_pid . '" '
            . 'target="_blank">Reload</a>'
            . "\n";
        $pic_info .= '&nbsp;&nbsp;'
            . '<a href="display.php'
            . '?in_pid=' . $display_pid
            . '&in_size=raw'
            . '" '
            . 'target="_blank">Raw</a>'
            . "\n";
        $pic_info .= '<input type="hidden" name="in_picture_sequence" '
            . 'value="' . $row['picture_sequence'] . '">' . "\n";
        print $pic_info;
    }
    ?>
    <input type="hidden" name="in_pid" value="<?php print $display_pid;?>">
 </td>
</tr>

<tr>
 <td align="right">Comments:</td>
 <td>
<TEXTAREA name="in_comment" rows="2" cols="40">
<?php print $row["comment"];?>
</TEXTAREA>
 </td>
</tr>

<tr>
<?php
$chk_a = '';
$chk_b = '';
$chk_c = '';
$chk_d = '';
if ($row['grade'] == 'B') {
    $chk_b = 'CHECKED';
} elseif ($row['grade'] == 'C') {
    $chk_c = 'CHECKED';
} elseif ($row['grade'] == 'D') {
    $chk_d = 'CHECKED';
} else {
    $chk_a = 'CHECKED';
}
?>
 <td align="right">Grade:</td>
 <td> <input type="radio" name="in_grade"
             value="A" <?php echo $chk_a;?>>A &nbsp;&nbsp;
      <input type="radio" name="in_grade"
             value="B" <?php echo $chk_b;?>>B &nbsp;&nbsp;
      <input type="radio" name="in_grade"
             value="C" <?php echo $chk_c;?>>C
      <input type="radio" name="in_grade"
             value="D" <?php echo $chk_d;?>>Duplicate
 </td>
</tr>

<tr>
 <td align="right">Picture Date:</td>
 <td> <input type="text" name="in_picture_date" size="30"
             value="<?php print $row["picture_date"]; ?>">

      <?php if (!empty($next_datetime)) { ?>
      <input type="hidden"
             name="next_datetime"
             value="<?php echo $next_datetime;?>">
      <br/>
      <input type="checkbox"
             name="set_date"
             onClick="setDatetime()">
                Set Date to <?php echo $next_datetime; ?>
      <?php } ?>

 </td>
</tr>

</table>
<p>

<?php
# Generate table rows of people in the picture already
$picturePeople = '';
if (is_array($row) && array_key_exists('pid', $row)) {
    $thisID = $row['pid'];
}
$people_cnt = 0;
if (!empty($thisID)) {
    $cmd = 'SELECT det.uid uid, p.display_name display_name ';
    $cmd .= 'FROM picture_rings det, people_or_places p ';
    $cmd .= "WHERE det.pid=$thisID ";
    $cmd .= 'AND det.uid = p.uid ';
    $cmd .= 'ORDER BY p.display_name ';
    $result = $DBH->query ($cmd);
    if ($result) {
        while ($link_row = $result->fetch_array(MYSQLI_ASSOC)) {
            $a_uid = $link_row['uid'];
            $a_name = $link_row['display_name'];
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
$cmd = 'SELECT uid,display_name ';
$cmd .= 'FROM people_or_places ';
$cmd .= 'ORDER BY display_name ';
$result = $DBH->query ($cmd);
$list_found = 0;
if ($result) {
    while ($person_row = $result->fetch_array(MYSQLI_ASSOC)) {
        $a_uid = $person_row['uid'];
        if (!empty($found["$a_uid"])) {
        continue;
    }
        $uid_list[$a_uid] = $person_row['display_name'];
        $thisWeight = 32767;
        if (!empty($_SESSION['s_uid_weight'][$a_uid])) {
            $thisWeight = 30
                * intval ((32000-$_SESSION['s_uid_weight'][$a_uid]) / 30);
        }
        $sort_uid = 'a'.sprintf("%05d", $thisWeight)
                       . $person_row['display_name'];
        $uid_sort[$sort_uid] = $a_uid;
        $list_found = 1;
    }
    if ($list_found) {
        ksort($uid_sort);
    } else {
       $uid_sort['notfound'] = 'No people found';
    }
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
   <input type="checkbox" name="in_clear_cache" value="1">Clear Name Cache
   <br/>
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
check_action_queue($this_pid);
sys_display_msg();
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
<tr>
  <td colspan="2" align="center">
      Reset New? <input type="checkbox" name="in_reset_new">
  </td>
</tr>
</table>

<p>

<?php
$defeat_cache = rand(1, 10000);
if (!empty($row['pid'])) {
    echo '<a href="display.php'
        . '?in_pid=' . $this_pid
        . '&in_size=raw'
        . '&rand=' . $defeat_cache
        . '" target="_blank">';
    echo '<img src="display.php';
    echo '?in_pid=' . $row["pid"];
    echo '&in_size=' . $CONF['maint_size'];
    echo '&dlm=' . htmlentities($date_last_maint);
    echo '">' . "\n";
    echo "</a>\n";
    echo "<br/>\n";
}

# Picture matching code
if ($this_pid > 0) {
  $sel = 'SELECT signature FROM picture_details ';
  $sel .= 'WHERE pid = ? AND size_id = "small"';
  if (!$stmt = $DBH->prepare($sel)) {
    sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
  }
  $stmt->bind_param('i', $this_pid);
  $stmt->execute();
  $stmt->bind_result($p1);
  if ($stmt->fetch()) {
    $this_signature = $p1;
  }
  $stmt->close();
  if (isset($this_signature)) {
    $sel = 'SELECT pid FROM picture_details ';
    $sel .= 'WHERE signature = ? AND pid != ? AND size_id = "small" ';
    $sel .= 'ORDER BY pid ';
    if (!$stmt = $DBH->prepare($sel)) {
      sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
    }
    $stmt->bind_param('si', $this_signature, $this_pid);
    $stmt->execute();
    $stmt->bind_result($p1);
    while ($stmt->fetch()) {
      $dup_pid = $p1;
      echo '<a href="picture_update.php?pid=' . $dup_pid . '">';
      echo "Duplicate Picture: $dup_pid </a>\n";
    }
    $stmt->close();
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
