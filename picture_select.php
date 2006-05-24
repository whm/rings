<?PHP
// -------------------------------------------------------------
// picture_select.php
// author: Bill MacAllister
// date: August 15, 2004

// Formatting help
require('inc_format.php');

// Open a session
require('pi_php_auth.inc');
require('pi_php_sessions.inc');

if ($in_login == 2) {
    pi_auth('user|rings');
} elseif ($in_logout>0) {
    session_destroy();
    $_SESSION['prideindustries_directory_user'] = '';
}

require('mysql.php');

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

function make_a_link ($thisUID, $this_date_taken, $thisName) {

    $urlName = urlencode($thisName);
    $thisLink = '<a href="picture_select.php';
    $thisLink .= '?in_ring_uid='.urlencode($thisUID);
    $thisLink .= '&in_ring_next='.urlencode($this_date_taken);
    $thisLink .= '">';
    $thisLink .= '<img src="button.php?in_button='.$urlName.'">';
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

</script>

</head>

<body>
<table border="0" width="100%"><tr><td align="center">

<?php

$next_links = array();

if (isset($in_ring_uid)) {
    
    $base_sel = "SELECT ";
    $base_sel .= "p.date_taken date_taken, ";
    $base_sel .= "p.pid        pid ";
    $base_sel .= "FROM pictures p ";
    $base_sel .= "JOIN picture_details det ";
    $base_sel .= "ON (p.pid=det.pid and det.uid='$in_ring_uid') ";
    
    $order_sel .= "ORDER BY p.date_taken ";
    $order_sel .= "LIMIT 0,1 ";
    
    // look up the next picture 
    if (isset($in_ring_next)) {
        $sel = $base_sel;
        $sel .= "WHERE date_taken>'$in_ring_next' ";
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

    // Get data
    
    $sel = "SELECT * ";
    $sel .= "FROM pictures ";
    $sel .= "WHERE pid=$in_ring_pid ";
    $result = mysql_query ($sel);
    if ($result) {
        $row = mysql_fetch_array($result);
        $this_type = trim($row["picture_type"]);
        $this_pid = $row["pid"];
        $this_date_taken = $row["date_taken"];
        $this_fullbytes = sprintf ('%7.7d', strlen($row["picture"])/1024);
        echo "<img src=\"/rings/display.php";
        echo "?in_pid=$this_pid";
        echo "&in_size=larger\">\n";
        if (strlen($row['description'])>0) {
            echo "<p>\n";
            echo $row['description']."\n";
        }
        echo "<p>\n";
        echo "Date Taken: ".format_date_time($this_date_taken)."\n";
        echo "<p>\n";
        $sel = "SELECT det.uid   uid, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_details det ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid) ";
        $sel .= "WHERE det.pid=$in_ring_pid ";
        $result = mysql_query ($sel);
        if ($result) {
            while ($row = mysql_fetch_array($result)) {
                $next_links[$row['uid']] = $row['display_name'];
            }
        }
    }
    
    // ------------------------------------------
    // display the links
    
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
                             $this_date_taken,
                             $next_links[$in_ring_uid]);
            echo "<br>\n";
        }
        foreach ($next_links as $thisUID => $thisName) {
            if ($in_ring_uid == $thisUID) {continue;}
            $urlName = urlencode($thisName);
            $thisLink = '<a href="picture_select.php';
            $thisLink .= '?in_ring_uid='.urlencode($thisUID);
            $thisLink .= '&in_ring_next='.urlencode($this_date_taken);
            $thisLink .= '">';
            $thisLink .= '<img src="button.php?in_button='.$urlName.'">';
            $thisLink .= "</a>\n";
            echo $thisLink;
        }
    }
    echo '</td align="right" width="600">'."\n";
    echo '</tr>'."\n";
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
    
    if (strlen($_SESSION['prideindustries_directory_user'])>0) {
        echo '<a href="picture_maint?in_pid='.$this_pid.'" target="_blank">';
        echo '<img src="images/icon-edit.png" border="0" ';
        echo 'onMouseOver="showEdit();" onMouseOut="hideEdit();" ';
        echo 'alt="Edit Picture Information">';
        echo "</a>\n";
        
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        
        echo '<a href="'.$PHP_SELF;
        echo '?in_logout=1';
        echo '&in_ring_uid='.$in_ring_uid.'">';
        echo 'Logout';
        echo "</a>\n";
    } else {
        echo '<a href="'.$PHP_SELF;
        echo '?in_login=2';
        echo '&in_ring_uid='.$in_ring_uid.'">';
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

<?php if ($in_slide_show > 0) { 

    echo "function slideShowNext(aUID, aDate, aMilliSec) {\n";
    echo "    var url;\n";
    echo '    url = "'.$PHP_SELF
        . '?in_ring_uid='.$in_ring_uid
        . '&in_ring_next='.urlencode($this_date_taken)
        . '&in_slide_show='.$in_slide_show
        . '";'."\n";
    echo '    location = url;'."\n";
    echo "}\n";

    echo 'setTimeout ("slideShowNext()",'.$in_slide_show.");\n";
}
?>
</script>
