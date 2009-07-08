<?PHP
// -------------------------------------------------------------
// picture_select.php
// author: Bill MacAllister
// date: August 15, 2004

// Formatting help
require('inc_format.php');

// Open a session
require('whm_php_auth.inc');
require('whm_php_sessions.inc');

if ($in_login == 2) {
    whm_auth('user|rings');
} elseif ($in_logout>0) {
    session_destroy();
    $_SESSION['whm_directory_user'] = '';
}

require ('/etc/whm/rings_dbs.php');

if (strlen($_SESSION['display_grade']) == 0) {
    $_SESSION['display_grade'] = 'A';
}
$grade_sel = "(p.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR p.grade = '' ";
$grade_sel .= "OR p.grade IS NULL) ";

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
    $msg = $msg . "<br>Error connecting to MySQL host $mysql_host";
    echo "$msg";
    exit;
}
$result = mysql_select_db($mysql_db);
if (!$result) {
    $msg = $msg . "<br>Error connecting to MySQL db $mysql_db";
    echo "$msg";
    exit;
}

// ---------------------------------------------
// make a link to another picture

function make_a_link ($thisUID, 
                      $thisPID,
                      $this_picture_date, 
                      $this_seq,
                      $thisName) {

    $urlName = urlencode($thisName);
    $thisLink = '<a href="picture_select.php';
    $thisLink .= '?in_ring_uid='.urlencode($thisUID);
    $thisLink .= '&in_ring_pid='.urlencode($thisPID);
    $thisLink .= '&in_ring_next_date='.urlencode($this_picture_date);
    $thisLink .= '&in_ring_next_seq='.urlencode($this_seq);
    $thisLink .= '">';
    if ($_SESSION['button_type'] == 'G') {
        $thisLink .= '<img src="button.php?in_button='.$urlName.'">';
    } else {
        $thisLink .= $thisName;
    }
    $thisLink .= "</a>\n";

    return $thisLink;

}

?>
<html>
<head>
<title>Rings</title>

<style type="text/css">
 <?php include('styles/pictures.css');?>
</style>


<script language="JavaScript">

function getDom(objectname){
    if (document.all) return document.all[objectname];
    else return document.getElementById(objectname);
}

function hideMail(){
    getDom("mailHelpDisplay").style.display = 'none';
}
function showMail(){
    getDom("mailHelpDisplay").style.display = '';
}

function hideBig(){
    getDom("bigHelpDisplay").style.display = 'none';
}
function showBig(){
    getDom("bigHelpDisplay").style.display = '';
}

function hideSelect(){
    getDom("selectHelpDisplay").style.display = 'none';
}
function showSelect(){
    getDom("selectHelpDisplay").style.display = '';
}

function hideEdit(){
    getDom("editHelpDisplay").style.display = 'none';
}
function showEdit(){
    getDom("editHelpDisplay").style.display = '';
}

function hideGrade(){
    getDom("gradeHelpDisplay").style.display = 'none';
}
function showGrade(){
    getDom("gradeHelpDisplay").style.display = '';
}

function hideReload(){
    getDom("reloadHelpDisplay").style.display = 'none';
}
function showReload(){
    getDom("reloadHelpDisplay").style.display = '';
}

function get_vote(idx,username) {
    var win = window.open("get_vote.php?id="+idx+"&username="+username,
                          "Give the Picture a Grade",
                          "width=400,height=150,status=no");
    return false;
}

</script>

</head>

<body>
<table border="0" width="100%"><tr><td align="center">

<?php

$next_links = array();

$private_sel = " AND pp.public_flag != 'N' ";
if (strlen($_SESSION['whm_directory_user'])>0) { $private_sel = ''; }

if (isset($in_ring_uid)) {
    
    $base_sel = "SELECT ";
    $base_sel .= "p.picture_date     picture_date, ";
    $base_sel .= "p.picture_sequence picture_sequence, ";
    $base_sel .= "p.pid              pid ";
    $base_sel .= "FROM pictures_information p ";
    $base_sel .= "JOIN picture_details det ";
    if ($in_ring_uid == 'next-by-date') {
        // Just selecting the next picture by date
        $base_sel .= "ON (p.pid=det.pid) ";
    } else {
        // Selecting by a specific ring uid
        $base_sel .= "ON (p.pid=det.pid and det.uid='$in_ring_uid') ";
    }
    $base_sel .= "JOIN people_or_places pp ";
    $base_sel .= "ON (pp.uid='$in_ring_uid' $private_sel) ";
    $order_sel .= "ORDER BY p.picture_date, p.picture_sequence ";
    $order_sel .= "LIMIT 0,1 ";
    
    // look up the next picture 
    if (isset($in_ring_next_date)) {
        $sel = $base_sel;
        $sel .= "WHERE ((picture_date='$in_ring_next_date' ";
        $sel .= "AND picture_sequence>$in_ring_next_seq) ";
        $sel .= "OR (picture_date>'$in_ring_next_date')) ";
        $sel .= "AND p.pid != $in_ring_pid ";
        if (strlen($_SESSION['whm_directory_user']) == 0) {
            $sel .= "AND public='Y' ";
        }
        $sel .= "AND $grade_sel ";
        $sel .= $order_sel;
        $result = mysql_query ($sel);
        if ($result) {
            $row = mysql_fetch_array($result);
            $in_ring_pid = $row['pid'];
        }
    }
    
    // either there was no previous picture or we are wrapping around
    if (strlen($in_ring_pid) == 0) {
        $sel = $base_sel;
        $sel .= $order_sel;
        $result = mysql_query ($sel);
        if ($result) {
            $row = mysql_fetch_array($result);
            $in_ring_pid = $row['pid'];
        }
    }  
}

if (isset($in_ring_pid)) {

    $thisSize = $_SESSION['display_size'];
    if (!($thisSize=='large' 
          || $thisSize=='larger' 
          || $thisSize=='1280_1024' 
          || $thisSize == 'raw')) {
        $thisSize = 'larger';
    }

    // Get data
    
    $sel = "SELECT * ";
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid=$in_ring_pid ";
    if (strlen($_SESSION['whm_directory_user']) == 0) {
        $sel .= "AND public='Y' ";
    }
    $result = mysql_query ($sel);
    $image_reference = '';
    if ($result) {
        $row = mysql_fetch_array($result);
        $this_type = trim($row["picture_type"]);
        $this_pid = $row["pid"];
        $this_picture_date = $row["picture_date"];
        $this_picture_seq = $row["picture_sequence"];
        $this_fullbytes = sprintf ('%7.7d', $row["raw_picture_size"]/1024);
        $image_reference .= "<img src=\"/rings/display.php";
        $image_reference .= "?in_pid=$this_pid";
        $image_reference .= "&in_size=$thisSize\">\n";
        if (strlen($row['description'])>0) {
            $image_reference .= "<p>\n";
            $image_reference .= $row['description']."\n";
        }
        $image_reference .= "<p>\n";
        $image_reference .= "Date Taken: ".format_date_time($this_picture_date)."\n";
        $image_reference .= "<p>\n";
        $sel = "SELECT det.uid   uid, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_details det ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid $private_sel) ";
        $sel .= "WHERE det.pid=$in_ring_pid ";
        $result = mysql_query ($sel);
        if ($result) {
            while ($row = mysql_fetch_array($result)) {
                $next_links[$row['uid']] = $row['display_name'];
            }
        }
        $next_links['next-by-date'] = 'Next by Date';
    }
    
    // ------------------------------------------
    // display the links
    
    if ($_SESSION['button_position'] == 'B') {
        echo $image_reference;
    }

    echo '<table border="0" cellpadding="5" width="100%">'."\n";

    echo "<tr>\n";
    echo "\n";
    echo '<td valign="top" align="center">'."\n";
    if (count($next_links)>0) {
        asort($next_links);

        if (strlen($in_ring_uid)>0) {
            // display the reason we got here first so that it is easy
            // to step through these pictures.
            echo make_a_link($in_ring_uid, 
                             $this_pid,
                             $this_picture_date,
                             $this_picture_seq, 
                             $next_links[$in_ring_uid]);
            echo "<br>\n";
        }
        echo '<font  color="white">';
        $c = '';
        foreach ($next_links as $thisUID => $thisName) {
            if ($in_ring_uid == $thisUID) {continue;}
            echo $c.make_a_link($thisUID, 
                                $this_pid,
                                $this_picture_date, 
                                $this_picture_seq,
                                $next_links[$thisUID]);
            $c = ' - ';
        }
        echo '</font>';
    }
    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";

    if ($_SESSION['button_position'] != 'B') {
        echo $image_reference;
    }

    echo '<table border="0" cellpadding="5" width="100%">'."\n";
    echo "\n";
    echo '<tr>'."\n";
    echo '<td valign="top" align="center">'."\n";
    
    echo '<a href="display.php?in_pid='.$this_pid.'" target="_blank">';
    echo '<img src="images/icon-view-details.png"  border="0" ';
    echo 'onMouseOver="showBig();" onMouseOut="hideBig();" ';
    echo 'alt="Display full size image in a new window.">';
    echo "</a>\n";
    
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    
    echo '<a href="picture_email?in_pid='.$this_pid.'" target="_blank" >';
    echo '<img src="images/icon-mail-send.png" border="0" ';
    echo 'onMouseOver="showMail();" onMouseOut="hideMail();" ';
    echo 'alt="Send this picture to someone">';
    echo "</a>\n";
    
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    
    echo '<a href="index.php">';
    echo '<img src="images/rings.png" border="0" ';
    echo 'onMouseOver="showSelect();" onMouseOut="hideSelect();" ';
    echo 'alt="Pick a new Picture Ring">';
    echo "</a>\n";
    
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    
    $loggedInUser = $_SESSION['whm_directory_user'];
    if (strlen($loggedInUser)>0) {
        echo '<a href="picture_maint?in_pid='.$this_pid.'" target="_blank">';
        echo '<img src="images/icon-edit.png" border="0" ';
        echo 'onMouseOver="showEdit();" onMouseOut="hideEdit();" ';
        echo 'alt="Edit Picture Information">';
        echo "</a>\n";
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        echo '<a href="picture_reload?in_pid='.$this_pid.'" target="_blank">';
        echo '<img src="images/icon-reload.png" border="0" ';
        echo 'onMouseOver="showReload();" onMouseOut="hideReload();" ';
        echo 'alt="ReLoad Picture Information">';
        echo "</a>\n";
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        
        echo '<img src="images/icon-grade.png"  border="0" ';
        echo "onClick=\"get_vote($this_pid,'$loggedInUser');\" ";
        echo 'onMouseOver="showGrade();" onMouseOut="hideGrade();" ';
        echo 'alt="Give this picture a grade.">';
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        echo '<a href="'.$PHP_SELF;
        echo '?in_logout=1';
        echo '&in_ring_pid='.$in_ring_pid.'">';
        echo 'Logout';
        echo "</a>\n";
    } else {
        echo '<a href="'.$PHP_SELF;
        echo '?in_login=2';
        echo '&in_ring_pid='.$in_ring_pid.'">';
        echo 'Login'."</a>\n";
    }
    
    echo '<p id="mailHelpDisplay">'."\n";
    echo "Email this Picture\n";
    echo "<br>\n";
    echo "(Requires a login id)\n";
    echo "</p>\n";
    
    echo '<p id="bigHelpDisplay">'."\n";
    echo "Display picture full size\n";
    echo "<br>\n";
    echo "($this_fullbytes kbytes)\n";
    echo "</p>\n";
    
    echo '<p id="selectHelpDisplay">'."\n";
    echo "Select another Picture Ring\n";
    echo "</p>\n";
    
    echo '<p id="editHelpDisplay">'."\n";
    echo "Edit Picture Ring Details\n";
    echo "</p>\n";
    
    echo '<p id="gradeHelpDisplay">'."\n";
    echo "Set the Grade for this picture.\n";
    echo "</p>\n";
    
    echo '<p id="reloadHelpDisplay">'."\n";
    echo "Re-Load a picture from a file.\n";
    echo "</p>\n";
    
    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";
    
}

?>

</Body>
</html>
<script language="JavaScript">

hideMail();
hideBig();
hideSelect();
hideEdit();
hideGrade();
hideReload();

<?php if ($in_slide_show > 0) { 

    $display_seconds = $_SESSION['display_seconds'];
    if ($display_seconds<3) {$display_seconds = 3;}

    echo "function slideShowNext(aUID, aDate, aMilliSec) {\n";
    echo "    var url;\n";
    echo '    url = "'.$PHP_SELF
        . '?in_ring_uid='.$in_ring_uid
        . '&in_ring_pid='.urlencode($this_pid)
        . '&in_ring_next_date='.urlencode($this_picture_date)
        . '&in_ring_next_seq='.urlencode($this_picture_seq)
        . '&in_slide_show='.$in_slide_show
        . '";'."\n";
    echo '    location = url;'."\n";
    echo "}\n";

    $thisMilli = $display_seconds * 1000;
    echo 'setTimeout ("slideShowNext()",'.$thisMilli.");\n";
}
?>
</script>
