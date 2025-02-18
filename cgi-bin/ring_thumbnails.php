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

// ============
// Main routine 

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

if (!$ring_user && auth_person_hidden($in_uid) > 0) {
    back_to_index('Invalid person selection');
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
$sel .= "FROM picture_rings d ";
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
    $sel .= "FROM picture_rings d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "WHERE d.uid='$in_uid' ";
    $sel .= "AND p.picture_date>'$in_start_date' ";
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
        $in_start = $thisCount - $partCount;
        if ($in_start < 0) {$in_start=0;}
    }
}

?>

<html>
<head>
<title>Picture Thumbnails</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<h2><?php echo $thisPerson;?></h2>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

<table border="0">
<tr><td align="right">Starting Date:</td>
    <td><input type="text" 
               name="in_start_date">
    </td>
    <td><input type="submit" name="btn_refresh" value="Refresh"></td>
</tr>
<tr><td align="right">Number of Pictures to Display:</td>
    <td><input type="text" 
               name="in_number" 
               size="10" 
               value="<?php echo $in_number;?>">
    </td>
    <td></td>
</tr>
</table>
</form>

<?php 

$sel = "SELECT p.picture_date, d.pid, d.date_last_maint ";
$sel .= "FROM picture_rings d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
if (!$ring_user) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.pid ";
$sel .= "LIMIT $in_start, $in_number ";
$result = $DBH->query($sel);
if (!$result) {
    echo "Person '$in_uid' not found.<br/>\n";
} else {

    echo "<table border=\"1\"><tr><td>\n";

    echo "<tr><td>";
    echo " <table broder=\"0\" width=\"100%\">\n";
    echo " <tr>\n";
    echo "  <td>\n";
    if ($in_start > 0) {
        $in_prev = $in_start - $in_number;
        if ($in_prev < 0) {$in_prev = 0;}
        if ($in_prev > 0) {
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=0">First</a>';
            echo " - ";
        }
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $in_prev
            . '">Previous</a>';
    } else {
        echo "&nbsp;";
    }
    echo "  </td>\n"; 
    echo "  <td align=\"right\">\n"; 
    $in_next = $in_start + $in_number;
    if ($in_next < $thisCount) {
        if ($in_next+$in_number > $thisCount) {
            $in_next = $thisCount - $in_number;
        }
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $in_next
            . '">Next</a>';
        if ($in_next+$in_number < $thisCount) {
            $in_last = $thisCount - $in_number;
            echo " - ";
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?in_start=' . $in_last
                . '">Last</a>';
        }
    } else {
        echo "&nbsp;";
    }
    echo "  </td>\n"; 
    echo " </tr>\n"; 
    echo " </table>\n"; 
    echo "</td></tr>\n";

    echo "<tr><td>";
    $cnt = 20;
    $hr = '';
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($cnt>6) {
            echo "<br/>\n";
            echo $hr;
            echo $row['picture_date']."\n";
            echo "<br/>\n";
            $cnt = 0;
            $hr = "<hr>\n";
        }
        $pid = $row["pid"];
        $pic_href = '<a href="picture_select.php'
            . '?in_ring_pid=' . $pid 
            . '&in_ring_uid=' . $in_uid
            . '" '
            . 'target="_blank">';
        $thumb = '<img src="display.php'
            . '?in_pid=' . $pid
            . '&in_size=' . $CONF['index_size']
            . '&dlm=' . htmlentities($row['date_last_maint']) . '" '
            . 'border="0">';
        echo $pic_href . $thumb . "</a>\n";
        $cnt++;
    }
    echo "</td></tr></table>\n";
}
?>
<br/>
<a href="/rings/index.php"><img 
       src="/rings-images/icon-home.png" 
       alt="Pick a New Ring"
       border="0"></a>
<?php sys_display_msg(); ?>

</body>
</html>
