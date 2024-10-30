<?php
// -------------------------------------------------------------
// picture_select.php
// author: Bill MacAllister
// date: August 15, 2004

// Get the screen properties before we do anything else
function make_id ($s) {
    return str_replace('.','',$s);
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
// Entries for the menu at the end of the page

function get_end_menu ($this_pid) {
    global $ring_admin;
    global $ring_user;
    
    $end_menu = array();

    # Allow caching of images for a day
    $i = date('Ymd');
    $end_menu[] = '<a href="display.php'
        . '?in_pid=' . $this_pid
        . '&in_size=raw'
        . '&rand=' . $i
        . '" target="_blank">'
        . 'Raw Image'
        . '</a>';

    $end_menu[] = '<a href="index.php">'
        . 'Home'
        . '</a>';

    if ($ring_user) {
        $email_link = '<a href="#emailTag" '
                    . 'name="emailTag" '
                    . "onClick=\"add_email_list($this_pid)()\">"
                    . 'Add to email';
        if (!empty($_SESSION['s_email_list'])) {
            $email_list = explode(" ", $_SESSION['s_email_list']);
            $email_cnt = count($email_list) - 1;
            $email_link .= " ($email_cnt)";
            $end_menu[] = $email_link;
        }
        if ($ring_admin) {
            $end_menu[] = '<a href="picture_maint.php'
                 . '?in_pid=' . $this_pid
                 . '" target="_blank">'
                 . 'Edit'
                 . '</a>';
        }
    } else {
        $end_menu[] = '<a href="' . auth_url($_SERVER['PHP_SELF'])
            . '?in_ring_pid=' . $this_pid . '">'
            . 'Login'
            . '</a>';
    }

    return $end_menu;
}

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
            if (isset($next_date)) {
                $this_picture_date = $next_date;
            }
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
    $sel .= 'FROM picture_rings det ';
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
    $sel .= 'FROM picture_rings det ';
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
        $next_pid = get_next_pic_by_uid($thisUID,
                                        $this_picture_date,
                                        $thisPID);
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

if ($ring_user) {
    $invisible_sel = '';
} else {
    $invisible_sel = "AND pp.visibility != 'INVISIBLE' ";
}

# Get first picture for a uid if no pid is specified
if (empty($in_ring_pid) && !empty($in_ring_uid)) {
    $sel = 'SELECT det.pid ';
    $sel .= 'FROM picture_rings det ';
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
    if (!$ring_user) {
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
            . '&dlm=' . str_replace(' ', ':', $this_dlm)
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
        $sel .= "FROM picture_rings det ";
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
    $next_menu[] = '<div id="linkDivHeader">Menu</div>' . "\n";
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

    $end_menu = get_end_menu($this_pid);
    foreach ($end_menu as $l) {
        $next_menu[] = '<p class="tight">' . $l . "</p>\n";
    }

    $next_menu[] = "</div>\n";

}

########################################################################
# Display output
########################################################################

?>
<html>
<head>
<title>Rings</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_pictures.php'); ?>
<style>
    body, html {
        height: 100%;
        width: 100%;
        margin: 0;
    }

    img.centermiddle {
        display: block;
        margin-left: auto;
        margin-right: auto
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

<img class="centermiddle" src="<?php echo $image_url; ?>" style="height:95%;">

<?php
// selection menu
foreach ($next_menu as $m) {
    echo $m;
}
?>

<?php sys_display_msg();?>

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
                          "Add this picture to the email2 list",
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

document.addEventListener('keydown', function(event) {
    if(event.keyCode == 13) {
        location = "<?php echo $this_url_next; ?>";
    }
});


</script>
