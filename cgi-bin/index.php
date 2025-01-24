<?PHP
// -------------------------------------------------------------
// index.php for the Picture Ring application
// author: Bill MacAllister
// date: 26-Nov-2004
//

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_button_pos   = get_request('in_button_pos');
$in_grade        = get_request('in_grade');
$in_group_id     = get_request('in_group_id');
$in_seconds      = get_request('in_seconds');
$in_size         = get_request('in_size');
$in_type         = get_request('in_type');

$in_pref_display = get_request('in_pref_display');
$in_button_set   = get_request('in_button_set');

// Cookie to Session map
$cookie_id = $CONF['cookie_id'];
$cm['GID'] = 'group_id';
$cm['SZ']  = 'display_size';
$cm['GRD'] = 'display_grade';
$cm['SEC'] = 'display_seconds';
$cm['BP']  = 'button_position';

// Set sessions variables from cookie if session variable is
// empty and there is a cookie value.
$cookie_val = array();
if (isset($_COOKIE[$cookie_id])) {
    $av_list = preg_split("/\|/", $_COOKIE[$cookie_id]);
    foreach ($av_list as $av) {
        $i = strpos($av, "=");
	if ($i) {
	    $cid = substr($av, 0, $i);
	    $val = substr($av, $i);
	    $cookie_val[$cid] = $val;
        }
    }
}
foreach ($cm as $cid => $sid) {
    if (isset(($_SESSION[$sid]))
      && empty($_SESSION[$sid])
      && isset($cookie_val[$cid])
    ) {
        $_SESSION[$sid] = $cookie_val[$cid];
    }
}

// set the group
if (isset($in_group_id)) {
    $_SESSION['group_id'] = $in_group_id;
} else {
    // If there is no group_id in the session space then see if there is a
    // cookie and use that to set session values.
    if (array_key_exists('group_id', $_SESSION)) {
        $in_group_id = $_SESSION['group_id'];
    } elseif (isset($_COOKIE[$cookie_id])) {
        $s = $_COOKIE[$cookie_id].'|';
        foreach ($cm as $cid => $sid) {
            $vals = array();
            if (preg_match("/\|$cid=(.+?)\|/", $cid, $vals)) {
                if (isset($vals[1])) {
                    $_SESSION[$sid] = $vals[1];
                }
            }
        }
        if (array_key_exists('group_id', $_SESSION)) {
            $in_group_id = $_SESSION['group_id'];
        }
    }
}

// set the display size
if (empty($in_size)) {
    if (empty($_SESSION['display_size'])) {
        $in_size = $CONF['maint_size'];
    } else {
        $in_size = $_SESSION['display_size'];
    }
}
$_SESSION['display_size'] = $in_size;

// set display grade
if (isset($in_grade)) {
    $_SESSION['display_grade'] = $in_grade;
} else {
    $in_grade = $_SESSION['display_grade'];
}
if (empty($in_grade)) {
    $in_grade = 'A';
}
$chk_a = $chk_b = $chk_c = $chk_d = '';
if ($in_grade == 'D') {
    $chk_d = 'CHECKED';
} elseif ($in_grade == 'C') {
    $chk_c = 'CHECKED';
} elseif ($in_grade == 'B') {
    $chk_b = 'CHECKED';
} else {
    $chk_a = 'CHECKED';
    $in_grade = 'A';
}
$_SESSION['display_grade'] = $in_grade;

$chk_float = $chk_right = $chk_left = $chk_top = $chk_bottom = '';
if ($in_button_pos == 'top') {
    $chk_bottom = 'CHECKED';
} elseif ($in_button_pos == 'bottom') {
    $chk_bottom = 'CHECKED';
} elseif ($in_button_pos == 'left') {
    $chk_left = 'CHECKED';
} elseif ($in_button_pos == 'right') {
    $chk_right = 'CHECKED';
} else {
    $chk_float = 'CHECKED';
    $in_button_pos = 'float';
}
$_SESSION['button_position'] = $in_button_pos;

// set show delay
if (!isset($in_seconds) && array_key_exists('display_seconds', $_SESSION)) {
    $in_seconds = $_SESSION['display_seconds'];
}
if ($in_seconds < 3) { $in_seconds = 3;}
$_SESSION['display_seconds'] = $in_seconds;

// set preferences display
$chk_pref_yes = '';
$chk_pref_no = '';
if ($in_pref_display == 'Y') {
    $chk_pref_yes = 'CHECKED';
} else {
    $chk_pref_no = 'CHECKED';
}

// Set a 10 year cookie
$cookie_value = '';
$cookie_life = time()+315360000;
foreach ($cm as $cid => $sid) {
     $cookie_value .= "|$cid=".$_SESSION[$sid];
}
setcookie($cookie_id, $cookie_value, $cookie_life);

?>
<html>
<head>
<title>Ring Select</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>

<script language="JavaScript">
function gotoGroup() {
    var f;
    f = document.pick_group;
    var new_group_url = "<?php echo $_SERVER['PHP_SELF'];?>?in_group_id="
                      + f.in_group_id.value;
    location = new_group_url;
}

function getDom(objectname){
  if (document.all) return document.all[objectname];
  else return document.getElementById(objectname);
}

function hidePreferences(){
  getDom("preferencesDisplay").style.display = 'none';
}
function showPreferences(){
  getDom("preferencesDisplay").style.display = '';
}

</script>

</head>

<body bgcolor="#eeeeff">

<h1><?php echo $CONF['ring_name']; ?>'s Photographs </h1>
<h2>Pick a Picture Group</h2>

<blockquote>
<form name="pick_group" action="<?php echo $_SERVER['PHP_SELF'];?>">

<table border="0" cellpadding="2">

<tr>
<th align="right">Pick a Group:</th>
<td align="left">
<select name="in_group_id"
        onChange="gotoGroup()">
 <option value="all-groups">Display Rings From All Groups

<?php

if (!isset($in_group_id)) {
    $in_group_id = 'all-groups';
    $_SESSION['group_id'] = $in_group_id;
}

$sel = "SELECT * FROM groups ORDER BY group_name ";
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
if ($result = $DBH->query($sel)) {
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $s = '';
        if ($_SESSION['group_id'] == $row['group_id']) {
            $s = ' SELECTED';
            $this_group_name = $row['group_name'];
        }
        echo ' <option value="'.$row['group_id'].'"' . $s . '>'
            .$row['group_name']."\n";
    }
}
?>
</select>
</td>
</tr>

<tr>
<th align="right" valign="top">Show Preferences:</th>
<td><input type="radio" name="in_pref_display"  <?php echo $chk_pref_yes;?>
           onClick="showPreferences()" value="Y">Yes
    &nbsp;&nbsp;
    <input type="radio" name="in_pref_display"  <?php echo $chk_pref_no;?>
           onClick="hidePreferences()" value="N">No
    <br>

    <p id="preferencesDisplay">

    <table border="1" cellpadding="2">

    <tr>
    <td rowspan="5">
    <input type="submit" name="in_button_set" value="Set">
    </td>
    </tr>
    
    <th align="right">Picture Size:</th>
    <td>
<?php
$sel = 'SELECT size_id, description ';
$sel .= 'FROM picture_sizes ';
$sel .= 'ORDER BY description ';
if (!$stmt = $DBH->prepare($sel)) {
    sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
}
$stmt->execute();
$stmt->bind_result($p1, $p2);
$sp = '';
while ($stmt->fetch()) {
    $this_size        = $p1;
    $this_description = $p2;
    $input = $sp . '<input type="radio" ';
    if ($in_size == $this_size) {
        $input .= 'CHECKED ';
    }
    $input .= 'name="in_size" ';
    $input .= 'value="' . $this_size . '">';
    $input .= $this_description;
    echo $input . "\n";
    $sp = '&nbsp;&nbsp';
}
$stmt->close();
?>
    </td>
    </tr>

    <tr>
    <th align="right">Picture Grade to Display:</th>
    <td><input type="radio" <?php echo $chk_a;?> name="in_grade"
                value="A">Only A's
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_b;?> name="in_grade"
                value="B">A's and B's
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_c;?> name="in_grade"
                value="C">A's, B's, and C's
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_d;?> name="in_grade"
                value="D">All
    </td>
    </tr>

    <tr>
    <th align="right">Button:</th>
    <td><input type="radio" <?php echo $chk_float;?> name="in_button_pos"
                value="float">Float
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_left;?> name="in_button_pos"
                value="left">Left
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_top;?> name="in_button_pos"
                value="right">Right
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_top;?> name="in_button_pos"
                value="top">Top
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_bottom;?> name="in_button_pos"
                value="bottom">Bottom
    </td>
    </tr>

    <tr>
    <th align="right">Seconds to Pause During Show:</th>
    <td>
       <input type="text" size="4" name="in_seconds"
              value="<?php echo $in_seconds;?>">
    </td>
    </tr>

    </table>
    </p>

</td>
</tr>
</table>

</form>

<?php
if ($ring_user) {
    echo "<h5><a href=\"index_maint.php\">Maintenance Menu</a><br>\n";
    if (!empty($_SESSION['s_email_list'])) {
        echo "<a href=\"picture_email.php\">Email Selected Pictures</a><br>\n";
    }
    echo '<a href="' . auth_url('logout')
        . '">Logout ' . $ring_user_name . "</a><br/>\n";
    echo "</h5>\n";
} else {
    echo '<h5><a href="' . auth_url('login') . '"' . '>Login</a>' . "\n";
    echo '&nbsp;-&nbsp;To see all of the pictures you need to login.' . "\n";
    echo '&nbsp;-&nbsp;<a href="access_email.php">Access Request Form.</a>'
        . "\n";
    echo "</h5>\n";
}
?>
</blockquote>

<?php

if (isset($in_group_id)) {

    // ------------------------------------------
    // display ring choices

    if (!empty($this_group_name)) {
        echo "<h2>Pick a Ring from the $this_group_name Group</h2>\n";
    } else {
        echo "<h2>Pick a Picture Ring</h2>\n";
    }
    // Hide the private folks
    if ($USER_ATTR['logged-in']) {
        $vis_sel = '';
    } else {
        $vis_sel = "AND visibility != 'HIDDEN' ";
        $vis_sel .= "AND visibility != 'INVISIBLE' ";
    }
    
    if ($in_group_id == "all-groups") {
        $sel = "SELECT det.uid   uid, ";
        $sel .= "min(det.pid)    pid, ";
        $sel .= "pp.description  description, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_rings det ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid ";
        $sel .= "$vis_sel) ";
        $sel .= "GROUP BY det.uid ";
        $sel .= "ORDER BY det.uid ";
    } else {
        $sel = "SELECT det.uid   uid, ";
        $sel .= "min(det.pid)    pid, ";
        $sel .= "pp.description  description, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_rings det ";
        $sel .= "JOIN picture_groups g ";
        $sel .= "ON (g.uid = det.uid ";
        $sel .= "AND g.group_id='$in_group_id') ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid ";
        $sel .= "$vis_sel) ";
        $sel .= "GROUP BY det.uid ";
        $sel .= "ORDER BY det.uid ";
    }
    if ($CONF['debug']) {
        syslog(LOG_DEBUG, $sel);
    }
    $result = $DBH->query($sel);
    $pp_list = array();
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $pp_list[$row["uid"]] = $row["display_name"];
        $pp_desc[$row["uid"]] = $row["description"];
        $pp_pid[$row["uid"]]  = $row["pid"];
    }
    asort($pp_list);
    foreach ($pp_list as $this_uid => $this_name) {
        $this_desc = $pp_desc["$this_uid"];
        $this_pid  = $pp_pid["$this_uid"];
        if (!$ring_user && auth_person_hidden($this_uid) > 0) {
            continue;
        }

        echo ' <p class="hang-big">'."\n";

        echo '  <a href="picture_select.php?in_ring_uid='.$this_uid.'">'."\n";
        echo '    <img src="/rings-images/icon-first.png" border="0" '
            . 'width="32" height="32" '
            . 'alt="First Picture of '.$this_name.'">'."\n";
        echo '  </a>'."\n";

        echo '  <a href="picture_select.php?in_ring_uid='.$this_uid
            . '&in_slide_show=3000">'."\n";
        echo '    <img src="/rings-images/icon-start.png" border="0" '
            . 'width="32" height="32" '
            . 'alt="Start slide show of '.$this_name.' pictures">'."\n";
        echo '  </a>'."\n";

        echo '  <a href="ring_thumbnails.php?in_uid='.$this_uid.'">'."\n";
        echo '    <img src="/rings-images/icon-index.png" border="0" '
            . 'width="32" height="32" '
            . 'alt="Index of all pictures of '.$this_name.'">'."\n";
        echo '  </a>'."\n";

        if ($ring_user) {
            echo '  <a href="ring_slide_table.php?in_uid='.$this_uid.'">'."\n";
            echo '    <img src="/rings-images/icon-sort.png" border="0" '
                . 'width="32" height="32" '
                . 'alt="Index of all pictures of '.$this_name.'">'."\n";
            echo '  </a>'."\n";
        }
        
        echo '  <a href="picture_select.php?in_ring_uid='.$this_uid.'">'."\n";
        echo '   <strong>'.$this_name.'</strong></a> &mdash; '.$this_desc."\n";
        echo " </p>\n";
    }
}

?>

<p>
<hr>
<h2>Random Notes</h2>

<h3>Some pictures are missing.  What happened?</h3>

<p>
Initially anyone could see all of the pictures stored in the rings.
This bothered some people.  And for a short time the site was
opened to Google indexing this made people even more uncomfortable.
To address these concerns the site policies have changed.  Any person
identified in the rings can choose any one of three policies applied
to pictures they are identified in:
</p>

<ul>
<li>Invisible - any picture with the person in it is completely
suppressed.
<li>Hidden - pictures are display but no names or links are created
for the person.
<li>Visible - names and pictures are shown to anyone that cares to
look.
</ul>

<p>
These policies apply only to anyone that has not logged into the server.
If you would like to see all of the pictures you need to login, and
to login you need credentials, and to get credentials use the
<a href="access_email.php">Access Request Form.</a>
</p>

<p>
Additionally, anyone that wants their pictures to be visible to everyone,
but does not like the fact that Google, et. al. will index their
name, can request that only a nickname be displayed.  If this is the
case just let us know and it will be so.
</p>

<h3>Where do those crazy dates come from?</h3>

<p>Some dates are accurate, some are just a wild guess.  Pictures are ordered
by date and time, so the really important thing is that the dates be in the
correct sequence, not that any individual date be absolutely accurate.  It is
nice if they are close because correlations across rings will make sense,
but that is not always possible.
</p>

<h3>Who makes up the descriptions, dates, etc.?</h3>

<p>At this point all updates are by Ring Administrators.  If you want
to update the web site yourself, either to add pictures, update
descriptions or whatever contact <?php echo $CONF['ring_admin'];?>.
</p>

<!-- Message area -->
<?php sys_display_msg(); ?>
</Body>
</html>

<script language="JavaScript">
<?php
if (empty($chk_pref_yes)) {
    echo "getDom(\"preferencesDisplay\").style.display = 'none';\n";
}
?>
</script>
