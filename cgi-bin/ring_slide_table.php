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
// Function to exit without displaying anything and return to
// the main index page.

function back_to_index () {

    echo "<html>\n";
    echo "<head>\n";
    echo "<meta http-equiv=\"refresh\" ";
    echo '    content="0; URL=http://'.$_SERVER['SERVER_NAME'].'/rings">'."\n";
    echo "<title>Rings of Pictures</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo '<a href="rings">Rings of Pictures</a>'."\n";
    echo "</body>\n";
    echo "</html>\n";
    sys_err('Ring Not Found.');

    exit;
}

// ----------------------------------------------------------
// Display page selection form

function display_page_select($start_date, $number, $uid) {
?>
<form name="page_select"
      action="<?php echo $_SERVER['PHP_SELF'];?>"
      onsubmit="return verifyInput()"
      method="post">
<fieldset>
  <legend>Page Control</legend>
  <label class="field">Starting Date</label>
     <input type="text" name="in_start_date"
            size="14"
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

    $sel = 'SELECT pid ';
    $sel .= 'FROM pictures_information ';
    $sel .= "WHERE pid != $pid ";
    $sel .= "AND raw_picture_size = " . $pic['raw_picture_size'] . ' ';
    $sel .= "AND file_name = '" . $pic['file_name'] . "' ";
    $dup_fld_list = array('camera', 'shutter_speed', 'fstop');
    foreach ($dup_fld_list as $f) {
      if (!empty($pic[$f])) {
          $sel .= "AND $f = '" . $pic[$f] . "' ";
      } else {
          $sel .= "AND $f IS NULL ";
      }
    }
    $sel .= "ORDER BY pid ";

    //$duplicate_list .= $sel;
    $result = $DBH->query ($sel);
    $dup_array = array();
    if ($result) {
        $comma = '';
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            array_push($dup_array, $row['pid']);
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
    
    $action_form = 'ring_slide_table_action.php';
?>
    <form name="slide_table" method="post">

    <fieldset>
    <legend>Slide Table</legend>
<?php
    display_table_nav();

    $cnt = 0;
    foreach ($pic_data as $cnt => $pic) {
        $pic_href = 'picture_select.php?in_ring_pid=' . $pic['pid'];
        $pic_edit = 'picture_maint.php?in_pid=' . $pic['pid'];
        $pic_src  = 'display.php?in_pid=' . $pic['pid']
            . '&in_size=' . $CONF['index_size'];

        // check for duplicates
        $dups = dup_check($pic['pid']);
        $pic_dups = '';
        foreach ($dups as $d) {
            $d .= "</br>\n";
            $d = '<font color="red">Duplicate: ';
            $d .= '<a href="picture_maint.php?in_pid=' . $d . '>';
            $d .= "</font>\n";
            $pic_dups .= $d;
        }
?>
    <div class="sort_image">
      <a href="<?php echo $pic_href;?>" target="_blank">
        <img src="<?php echo $pic_src; ?>" border="0">
      </a>
      <div class="caption">
        <a href="<?php echo $pic_edit; ?>"
           target="_blank">
         <?php echo $pic['pid']; ?>
        </a>
        <?php echo $pic_dups; ?>
        <input type="text" name="in_date_<?php echo $cnt;?>"
               value="<?php echo $pic['date'];?>" size="12">
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
    $in['number'] = 10 * 7;
}

// Bail out if we don't have a selection
if (empty($_SERVER['REMOTE_USER']) && auth_person_hidden($in['uid']) > 0) {
    back_to_index();
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

// get a count of the number of pictures in total
$picture_count = 0;
$sel = "SELECT count(*) cnt ";
$sel .= "FROM picture_details d ";
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
    $sel .= "FROM picture_details d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "WHERE d.uid='" . $in['uid'] . "' ";
    $sel .= "AND p.picture_date>='" . $in['start_date'] . "' ";
    $sel .= "AND $grade_sel ";
    if (empty($_SERVER['REMOTE_USER'])) {
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
        if (isset($in[$b]) && $in[$b] > 0) {
            $in['start'] = $in[$b];
            break;
        }
    }

}

##############################################################################
# Select Picture data
##############################################################################

$sel = "SELECT p.picture_date, d.pid, d.date_last_maint ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='" . $in['uid'] . "' ";
if (empty($_SERVER['REMOTE_USER'])) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.pid ";
$sel .= 'LIMIT ' . $in['start'] . ',' . $in['number'] . ' ';
$result = $DBH->query($sel);
if (!$result) {
    sys_err("Person '" . $in['uid'] . "' not found.</br>");
} else {
    $pic_data = [];
    $cnt = 0;
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $pic_data[$cnt]['pid']  = $row['pid'];
        $pic_data[$cnt]['date'] = $row['picture_date'];
        $pic_data[$cnt]['dlm']  = $row['date_last_maint'];
        $cnt++;
    }
}
?>

<html>
<head>
<title>Picture Slide Table</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
<script language="JavaScript">

var click_update = 0;

/* --------------------- */
/* Verify the input form */
/* --------------------- */

function verifyInput() {

    var f = document.page_select;

    if (f.in_number.isNaN) {
        alert('Number of pictures must be a number');
        return false;
    }

    return true;
}

</script>
</head>

<body bgcolor="#eeeeff">

<h2><?php echo $thisPerson;?></h2>

<?php

$sel = "SELECT p.picture_date, d.pid, d.date_last_maint ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='" . $in['uid'] . "' ";
if (empty($_SERVER['REMOTE_USER'])) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= 'GROUP BY d.pid ';
$sel .= 'ORDER BY p.picture_date,p.pid ';
$sel .= 'LIMIT ' . $in['start'] . ',' . $in['number'] . ' ';
$result = $DBH->query($sel);
if (!$result) {
    sys_err("Person '" . $in['uid'] . ' not found.</br>');
    sys_display_msg();
} else {
    display_page_select($pic_data[0]['date'], $in['number'], $in['uid']);
    sys_display_msg();
    display_slide_table($pic_data);
}
?>
<br>
<a href="/rings/index.php"><img
       src="/rings-images/icon-home.png"
       alt="Pick a New Ring"
       border="0"></a>
</body>
</html>
