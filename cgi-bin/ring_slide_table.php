<?PHP
// -------------------------------------------------------------
// ring_thumbnails.php
// author: Bill MacAllister
// date: 26-Nov-2004

// Init session, connect to database
require('inc_ring_init.php');

// Form or URL inputs
$in_number     = get_request('in_number');
$in_start_date = get_request('in_start_date');
$in_last       = get_request('in_last');
$in_start      = get_request('in_start');
$in_next       = get_request('in_next');
$in_prev       = get_request('in_prev');
$in_uid        = get_request('in_uid');

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
    sys_msg_err('Ring Not Found.');

    exit;
}

function display_page_select($start_date) {
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<fieldset>
  <legend>Page Control</legend>
  <p><label class="field">Starting Date</label>
     <input type="text" name="in_start_date"
            value="<?php echo $start_date;?>">
     <input type="submit" name="btn_refresh" value="Refresh">
  </p>
  <p><label class="field">Number of Pictures to Display</label>
     <input type="text" 
            name="in_number" 
            size="10" 
            value="<?php echo $in_number;?>">
  </p>
</fieldset>
</form>
<?php
    return;
}

// Display First, Previous, Next, Last
function display_table_nav() {
    
    global $display_first;
    global $display_prev;
    global $display_next;
    global $display_last;
    
    if ($display_first > 0) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=0">First</a>';
        echo " - ";
    }
    if ($display_prev > -1) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $display_prev
            . '">Previous</a>';
    }

    if ($display_next > 0) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $display_next
            . '">Next</a>';
    }
    if ($display_last > 0) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $display_last
            . '">Last</a>';
    }
    return;
}

// ----------------------------------------------------------
// Function to display the pictures in a form that will allow dates
// to be adusted.

function display_slide_table($pic_data) {

    global $CONF;
    
    display_table_nav();

    $action_form = $_SERVER['PHP_SELF'] . '_action'
?>
    <br/>
    <form method="post" action="<?php echo $action_form;?>">
    <p>
    <input type="submit" name="btn_refresh" value="Refresh">
    </p>
    <fieldset>
    <legend>Slide Table</legend>
<?php
    foreach ($pic_data as $cnt => $pic) {
        $s = $CONF['index_size'];
        $pic_href = 'picture_select.php?in_ring_pid=' . $pic['pid'];
        $pic_edit = 'picture_maint.php?in_pid=' . $pic['pid'];
        $pic_src  = 'display.php?in_pid=' . $pic['pid']
            . '&in_size=' . $CONF['index_size'];
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
    }
?>
    </fieldset>
    
    <p>
    <input type="submit" name="btn_refresh" value="Refresh">
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

$in_start = empty($in_start) ? 0 : $in_start;

if ($in_number == 0) {
    if (!empty($_SESSION['s_thumbs_per_page'])) {
        $in_number = $_SESSION['s_thumbs_per_page'];
    } else {
        $in_number = 10 * 7;
    }
}
$_SESSION['s_thumbs_per_page'] = $in_number;

if (empty($in_uid)) {
    $in_uid = $_SESSION['s_uid'];
} else {
    $_SESSION['s_uid'] = $in_uid;
}

if (empty($_SERVER['REMOTE_USER']) && auth_person_hidden($in_uid) > 0) {
    back_to_index();
}

$thisPerson = "$in_uid";
$sel = "SELECT display_name ";
$sel .= "FROM people_or_places pp ";
$sel .= "WHERE uid='$in_uid' ";
$result = $DBH->query($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $thisPerson = $row['display_name'];
    }
}
if (empty($row['display_name'])) {
    back_to_index();
}

$sel = "SELECT count(*) cnt ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
$sel .= "AND $grade_sel ";

$thisCount = 0;
$result = $DBH->query($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $thisCount = $row['cnt'];
    }
}
if (!empty($in_start_date)) {
    $sel = "SELECT count(*) cnt ";
    $sel .= "FROM picture_details d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "WHERE d.uid='$in_uid' ";
    $sel .= "AND p.picture_date>'$in_start_date' ";
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
        $in_start = $thisCount - $partCount;
        if ($in_start < 0) {$in_start=0;}
    }
}

##############################################################################
# Select Picture data
##############################################################################

$sel = "SELECT p.picture_date, d.pid, d.date_last_maint ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
if (empty($_SERVER['REMOTE_USER'])) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.pid ";
$sel .= "LIMIT $in_start, $in_number ";
$result = $DBH->query($sel);
if (!$result) {
    sys_msg_err("Person '$in_uid' not found.<br>");
} else {
    $display_first = 0;
    $display_prev = -1;
    $display_next = 0;
    $display_last = 0;
    if ($in_start > 0) {
        $in_prev = $in_start - $in_number;
        if ($in_prev < 0) {
            $in_prev = 0;
        }
        $display_first = 0;
        if ($in_prev > 0) {
            $display_first = 1;
        }
        $display_prev = $in_prev;
    }
    $in_next = $in_start + $in_number;
    if ($in_next < $thisCount) {
        if ($in_next+$in_number > $thisCount) {
            $display_next = $thisCount - $in_number;
        }
        if ($in_next+$in_number < $thisCount) {
            $display_last = $thisCount - $in_number;
        }
    }
    
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
</head>

<body bgcolor="#eeeeff">

<h2><?php echo $thisPerson;?></h2>

<?php 

$sel = "SELECT p.picture_date, d.pid, d.date_last_maint ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
if (empty($_SERVER['REMOTE_USER'])) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.pid ";
$sel .= "LIMIT $in_start, $in_number ";
$result = $DBH->query($sel);
if (!$result) {
    sys_err("Person '$in_uid' not found.");
} else {
    display_page_select($pic_data);
    display_slide_table($pic_data);
}
?>
<br>
<a href="/rings/index.php"><img 
       src="/rings-images/icon-home.png" 
       alt="Pick a New Ring"
       border="0"></a>
<?php sys_display_msg(); ?>

</body>
</html>
