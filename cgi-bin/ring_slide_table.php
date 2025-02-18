<?PHP
// -------------------------------------------------------------
// ring_slide_table.php
// author: Bill MacAllister
// date: 21-May-2017

// Init session, connect to database
require('inc_ring_init.php');

// Form or URL inputs
$form_flds = array(
    'number',
    'start_date',
    'last',
    'start',
    'next',
    'prev',
    'uid'
);
$in = array();
foreach ($form_flds as $f) {
    $in[$f] = get_request('in_' . $f);
}

##############################################################################
# Subroutines
##############################################################################

// ----------------------------------------------------------
// Display page selection form

function display_page_select($start_date, $number, $uid) {
?>
<form name="page_select"
      action="<?php echo $_SERVER['PHP_SELF'];?>"
      onsubmit="return verifyInput()"
      method="post">
<div id="pageControl">
<fieldset>
  <legend>Page Control</legend>
  <label class="field">Starting Date</label>
     <input type="text" name="in_start_date"
            size="18"
            value="<?php echo $start_date;?>">
     <input type="submit" name="btn_refresh" value="Refresh">
  <br/>
  <p><label class="field">Number of Pictures to Display</label>
     <input type="text"
            name="in_number"
            size="2"
            value="<?php echo $number;?>">
     <input type="hidden"
            name="in_uid"
            size="2"
            value="<?php echo $uid;?>">
</fieldset>
</div>
</form>
<?php
    return;
}

// ----------------------------------------------------------
// Display First, Previous, Next, Last
function display_table_nav() {

    global $picture_count;
    global $in;

    if ($picture_count > $in['number']) {
        echo "<fieldset>\n";
        echo '<div class="pagenav">' . "\n";
        if ($in['start']+1 > $in['number']) {
            echo '<div class="first">';
            echo '<a href="' . $_SERVER['PHP_SELF']
                . '?in_start=0'
                . '&in_uid=' . $in['uid']
                . '">First</a>';
            echo '</div>';
            $display_prev = $in['start'] - $in['number'];
            if ($display_prev < 0) {
                $display_rev = 0;
            }
            echo '<div class="prev">';
            echo '<a href="' . $_SERVER['PHP_SELF']
                . '?in_start=' . $display_prev
                . '&in_uid=' . $in['uid']
                . '">Previous</a>';
            echo '</div>';
        } else {
            echo '<div class="first">&nbsp</div>';
            echo '<div class="prev">&nbsp</div>';
        }
        echo "\n";

        if ($in['start'] < $picture_count - $in['number']) {
            $display_next = $in['start'] + $in['number'];
            $display_last = $picture_count - $in['number'];
            echo '<div class="last">';
            echo '<a href="' . $_SERVER['PHP_SELF']
                . '?in_start=' . $display_last
                . '&in_uid=' . $in['uid']
                . '">Last</a>';
            echo '</div>';
            echo '<div class="next">';
            echo '<a href="' . $_SERVER['PHP_SELF']
            . '?in_start=' . $display_next
                . '&in_uid=' . $in['uid']
                . '">Next</a>';
            echo '</div>';
        } else {
            echo '<div class="next">&nbsp</div>';
            echo '<div class="last">&nbsp</div>';
        }
        echo "\n";
        echo '</div>' . "\n";
        echo "</fieldset>\n";
    }

    return;
}

// ----------------------------------------------------------
// check to see if a picture duplicated

function dup_check($pid) {
    global $DBH;

    $duplicate_list = '';

    // Read the existing picture data
    $pic = array();
    $flds = array(
        'camera',
        'file_name',
        'fstop',
        'raw_picture_size',
        'shutter_speed'
    );
    $sel = 'SELECT ';
    $comma = '';
    foreach ($flds as $f) {
        $sel .= $comma . $f;
        $comma = ',';
    }
    $sel .= ' ';
    $sel .= 'FROM pictures_information ';
    $sel .= "WHERE pid = $pid ";
    $result = $DBH->query ($sel);
    if ($result) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            foreach ($flds as $f) {
                if ( !empty($row[$f]) ) {
                    $pic[$f] = $row[$f];
                }
            }
        }
    } else {
        sys_err("Problem SQL: $sel");
        sys_err('PID ' . $pid . ' not found');
        return array();
    }

    $this_file = pathinfo($pic['file_name'], PATHINFO_FILENAME);
    $sel = 'SELECT pid, raw_picture_size ';
    $sel .= 'FROM pictures_information ';
    $sel .= "WHERE pid != $pid ";
    $sel .= "AND file_name LIKE '%${this_file}%' ";
    $dup_fld_list = array('camera', 'shutter_speed', 'fstop');
    foreach ($dup_fld_list as $f) {
      if (!empty($pic[$f])) {
          $sel .= "AND $f = '" . $pic[$f] . "' ";
      } else {
          $sel .= "AND $f IS NULL ";
      }
    }
    $sel .= "ORDER BY pid ";

    $result = $DBH->query ($sel);
    $dup_array = array();
    if ($result) {
        $comma = '';
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            if ($pic['raw_picture_size'] > 0) {
                if ($row['raw_picture_size'] == $pic['raw_picture_size']) {
                    array_push($dup_array, $row['pid'] . ' ' . $size_match);
                }
            }
        }
    }
    return $dup_array;
}

// ----------------------------------------------------------
// Function to display the pictures in a form that will allow dates
// to be adusted.

function display_slide_table($pic_data) {

    global $CONF;
    global $in;
    global $ring_admin;

    $update_form_action = 'ring_slide_table_action.php';
?>
    <form name="slide_table"
          action="<?php echo $update_form_action;?>"
          method="post">

    <fieldset>
    <legend>Slide Table</legend>
<?php
    display_table_nav();

    $cnt = 0;
    foreach ($pic_data as $cnt => $pic) {
        $pic_href = 'picture_select.php'
                  . '?in_ring_pid=' . $pic['pid']
                  . '&in_ring_uid=' . $in['uid'];
        $pic_edit = 'picture_maint.php?in_pid=' . $pic['pid'];
        $pic_src  = 'display.php?in_pid=' . $pic['pid']
            . '&in_size=' . $CONF['index_size'];

        // check for duplicates
        $dups = dup_check($pic['pid']);
        $pic_dups = '';
        foreach ($dups as $d) {
            $ditem = "<br/>\n";
            $ditem .= '<font color="red">Duplicate: ';
            $ditem .= '<a href="picture_maint.php?in_pid=' . $d . '" '
                   . 'target="_blank">';
            $ditem .= $d . '</a>';
            $ditem .= "</font>\n";
            $pic_dups .= $ditem;
        }
?>
    <div class="sort_image">
      <a href="<?php echo $pic_href;?>" target="_blank">
        <img src="<?php echo $pic_src; ?>" border="0">
      </a>
      <div class="caption">
<?php if ($ring_admin) { ?>
        <a href="<?php echo $pic_edit; ?>"
           target="_blank">
         Edit:<?php echo $pic['pid']?>
	 &nbsp;&nbsp
	 Grade:<?php $pic['grade']; ?>
        </a>
<?php if (!empty($pic_dups)) { ?>
        <input type="checkbox" value="delete"
               name="in_delete_<?php echo $cnt;?>">Delete
<?php } ?>
        <br/>
<?php } ?>
        <input type="text" name="in_date_<?php echo $cnt;?>"
               value="<?php echo $pic['date'];?>" size="18">
        <?php echo $pic_dups; ?>
        <br/>
      </div>
    </div>
    <input type="hidden" name="in_pid_<?php echo $cnt;?>"
        value="<?php echo $pic['pid'];?>">
    <input type="hidden" name="in_od_<?php echo $cnt;?>"
        value="<?php echo $pic['date'];?>">
    <input type="hidden" name="in_dlm_<?php echo $cnt;?>"
        value="<?php echo $pic['dlm'];?>">
<?php
        $cnt++;
    }
?>
    </fieldset>

    <p>
    <input type="submit" name="in_button_update" value="Update">
    <input type="hidden" name="in_picture_cnt"
           value="<?php echo $cnt;?>">
    <input type="hidden" name="in_uid"
           value="<?php echo $in['uid'];?>">
    <input type="hidden" name="in_start"
           value="<?php echo $in['start'];?>">
    </p>

    </form>
<?php
    return;
}

##############################################################################
# Main Routine
##############################################################################

if (empty($_SESSION['display_grade'])) {
    $_SESSION['display_grade'] = 'A';
}
$grade_sel = "(p.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR p.grade = '' ";
$grade_sel .= "OR p.grade IS NULL) ";

// Set some defaults
if (empty($in['start'])){
    $in['start']  = 0;
}
if ($in['number'] == 0) {
    if (!empty($_SESSION['s_thumbs_per_page'])) {
        $in['number'] = $_SESSION['s_thumbs_per_page'];
    } else {
        $in['number'] = 10 * 7;
    }
}

// Bail out if we don't have a selection
if (!$ring_user && auth_person_hidden($in['uid']) > 0) {
    back_to_index('Invalid person selection');
}
$thisPerson = $in['uid'];
$sel = "SELECT display_name ";
$sel .= "FROM people_or_places pp ";
$sel .= "WHERE uid='" . $in['uid'] . "' ";
$result = $DBH->query($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $thisPerson = $row['display_name'];
    }
}
if (empty($row['display_name'])) {
    back_to_index();
}

if ($ring_user_priv == 'ADMINISTRATOR') {
    $ring_admin = true;
} else {
    $ring_admin = false;
}

// get a count of the number of pictures in total
$picture_count = 0;
$sel = "SELECT count(*) cnt ";
$sel .= "FROM picture_rings d ";
$sel .= "JOIN pictures_information p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='" . $in['uid'] . "' ";
$sel .= "AND $grade_sel ";
$result = $DBH->query($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $picture_count = $row['cnt'];
    }
}

// Start by date if we have a new date
if (!empty($in['start_date'])) {
    $sel = "SELECT count(*) cnt ";
    $sel .= "FROM picture_rings d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "WHERE d.uid='" . $in['uid'] . "' ";
    $sel .= "AND p.picture_date>='" . $in['start_date'] . "' ";
    $sel .= "AND $grade_sel ";
    if (!$ring_user) {
        $sel .= "AND p.public='Y' ";
    }
    $partCount = 0;
    $result = $DBH->query ($sel);
    if ($result) {
        if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $partCount = $row['cnt'];
        }
    }
    if ($partCount > 0) {
        $in['start'] = $picture_count - $partCount;
        if ($in['start'] < 0) {
            $in['start'] = 0;
        }
    }
} else {
    $nav_btn = array('first', 'prev', 'next', 'last');
    foreach ($nav_btn as $b) {
        if (!empty($in[$b]) && $in[$b] > 0) {
            $in['start'] = $in[$b];
            break;
        }
    }

}

##############################################################################
# Select Picture data
##############################################################################

$sel = "SELECT p.picture_date, p.grade, d.pid, d.date_last_maint ";
$sel .= "FROM picture_rings d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='" . $in['uid'] . "' ";
if (!$ring_user) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.pid ";
$sel .= 'LIMIT ' . $in['start'] . ',' . $in['number'] . ' ';
$result = $DBH->query($sel);
if (!$result) {
    sys_err("Person '" . $in['uid'] . "' not found");
} else {
    $pic_data = [];
    $cnt = 0;
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $pic_data[$cnt]['pid']   = $row['pid'];
        $pic_data[$cnt]['date']  = $row['picture_date'];
        $pic_data[$cnt]['grade'] = $row['grade'];
        $pic_data[$cnt]['dlm']   = $row['date_last_maint'];
        $cnt++;
    }
}
?>

<html>
<head>
<title>Picture Slide Table</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>
<script language="JavaScript">

var click_update = 0;

/* --------------------- */
/* Verify the input form */
/* --------------------- */

function verifyInput() {

    var f = document.page_select;

    if (isNaN(f.in_number.value)) {
        alert('Number of pictures must be a number');
        return false;
    }

    if (f.in_start_date.value == "") {
        f.in_start_date.value = "1900-01-01 12:00:00";
        alert("Setting empty date to the distant past.  Try refresh again.");
        return false;
    }

    var dt_parts = f.in_start_date.value.split(" ");
    var d = dt_parts[0];
    var t = dt_parts[1];
    var d_regex = /^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$/;
    var d_match = d_regex.exec(d);
    if (!d_match) {
        alert("Invalid date, use format YYYY-MM-NN");
        return false;
    }
    if (parseInt(d_match[1]) < 1000) {
        alert("Invalid year: ".concat(d_match[1]));
        return false;
    }
    if (parseInt(d_match[2]) < 1 || parseInt(d_match[2]) > 12) {
        alert("Invalid month: ".concat(d_match[2]));
        return false;
    }
    if (parseInt(d_match[3]) < 1 || parseInt(d_match[3]) > 31) {
        alert("Invalid day: ".concat(d_match[3]));
        return false;
    }

    return true;
}

</script>
</head>

<body bgcolor="#eeeeff">

<h2 id="personTitle"><?php echo $thisPerson;?></h2>

<div id="homeTop">
<a href="/rings/index.php"><img
       src="/rings-images/icon-home.png"
       alt="Pick a New Ring"
       border="0"></a>
</div>

<?php

$sel = "SELECT p.picture_date, p.grade, d.pid, d.date_last_maint ";
$sel .= "FROM picture_rings d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='" . $in['uid'] . "' ";
if (!$ring_user) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= 'GROUP BY d.pid ';
$sel .= 'ORDER BY p.picture_date,p.pid ';
$sel .= 'LIMIT ' . $in['start'] . ',' . $in['number'] . ' ';
$result = $DBH->query($sel);
if (!$result) {
    sys_err("Person '" . $in['uid'] . ' not found.');
    sys_display_msg();
} else {
    display_page_select($pic_data[0]['date'], $in['number'], $in['uid']);
    sys_display_msg();
    display_slide_table($pic_data);
}
?>
<br/>
<a href="/rings/index.php"><img
       src="/rings-images/icon-home.png"
       alt="Pick a New Ring"
       border="0"></a>
</body>
</html>
