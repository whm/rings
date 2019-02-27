<?PHP
// -------------------------------------------------------------
// picture_select.php
// author: Bill MacAllister
// date: August 15, 2004

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_slide_show     = get_request('in_slide_show');
$in_ring_uid       = get_request('in_ring_uid');
$in_ring_pid       = get_request('in_ring_pid');
$in_ring_next_date = get_request('in_ring_next_date');

if (empty($_SESSION['display_grade'])) {
    $_SESSION['display_grade'] = 'A';
}
$grade_sel = "(p.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR p.grade = '' ";
$grade_sel .= "OR p.grade IS NULL) ";

##############################################################################
# Subroutines
##############################################################################

// ---------------------------------------------
// Get the next pid by date

function get_next_pic_by_date($this_picture_date, $thisPID) {

    global $CONF;
    global $DBH;
    global $grade_sel;

    $rname = 'get_next_pic_by_date';
    $loop_limit = 500;
    $i = 0;
    while ($i < $loop_limit) {
        $next_pid = 0;
        # First handle pictures with the exact same date taken.
        $sel = 'SELECT p.pid ';
        $sel .= 'FROM pictures_information p ';
        $sel .= 'WHERE p.picture_date = ? ';
        $sel .= 'AND p.pid > ? ';
        $sel .= 'AND ' . $grade_sel;
        $sel .= 'ORDER BY p.picture_date, p.pid ';
        $sel .= 'LIMIT 0,1 ';
        if (!$sth = $DBH->prepare($sel)) {
            sys_err("Prepare failed ($rname exact): "
                    . $DBH->error . '(' . $DBH->errno . ')');
            sys_err("Problem statement: $sel");
            return 0;
        }
        $sth->bind_param('si', $this_picture_date, $thisPID);
        if (!$sth->execute()) {
            sys_err('Execute failed ($rname): '
                    . $DBH->error . '(' . $DBH->errno . ')');
            sys_err("Problem statement: $cmd");
            return 0;
        }
        $sth->bind_result($p1);
        if ($sth->fetch()) {
            $next_pid = $p1;
        }
        $sth->close();
        if ($next_pid > 0) {
            if (auth_picture_invisible($next_pid)) {
                $i++;
                $thisPID = $next_pid;
                continue;
            } else {
                break;
            }
        }

        # Select next picture by date
        $sel = 'SELECT p.pid, ';
        $sel .= 'p.picture_date ';            
        $sel .= 'FROM pictures_information p ';
        $sel .= 'WHERE p.picture_date > ? ';
        $sel .= 'AND ' . $grade_sel;
        $sel .= 'ORDER BY p.picture_date ';
        $sel .= 'LIMIT 0,1 ';
        if (!$sth = $DBH->prepare($sel)) {
            sys_err("Prepare failed ($rname): "
                    . $DBH->error . '(' . $DBH->errno . ')');
            sys_err("Problem statement: $sel");
            return 0;
        }
        $sth->bind_param('s', $this_picture_date);
        if (!$sth->execute()) {
            sys_err('Execute failed: ' . $DBH->error . '(' . $DBH->errno . ')');
            sys_err("Problem statement: $cmd");
            return;
        }
        $sth->bind_result($p1, $p2);
        if ($sth->fetch()) {
            $next_pid  = $p1;
            $next_date = $p2;
        }
        $sth->close();
        if ($next_pid > 0 && !auth_picture_invisible($next_pid)) {
            break;
        } else {
            $thisPID           = $next_pid;
            $this_picture_date = $next_date;
            $i++;
        }
    }
    return $next_pid;
}

// ---------------------------------------------
// Get next picture for a given uid

function get_next_pic_by_uid($thisUID, $this_picture_date, $thisPID) {

    global $CONF;
    global $DBH;
    global $grade_sel;

    $rname = 'get_next_pic_by_uid';
    $next_pid = 0;

    # First handle any pictures that have exactly the same picture
    # date.
    $sel = 'SELECT det.pid ';
    $sel .= 'FROM picture_details det ';
    $sel .= 'JOIN pictures_information p ';
    $sel .= 'ON (p.pid = det.pid) ';
    $sel .= 'WHERE det.uid = ? ';
    $sel .= 'AND p.picture_date = ? ';
    $sel .= 'AND det.pid > ? ';
    $sel .= 'ORDER BY p.picture_date, det.pid ';
    $sel .= 'LIMIT 0,1 ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err("Prepare failed ($rname exact): "
                . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $sel");
        return 0;
    }
    $sth->bind_param('ssi', $thisUID, $this_picture_date, $thisPID);
    if (!$sth->execute()) {
        $m = 'Execute failed: ' . $DBH->error . '(' . $DBH->errno . ') ' ;
        $m .= "Problem statement: $cmd";
        sys_err($m);
        return 0;
    }
    $sth->bind_result($p1);
    if ($sth->fetch()) {
        $next_pid = $p1;
    }
    $sth->close();
    if ($next_pid > 0) {
        return $next_pid;
    }

    # If there is no duplicate date then just select the next
    # picture by date.
    $sel = 'SELECT det.pid ';
    $sel .= 'FROM picture_details det ';
    $sel .= 'JOIN pictures_information p ';
    $sel .= 'ON (p.pid = det.pid) ';
    $sel .= 'WHERE det.uid = ? ';
    $sel .= 'AND p.picture_date > ? ';
    $sel .= 'ORDER BY p.picture_date, det.pid ';
    $sel .= 'LIMIT 0,1 ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err("Prepare failed: ($rname)"
                . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $sel");
        return 0;
    }
    $sth->bind_param('ss', $thisUID, $this_picture_date);
    if (!$sth->execute()) {
        sys_err('Execute failed: ' . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $cmd");
        return 0;
    }
    $sth->bind_result($p1);
    if ($sth->fetch()) {
        $next_pid = $p1;
    }
    $sth->close();

    return $next_pid;
}

// ---------------------------------------------
// make a link to another picture

function make_a_link ($thisUID,
                      $thisPID,
                      $this_picture_date,
                      $thisName) {
    global $CONF;
    global $DBH;

    $thisLink = '';
    if (auth_picture_invisible($thisPID)) {return $thisLink;}
    if (auth_person_hidden($thisUID))     {return $thisLink;}

    $next_uid  = $thisUID;
    $next_pid  = '';
    $next_date = '';

    if ($thisUID == 'next-by-date') {
        $next_pid = get_next_pic_by_date($this_picture_date, $thisPID);
        if ($next_pid == 0) {
            $next_pid = get_next_pic_by_date('0001-01-01', $thisPID);
        }
    } else {
        $next_pid = get_next_pic_by_uid($thisUID, $this_picture_date, $thisPID);
        if ($next_pid == 0) {
            $next_pid = get_next_pic_by_uid($thisUID, '0001-01-01', $thisPID);
        }
    }
    if ($next_pid == 0) {
        $next_pid = 1;
    }

    $thisLink .= '<a href="picture_select.php';
    $sep = '?';
    $suffix = '';
    if (empty($next_pid)) {
        $suffix = ' (restart)';
    } else {
        $thisLink .= $sep . 'in_ring_pid=' . urlencode($next_pid);
        $sep = '&';
    }
    $thisLink .= $sep . 'in_ring_uid=' . urlencode($next_uid);
    $sep = '&';
    $thisLink .= '">';

    $urlName = urlencode($thisName . $suffix);
    if (!empty($_SESSION['button_type']) && $_SESSION['button_type'] == 'G') {
        $thisLink .= '<img src="button.php?in_button=' . $urlName . '">';
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
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/pictures.css" rel="stylesheet" type="text/css">
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

function hideReload(){
    getDom("reloadHelpDisplay").style.display = 'none';
}
function showReload(){
    getDom("reloadHelpDisplay").style.display = '';
}

function add_email_list(idx) {
    var win = window.open("add_email_list.php?in_id="+idx,
                          "Add this picture to the email list",
                          "width=400,height=150,status=no");
    return false;
}

</script>

</head>

<body>
<table border="0" width="100%"><tr><td align="center">

<?php

$next_links = array();

if (!empty($CONF['remote_user'])) {
    $invisible_sel = '';
} else {
    $invisible_sel = "AND pp.visibility != 'INVISIBLE' ";
}

# Get first picture for a uid if no pid is specified
if (empty($in_ring_pid) && !empty($in_ring_uid)) {
    $sel = 'SELECT det.pid ';
    $sel .= 'FROM picture_details det ';
    $sel .= 'JOIN pictures_information p ';
    $sel .= 'ON (p.pid = det.pid) ';
    $sel .= 'WHERE det.uid = ? ';
    $sel .= 'ORDER BY p.picture_date, det.pid ';
    $sel .= 'LIMIT 0,1 ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err('Prepare failed (get first picture): ' . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $sel");
        $in_ring_pid = 1;
    }
    $sth->bind_param('s', $in_ring_uid);
    if (!$sth->execute()) {
        sys_err('Execute failed: ' . $DBH->error . '(' . $DBH->errno . ') ');
        sys_err("Problem statement: $sel");
        $in_ring_pid = 1;
    }
    $sth->bind_result($p1);
    if ($sth->fetch()) {
        $in_ring_pid = $p1;
    } else {
        $in_ring_pid = 1;
        sys_err('Problem getting picture for first ' . $in_ring_uid);
    }
    $sth->close();
}

if (!empty($in_ring_pid)) {

    // If the picture contains an invisible person return the caller to
    // the index page.
    if (auth_picture_invisible($in_ring_pid) > 0) {
        http_redirect('/rings/index.php');
        exit;
    }

    $this_size = $_SESSION['display_size'];
    if (empty($this_size)) {
        $this_size = $CONF['display_size'];
    }

    // Get data

    $image_reference = '';
    $sel = "SELECT * ";
    $sel .= "FROM pictures_information p ";
    $sel .= "WHERE pid=$in_ring_pid ";
    if (empty($CONF['remote_user'])) {
        $sel .= "AND public='Y' ";
    }
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_pid          = $row["pid"];
        $this_picture_date = $row["picture_date"];
        $this_dlm          = $row["date_last_maint"];
        $this_fullbytes    = sprintf ('%7.7d', $row["raw_picture_size"]/1024);
        $image_reference
            .= '<img src="display.php'
            . '?in_pid=' . $this_pid
            . '&dlm=' . htmlentities($this_dlm)
            . '&in_size=' . $this_size
            . '">';
        if (!empty($row['description'])) {
            $image_reference .= "<p>\n";
            $image_reference .= $row['description']."\n";
        }
        $image_reference .= "<p>\n";
        $image_reference .= "Picture Date: "
            . format_date_time($this_picture_date) . "\n";
        $image_reference .= "<p>\n";
        $sel = "SELECT det.uid uid, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_details det ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid) ";
        $sel .= "WHERE det.pid=$in_ring_pid ";
        $result=  $DBH->query($sel);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $next_links[$row['uid']] = $row['display_name'];
            }
        }
        $next_links['next-by-date'] = 'Next by Date';
    } else {
        sys_err('ERROR: ' . $result->error);
        sys_err("SQL: $sel");
    }

    // ------------------------------------------
    // display the links

    if (!empty($_SESSION['button_position'])
    && $_SESSION['button_position'] == 'B') {
        echo $image_reference;
    }

    echo '<table border="0" cellpadding="5" width="100%">'."\n";

    echo "<tr>\n";
    echo "\n";
    echo '<td valign="top" align="center">'."\n";
    if (count($next_links)>0) {
        asort($next_links);

        if (!empty($in_ring_uid)) {
            // display the reason we got here first so that it is easy
            // to step through these pictures.
            $l = make_a_link($in_ring_uid,
                             $this_pid,
                             $this_picture_date,
                             $next_links[$in_ring_uid]);
            if (!empty($l)) {
                echo $l."<br/>\n";
            }
        }
        echo '<font  color="white">';
        if ($in_slide_show > 0) {
            $l = "<a href=\"?in_slide_show=0&in_ring_pid=$this_pid\">";
            $l .= '<img src="button.php?in_button=Stop Show">';
            $l .= "</a>\n";
            echo $l;
        } else {
            $c = '';
            foreach ($next_links as $thisUID => $thisName) {
                if ($in_ring_uid == $thisUID) {continue;}
                $l = make_a_link($thisUID,
                                 $this_pid,
                                 $this_picture_date,
                                 $next_links[$thisUID]);
                if (!empty($l)) {
                    echo $c.$l;
                    $c = ' <font color="#000000">.</font> ';
                }
            }
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

    # Defeat the local picture cache by adding a random number to
    # the image tag.
    $i = rand(0, 10000);
    echo '<a href="display.php'
        . '?in_pid=' . $this_pid
        . '&in_size=raw'
        . '&rand=' . $i
        . '" target="_blank">';
    echo '<img src="/rings-images/icon-view-details.png"  border="0" ';
    echo 'onMouseOver="showBig();" onMouseOut="hideBig();" ';
    echo 'alt="Display full size image in a new window.">';
    echo "</a>\n";

    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    echo '<a href="index.php">';
    echo '<img src="/rings-images/icon-home.png" border="0" ';
    echo 'onMouseOver="showSelect();" onMouseOut="hideSelect();" ';
    echo 'alt="Pick a new Picture Ring">';
    echo "</a>\n";

    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    if (!empty($CONF['remote_user'])) {
        $loggedInUser = $CONF['remote_user'];
    }
    if (!empty($loggedInUser)) {
        echo '<img src="/rings-images/icon-mail-send.png" border="0" ';
        echo "onClick=\"add_email_list($this_pid);\" ";
        echo 'onMouseOver="showMail();" onMouseOut="hideMail();" ';
        echo 'alt="Add this picture to the email list">';

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
       
        if ($_SESSION['ring_admin']) {
            echo '<a href="picture_maint.php'
                . '?in_pid='.$this_pid.'" target="_blank">';
            echo '<img src="/rings-images/icon-edit.png" border="0" ';
            echo 'onMouseOver="showEdit();" onMouseOut="hideEdit();" ';
            echo 'alt="Edit Picture Information">';
            echo "</a>\n";
        }
    } else {
        echo '<a href="' . auth_url();
        echo '?in_ring_pid='.$in_ring_pid.'">';
        echo '<img src="/rings-images/login.jpg" border="0">';
        echo "</a>\n";
    }

    echo '<p id="mailHelpDisplay">'."\n";
    echo "Select this picture to email\n";
    echo "</p>\n";

    echo '<p id="bigHelpDisplay">'."\n";
    echo "Display picture full size\n";
    echo "<br/>\n";
    echo "($this_fullbytes kbytes)\n";
    echo "</p>\n";

    echo '<p id="selectHelpDisplay">'."\n";
    echo "Select another Picture Ring\n";
    echo "</p>\n";

    echo '<p id="editHelpDisplay">'."\n";
    echo "Edit Picture Ring Details\n";
    echo "</p>\n";

    echo '<p id="reloadHelpDisplay">'."\n";
    echo "Re-Load a picture from a file.\n";
    echo "</p>\n";

    echo "</td>\n";
    echo "</tr>\n";
    echo "</table>\n";

}

sys_display_msg();

?>

</Body>
</html>
<script language="JavaScript">

hideMail();
hideBig();
hideSelect();
hideEdit();
hideReload();

<?php if ($in_slide_show > 0) {

    $display_seconds = $_SESSION['display_seconds'];
    if ($display_seconds<3) {$display_seconds = 3;}

    echo "function slideShowNext(aUID, aDate, aMilliSec) {\n";
    echo "    var url;\n";
    echo '    url = "'.ring_url()
        . '?in_ring_uid='.$in_ring_uid
        . '&in_ring_pid='.urlencode($this_pid)
        . '&in_ring_next_date='.urlencode($this_picture_date)
        . '&in_slide_show='.$in_slide_show
        . '";'."\n";
    echo '    location = url;'."\n";
    echo "}\n";

    $thisMilli = $display_seconds * 1000;
    echo 'setTimeout ("slideShowNext()",'.$thisMilli.");\n";
}
?>
</script>
