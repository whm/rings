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
$in_ring_next_seq  = get_request('in_ring_next_seq');
$in_ring_pid       = get_request('in_ring_pid');
$in_ring_next_date = get_request('in_ring_next_date');

if (empty($_SESSION['display_grade'])) {
    $_SESSION['display_grade'] = 'A';
}
$grade_sel = "(p.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR p.grade = '' ";
$grade_sel .= "OR p.grade IS NULL) ";

// ---------------------------------------------
// make a link to another picture

function make_a_link ($thisUID,
                      $thisPID,
                      $this_picture_date,
                      $this_seq,
                      $thisName) {

    $thisLink = '';
    if (auth_picture_invisible($thisPID)) {return $thisLink;}
    if (auth_person_hidden($thisUID))    {return $thisLink;}

    $urlName = urlencode($thisName);
    $thisLink .= '<a href="picture_select.php';
    $thisLink .= '?in_ring_uid='.urlencode($thisUID);
    $thisLink .= '&in_ring_pid='.urlencode($thisPID);
    $thisLink .= '&in_ring_next_date='.urlencode($this_picture_date);
    $thisLink .= '&in_ring_next_seq='.urlencode($this_seq);
    $thisLink .= '">';
    if (!empty($_SESSION['button_type']) && $_SESSION['button_type'] == 'G') {
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
    var win = window.open("get_vote.php?in_id="+idx+"&username="+username,
                          "Give the Picture a Grade",
                          "width=400,height=150,status=no");
    return false;
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

if (!empty($_SERVER['REMOTE_USER'])) {
    $invisible_sel = '';
} else {
    $invisible_sel = "AND pp.visibility != 'INVISIBLE' ";
}

if (!empty($in_ring_uid)) {

    $new_pid   = '';
    $order_sel = '';
    
    // Build selection for next links
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
    if (!empty($invisible_sel)) {
        $base_sel .= "JOIN people_or_places pp ";
        $base_sel .= "ON (pp.uid = det.uid $invisible_sel) ";
    }
    $order_sel .= "ORDER BY p.picture_date, p.picture_sequence ";

    // look up the next picture
    if (!empty($in_ring_next_date)) {
        $sel = $base_sel;
        $sel .= "WHERE ((picture_date='$in_ring_next_date' ";
        $sel .= "AND picture_sequence>$in_ring_next_seq) ";
        $sel .= "OR (picture_date>'$in_ring_next_date')) ";
        $sel .= "AND p.pid != $in_ring_pid ";
        if (empty($_SERVER['REMOTE_USER'])) {
            $sel .= "AND public='Y' ";
        }
        $sel .= "AND $grade_sel ";
        $sel .= $order_sel;
        $result = $DBH->query($sel);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ( auth_picture_invisible($row['pid']) == 0 ) {
                    $new_pid = $row['pid'];
                    break;
                }
            }
        }
    }

    // either there was no previous picture or we are wrapping around
    if (empty($new_pid)) {
        $sel = $base_sel;
        $sel .= $order_sel;
        $result = $DBH->query($sel);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ( auth_picture_invisible($row['pid']) == 0 ) {
                    $new_pid = $row['pid'];
                    break;
                }
            }
        }
    }

    if (empty($new_pid)) {
        http_redirect('/rings/index.php');
        exit;
    } else {
        $in_ring_pid = $new_pid;
    }
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
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid=$in_ring_pid ";
    if (empty($_SERVER['REMOTE_USER'])) {
        $sel .= "AND public='Y' ";
    }
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_pid          = $row["pid"];
        $this_picture_date = $row["picture_date"];
        $this_picture_seq  = $row["picture_sequence"];
        $this_fullbytes    = sprintf ('%7.7d', $row["raw_picture_size"]/1024);
        $image_reference   .= "<img src=\"display.php";
        $image_reference   .= "?in_pid=$this_pid";
        $image_reference   .= "&in_size=$this_size\">\n";
        if (!empty($row['description'])) {
            $image_reference .= "<p>\n";
            $image_reference .= $row['description']."\n";
        }
        $image_reference .= "<p>\n";
        $image_reference .= "Date Taken: "
            . format_date_time($this_picture_date) . "\n";
        $image_reference .= "<p>\n";
        $sel = "SELECT det.uid   uid, ";
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
        $_SESSION['msg'] .= 'ERROR: ' . $result->error . "<b>\n";
        $_SESSION['msg'] .= "SQL: $sel<br>\n";
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
                             $this_picture_seq,
                             $next_links[$in_ring_uid]);
            if (!empty($l)) {echo $l."<br>\n";}
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
                                 $this_picture_seq,
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

    echo '<a href="display.php?in_pid='.$this_pid.'" target="_blank">';
    echo '<img src="/rings-images/icon-view-details.png"  border="0" ';
    echo 'onMouseOver="showBig();" onMouseOut="hideBig();" ';
    echo 'alt="Display full size image in a new window.">';
    echo "</a>\n";

    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    echo '<a href="index.php">';
    echo '<img src="/rings-images/rings.png" border="0" ';
    echo 'onMouseOver="showSelect();" onMouseOut="hideSelect();" ';
    echo 'alt="Pick a new Picture Ring">';
    echo "</a>\n";

    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

    if (!empty($_SERVER['REMOTE_USER'])) {
        $loggedInUser = $_SERVER['REMOTE_USER'];
    }
    if (!empty($loggedInUser)) {
        echo '<img src="/rings-images/icon-grade.png"  border="0" ';
        echo "onClick=\"get_vote($this_pid,'$loggedInUser');\" ";
        echo 'onMouseOver="showGrade();" onMouseOut="hideGrade();" ';
        echo 'alt="Give this picture a grade.">';

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        echo '<img src="/rings-images/icon-mail-send.png" border="0" ';
        echo "onClick=\"add_email_list($this_pid);\" ";
        echo 'onMouseOver="showMail();" onMouseOut="hideMail();" ';
        echo 'alt="Add this picture to the email list">';

        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        echo '<a href="picture_maint.php'
            . '?in_pid='.$this_pid.'" target="_blank">';
        echo '<img src="/rings-images/icon-edit.png" border="0" ';
        echo 'onMouseOver="showEdit();" onMouseOut="hideEdit();" ';
        echo 'alt="Edit Picture Information">';
        echo "</a>\n";
    } else {
        echo '<a href="' . auth_url($_SERVER['PHP_SELF']);
        echo '?in_ring_pid='.$in_ring_pid.'">';
        echo '<img src="/rings-images/login.jpg" border="0">';
        echo "</a>\n";
    }

    echo '<p id="mailHelpDisplay">'."\n";
    echo "Select this picture to email\n";
    echo "<br>\n";
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

if (!empty($_SESSION['msg'])) {
  echo $_SESSION['msg'];
  $_SESSION['msg'] = '';
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
    echo '    url = "'.$_SERVER['PHP_SELF']
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
