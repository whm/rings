<?php
// -------------------------------------------------------------
// picture_select.php
// author: Bill MacAllister
// date: August 15, 2004

// Get the screen properties before we do anything else
function make_id ($s) {
    return str_replace('.','',$s);
}
$clientProps = array('screen.width',
                     'screen.height',
                     'window.innerWidth',
                     'window.innerHeight',
                     'window.outerWidth',
                     'window.outerHeight',
                     'screen.colorDepth',
                     'screen.pixelDepth');
if(empty($_POST['screenheight'])) {
    echo "<!DOCTYPE html>\n";
    echo "<html>\n";
    echo "<head>\n";
    echo "  <style>\n";
    echo "    body {\n";
    echo "      background-color: black;\n";
    echo "    }\n";
    echo "  </style>\n";
    echo "  <title>Get Window Size</title>\n";
    echo "</head>\n";
    echo "\n";
    echo "<body>\n";
    echo "Loading...";
    // create hidden form
    echo "<form method='POST' id='data' style='display:none'>\n";
    foreach($clientProps as $p) {
        $id = make_id($p);
        echo "<input type='text' id='$id' name='$id'>\n";
    }
    echo "<input type='submit'>\n";
    echo "</form>\n";

    echo "<script>\n";
    foreach($clientProps as $p) {
        //populate hidden form with screen/window info
        $id = make_id($p);
        echo "document.getElementById('$id').value = $p;\n";
    }
    //submit form
    echo "document.forms.namedItem('data').submit();\n";
    echo "</script>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

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

# Variable to how the next url for slide shows
$this_url_next = '';

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
    $sel .= 'AND ' . $grade_sel;
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
        sys_err('Execute failed: ' . $DBH->error . '(' . $DBH->errno . ') ');
        sys_err("Problem statement: $cmd");
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
    $sel .= 'AND ' . $grade_sel;
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

// ---------------------------------------------
// make a url to go to another picture

function make_a_url ($thisUID,
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

    if (empty($next_pid) || $next_pid == 0) {
        $next_url = 1;
    }

    $this_url = 'picture_select.php';
    $sep = '?';

    $this_url .= $sep . 'in_ring_pid=' . $next_pid;
    $sep = '&';
    $this_url .= $sep . 'in_ring_uid=' . urlencode($next_uid);
    $sep = '&';

    return $this_url;

}

########################################################################
# Main routine
########################################################################

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
    $sel .= 'JOIN pictures_information p ';
    $sel .= 'ON (p.pid = det.pid) ';
    $sel .= 'WHERE det.uid = ? ';
    $sel .= 'AND ' . $grade_sel;
    $sel .= 'ORDER BY p.picture_date, det.pid ';
    $sel .= 'LIMIT 0,1 ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err('Prepare failed (get first picture): ' . $DBH->error
                . '(' . $DBH->errno . ')');
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

    $this_size
        = empty($_SESSION['display_size']) ? '' : $_SESSION['display_size'];
    if (empty($this_size)) {
        $this_size = $CONF['display_size'];
    }

    // Get data

    $image_reference = '';
    $image_url       = '';
    $sel = "SELECT * ";
    $sel .= "FROM pictures_information p ";
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
        $image_url
            = 'display.php?in_pid=' . $this_pid
            . '&dlm=' . htmlentities($this_dlm)
            . '&in_size=' . $this_size;
        $image_reference = '<img src="' . $image_url . '>';
        if (!empty($row['description'])) {
            $image_reference .= "<p>\n";
            $image_reference .= $row['description']."\n";
        }
        $image_reference .= "<p>\n";
        $image_reference .= "Picture Date: "
            . format_date_time($this_picture_date) . "\n";
        $image_reference .= "<p>\n";
        $image_reference = '<img src="' . $image_url . '">';
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
    // gather the tags in this picture into links to the next picture

    $next_menu = array();
    $next_menu[] = '<div id="linkDiv">' . "\n";
    $next_menu[] = '<div id="linkDivHeader">Moveable Menu</div>' . "\n";
    if (count($next_links)>0) {
        asort($next_links);

        if (!empty($in_ring_uid)) {
            // display the reason we got here first so that it is easy
            // to step through these pictures.
            $l = make_a_link($in_ring_uid,
                             $this_pid,
                             $this_picture_date,
                             $next_links[$in_ring_uid]);
            $this_url_next = make_a_url($in_ring_uid,
                             $this_pid,
                             $this_picture_date,
                             $next_links[$in_ring_uid]);

            if (!empty($l)) {
                $next_menu[] = '<p class="tight">' . $l ."</p>\n";
            }
        }
        if ($in_slide_show > 0) {
            $l = "<a href=\"?in_slide_show=0&in_ring_pid=$this_pid\">";
            $l .= '<img src="button.php?in_button=Stop Show">';
            $l .= "</a>\n";
            $next_menu[] = '<p class="tight">' . $l . "</p>\n";
        } else {
            foreach ($next_links as $thisUID => $thisName) {
                if ($in_ring_uid == $thisUID) {continue;}
                $l = make_a_link($thisUID,
                                 $this_pid,
                                 $this_picture_date,
                                 $next_links[$thisUID]);
                if (!empty($l)) {
                    $next_menu[] = '<p class="tight">' . $l . "</p>\n";
                }
            }
        }
    }
    $next_menu[] = "</div>\n";

    # --------------------------------
    # The end menu

    $end_menu = array();

    # Defeat the local picture cache by adding a random number to
    # the image tag.
    $i = rand(0, 10000);
    $end_menu[] = '<ul>' . "\n";
    $end_menu[] = '<li>' . "\n";
    $end_menu[] = '<a href="display.php'
        . '?in_pid=' . $this_pid
        . '&in_size=raw'
        . '&rand=' . $i
        . '" target="_blank">' . "\n";
    $end_menu[] = '<img src="/rings-images/icon-view-details.png"' . "\n";
    $end_menu[] = '     border="0"' . "\n";
    $end_menu[] = '     alt="Full size image in a new window.">' . "\n";
    $end_menu[] = "</a>\n";
    $end_menu[] = "</li>\n";

    $end_menu[] = '<li>' . "\n";
    $end_menu[] = '<a href="index.php">' . "\n";
    $end_menu[] = '<img src="/rings-images/icon-home.png"' . "\n";
    $end_menu[] = '     border="0"' . "\n";
    $end_menu[] = '     alt="Pick a new Picture Ring">' . "\n";
    $end_menu[] = "</a>\n";
    $end_menu[] = "</li>\n";

    if (!empty($_SERVER['REMOTE_USER'])) {
        $loggedInUser = $_SERVER['REMOTE_USER'];
    }
    if (!empty($loggedInUser)) {
        $end_menu[] = '<li>' . "\n";
        $end_menu[] = "<!-- Logged in user -->\n";
        $end_menu[] = '<img src="/rings-images/icon-mail-send.png"' . "\n";
        $end_menu[] = '     border="0"' . "\n";
        $end_menu[] = "     onClick=\"add_email_list($this_pid);\"\n";
        $end_menu[] = '     alt="Add picture to the email list">' . "\n";
        $end_menu[] = "</li>\n";

        if ($ring_admin_group) {
            $end_menu[] = '<li>' . "\n";
            $end_menu[] = "<!-- Admin User -->\n";
            $end_menu[] = '<a href="picture_maint.php'
                . '?in_pid=' . $this_pid . '" target="_blank">' . "\n";
            $end_menu[] = '<img src="/rings-images/icon-edit.png"' . "\n";
            $end_menu[] = '     border="0"' . "\n";
            $end_menu[] = '     alt="Edit Picture Information">' . "\n";
            $end_menu[] = "</a>\n";
            $end_menu[] = "</li>\n";
        }
    } else {
        $end_menu[] = '<li>' . "\n";
        $end_menu[] = '<a href="' . auth_url($_SERVER['PHP_SELF']);
        $end_menu[] = '?in_ring_pid='.$in_ring_pid.'">' . "\n";
        $end_menu[] = '<img src="/rings-images/login.jpg" border="0">' . "\n";
        $end_menu[] = "</a>\n";
        $end_menu[] = "</li>\n";
    }
    $end_menu[] = "</ul>\n";

}

// --------------------------------
// Retrieve the screen properties
$debug = 0;
$props = array();
foreach ($clientProps as $p) {
    $id  = make_id($p);
    $val = $_POST[$id];
    $props[$id] = $val;
    if ($debug) {
        echo "p:$p id:$id prop_val:$val<br/>\n";
    }
}

$max_x               = $props['windowinnerWidth'];
$max_y               = $props['windowinnerHeight'];
list($pic_x, $pic_y) = lookup_pic_dimen($this_pid, $this_size);
list($x, $y)         = calc_size($max_x, $max_y, $pic_x, $pic_y);

########################################################################
# Display output
########################################################################

?>
<html>
<head>
<title>Rings</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/pictures.css" rel="stylesheet" type="text/css">
<style>
    body, html {
        height: 100%;
        width: 100%;
        margin: 0;
    }

    .bg {
        background-image: url("<?php echo $image_url; ?>");
        height: 100%;
        background-position: center;
        background-repeat: no-repeat;
        background-size: <?php echo "${x}px ${y}px"; ?>;
    }
    ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }

    li {
        display: inline;
    }

</style>

</head>

<body>
<div class="bg"></div>

<?php
// selection menu
foreach ($next_menu as $m) {
    echo $m;
}
?>

<?php sys_display_msg();?>

<div align="center">
<?php foreach ($end_menu as $l) { echo $l; } ?>
</div>

</Body>
</html>

<script language="JavaScript">

// Make the DIV element draggable:
function dragElement(elmnt) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    if (document.getElementById(elmnt.id + "Header")) {
        // if present, the header is where you move the DIV from:
        document.getElementById(elmnt.id + "Header").onmousedown
                = dragMouseDown;
    } else {
        // otherwise, move the DIV from anywhere inside the DIV:
        elmnt.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e = e || window.event;
        e.preventDefault();
        // get the mouse cursor position at startup:
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        // call a function whenever the cursor moves:
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        // calculate the new cursor position:
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        // set the element's new position:
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        // stop moving when mouse button is released:
        document.onmouseup = null;
        document.onmousemove = null;
    }
}
dragElement(document.getElementById("linkDiv"));

// The mail selection action

function getDom(objectname){
    if (document.all) return document.all[objectname];
    else return document.getElementById(objectname);
}

function add_email_list(idx) {
    var win = window.open("add_email_list.php?in_id="+idx,
                          "Add this picture to the email list",
                          "width=400,height=150,status=no");
    return false;
}

<?php
if ($in_slide_show > 0) {

    $display_seconds = $_SESSION['display_seconds'];
    if ($display_seconds<3) {
        $display_seconds = 3;
    }

    $show_url = $this_url_next . '&in_slide_show=' . $in_slide_show;

    echo "function slideShowNext(aUID, aDate, aMilliSec) {\n";
    echo '    var url;' . "\n";
    echo '    url = "' . $show_url . '";' ."\n";
    echo '    location = url;' . "\n";
    echo "}\n";

    $thisMilli = $display_seconds * 1000;
    echo 'setTimeout ("slideShowNext()",' . $thisMilli . ");\n";
}
?>

</script>
