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
$grade_sel = "(gr.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR gr.grade = '' ";
$grade_sel .= "OR gr.grade IS NULL) ";

// ---------------------------------------------
// make a link to another picture

function make_a_link ($thisUID,
                      $thisPID,
                      $this_picture_date,
                      $thisName) {
    global $CONF;
    global $DBH;
    global $grade_sel;
    
    $thisLink = '';
    if (auth_picture_invisible($thisPID)) {return $thisLink;}
    if (auth_person_hidden($thisUID))    {return $thisLink;}

    $next_uid  = $thisUID;
    $next_pid  = '';
    $next_date = '';

    if ($thisUID == 'next-by-date') {
        # Select next picture by date
        $sel = 'SELECT info.pid ';
        $sel .= 'FROM pictures_information info ';
        $sel .= 'LEFT OUTER JOIN picture_grades gr ';
        $sel .= 'ON (gr.pid = info.pid) ';
        $sel .= 'WHERE info.picture_date >= ? ';
        $sel .= 'AND info.pid > ? ';
        $sel .= 'AND ' . $grade_sel;
        $sel .= 'ORDER BY info.picture_date, info.pid ';
        $sel .= 'LIMIT 0,1 ';
        if (!$sth = $DBH->prepare($sel)) {
            sys_msg(
                'Prepare failed: ' . $DBH->error . '(' . $DBH->errno . ')'
            );
            sys_msg("Problem statement: $sel");
            return $thisLink;
        }
        $sth->bind_param('si', $this_picture_date, $thisPID);
        if (!$sth->execute()) {
            $m = 'Execute failed: ' . $DBH->error
                . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $cmd";
            sys_err($m);
            return $thisLink;
        }
        $sth->bind_result($p1);
        if ($sth->fetch()) {
            $next_pid = $p1;
        } else {
            $next_pid = 1;
        }
        $sth->close();
    } else {
        # Find the next picture for this uid.  If we don't find
        # a next entry return the first entry.
        $sel = 'SELECT det.pid, det.uid ';
        $sel .= 'FROM picture_details det ';
        $sel .= 'JOIN pictures_information info ';
        $sel .= 'ON (info.pid = det.pid) ';
        $sel .= 'LEFT OUTER JOIN picture_grades gr ';
        $sel .= 'ON (gr.pid = info.pid) ';
        $sel .= 'WHERE det.uid = ? ';
        $sel .= 'AND info.picture_date >= ? ';
        $sel .= 'AND det.pid > ? ';
        $sel .= 'ORDER BY info.picture_date, det.pid ';
        $sel .= 'LIMIT 0,1 ';
        if (!$sth = $DBH->prepare($sel)) {
            sys_msg(
                'Prepare failed: ' . $DBH->error . '(' . $DBH->errno . ')'
            );
            sys_msg("Problem statement: $sel");
            return $thisLink;
        }
        $sth->bind_param('ssi', $thisUID, $this_picture_date, $thisPID);
        if (!$sth->execute()) {
            $m = 'Execute failed: ' . $DBH->error
                . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $cmd";
            sys_err($m);
            return $thisLink;
        }
        $sth->bind_result($p1, $p2);
        if ($sth->fetch()) {
            $next_pid = $p1;
        }
        $sth->close();
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

# Get first picture for a uid if no pid is specified
if (empty($in_ring_pid) && !empty($in_ring_uid)) {
    $sel = 'SELECT det.pid ';
    $sel .= 'FROM picture_details det ';
    $sel .= 'JOIN pictures_information info ';
    $sel .= 'ON (info.pid = det.pid) ';
    $sel .= 'LEFT OUTER JOIN picture_grades gr ';
    $sel .= 'ON (gr.pid = info.pid) ';
    $sel .= 'WHERE det.uid = ? ';
    $sel .= 'ORDER BY info.picture_date, det.pid ';
    $sel .= 'LIMIT 0,1 ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err('Prepare failed: ' . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $sel");
    }
    $sth->bind_param('s', $in_ring_uid);
    if (!$sth->execute()) {
        sys_err('Execute failed: ' . $DBH->error . '(' . $DBH->errno . ') ');
        sys_err("Problem statement: $sel");
    }
    $sth->bind_result($p1);
    if ($sth->fetch()) {
        $in_ring_pid = $p1;
    } else {
        $in_ring_pid = 1;
        sys_msg('Problem getting picture for first ' . $in_ring_uid);
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
        sys_msg_err('ERROR: ' . $result->error);
        sys_msg_err("SQL: $sel");
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

sys_display_msg();

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
        . '&in_slide_show='.$in_slide_show
        . '";'."\n";
    echo '    location = url;'."\n";
    echo "}\n";

    $thisMilli = $display_seconds * 1000;
    echo 'setTimeout ("slideShowNext()",'.$thisMilli.");\n";
}
?>
</script>
