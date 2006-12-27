<?PHP
// -------------------------------------------------------------
// ring_select.php
// author: Bill MacAllister
// date: 26-Nov-2004
//

// Open a session
require('pi_php_sessions.inc');
require('pi_php_auth.inc');

if ($in_login == 2) {
    pi_auth('user|rings');
} elseif ($in_logout>0) {
    session_destroy();
    $_SESSION['prideindustries_directory_user'] = '';
}

require('inc_dbs.php');

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL host $mysql_host";
}
$result = mysql_select_db($mysql_db);
if (!$result) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL db $mysql_db";
}

// set the group
if (strlen($in_group_id)>0) {
    $_SESSION['group_id'] = $in_group_id;
} else {
    $in_group_id = $_SESSION['group_id'];
}

// set the display size
if (strlen($in_size)>0) {
    $_SESSION['display_size'] = $in_size;
} else {
    $in_size = $_SESSION['display_size'];
}
if (strlen($in_size)==0) {
    $in_size = 'large';
}
$chk_large = $chk_larger = $chk_raw = '';
if ($in_size == 'large') {
    $chk_large = 'CHECKED';
} elseif ($in_size == 'larger') {
    $chk_larger = 'CHECKED';
} elseif ($in_size == 'raw') {
    $chk_raw = 'CHECKED';
} else {
    $chk_larger = 'CHECKED';
    $in_size = 'larger';
}
$_SESSION['display_size'] = $in_size;

// set host delay
if (strlen($in_seconds) == 0) {
    $in_seconds = $_SESSION['display_seconds'];
}
if ($in_seconds < 3) { $in_seconds = 3;}
$_SESSION['display_seconds'] = $in_seconds;

// set button postion on picture display pages
if (strlen($in_pos) == 0) {$in_pos = $_SESSION['button_position'];}
$chk_pos_top = $chk_pos_bottom = '';
if ($in_pos == 'T') {
    $chk_pos_top = 'CHECKED';
} else {
    $chk_pos_bottom = 'CHECKED';
    $in_pos = 'B';
}
$_SESSION['button_position'] = $in_pos;

// set button postion on picture display pages
if (strlen($in_type) == 0) {$in_type = $_SESSION['button_type'];}
$chk_btext = $chk_bgraphic = '';
if ($in_type == 'T') {
    $chk_type_text = 'CHECKED';
} else {
    $chk_type_graphic = 'CHECKED';
    $in_type = 'G';
}
$_SESSION['button_type'] = $in_type;

// set preferences display
$chk_pref_yes = '';
$chk_pref_no = '';
if ($in_pref_display == 'Y') {
    $chk_pref_yes = 'CHECKED';
} else {
    $chk_pref_no = 'CHECKED';
}
?>
<html>
<head>
<title>Ring Select</title>
<?php include('ring_style.css');?>

<script language="JavaScript">
function gotoGroup() {
    var f;
    f = document.pick_group;
    var new_group_url = "<?php echo $PHP_SELF;?>?in_group_id="
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

<h1>Pick a Picture Group</h1>

<blockquote>
<form name="pick_group" action="<?php echo $PHP_SELF;?>">

<table border="0" cellpadding="2">

<tr>
<th align="right">Pick a Group:</th>
<td align="left">
<select name="in_group_id"
        onChange="gotoGroup()">
 <option value="all-groups">Display Rings From All Groups

<?php

if (strlen($in_group_id) == 0) {
    $in_group_id = 'happenings';
    $_SESSION['group_id'] = $in_group_id;
}

$sel = "SELECT * FROM groups ORDER BY group_name ";
if (  $result = mysql_query ($sel,$cnx) ) {
    while ( $row = mysql_fetch_array ($result) ) {
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
<td><input type="radio" name="in_pref_display"  <?php echo $chk_pref_yes;?> onClick="showPreferences()" value="Y">Yes
    &nbsp;&nbsp;
    <input type="radio" name="in_pref_display"  <?php echo $chk_pref_no;?> onClick="hidePreferences()" value="N">No
    <br>

    <p id="preferencesDisplay">

    <table border="1" cellpadding="2">

    <tr>
    <td rowspan="4">
    <input type="submit" name="btn_refresh" value="Set">
    </td>
    <th align="right">Message Size:</th>
    <td><input type="radio" <?php echo $chk_large;?> name="in_size"
                value="large">Large
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_larger;?> name="in_size" 
                value="larger">Larger
         &nbsp;&nbsp;
         <input type="radio" <?php echo $chk_raw;?> name="in_size" 
                value="raw">Gigantic
    </td>
    </tr>

    <tr>
    <th align="right">Seconds to Pause During Show:</th>
    <td>
       <input type="text" size="4" name="in_seconds" 
              value="<?php echo $in_seconds;?>">
    </td>
    </tr>

    <tr>
    <th align="right">Button Position:</th>
    <td>
       <input type="radio" <?php echo $chk_pos_top;?> name="in_pos"
              value="T">Top
       &nbsp;&nbsp;
       <input type="radio" <?php echo $chk_pos_bottom;?> name="in_pos" 
              value="B">Bottom
    </td>
    </tr>

    <tr>
    <th align="right">Button Type:</th>
    <td>
       <input type="radio" <?php echo $chk_type_text;?> name="in_type"
              value="T">Text
       &nbsp;&nbsp;
       <input type="radio" <?php echo $chk_type_graphic;?> name="in_type" 
              value="G">Graphic
    </td>
    </tr>

    </table>
    </p>

</td>
</tr>
</table>

</form>

<?php
if (strlen($_SESSION['prideindustries_directory_user'])>0) {
    echo "<h5><a href=\"index_maint\">Maintenance Menu</a><br>\n";
    echo "<a href=\"$PHP_SELF?in_logout=2\">Logout</a></h5>\n";
}
?>
</blockquote>

<?php

if ( strlen($in_group_id) > 0) {

    // ------------------------------------------
    // display ring choices
    
    if (strlen($this_group_name) > 0) {
        echo "<h1>Pick a Picture from the $this_group_name Ring</h1>\n"; 
    } else {
        echo "<h1>Pick a Picture Ring</h1>\n";
    }
    echo "<blockquote>\n";
    echo "<table border=\"0\" background=\"notebook.gif\" cellpadding=\"2\">\n";
    if ($in_group_id == "all-groups") {
        $sel = "SELECT det.uid   uid, ";
        $sel .= "min(det.pid)    pid, ";
        $sel .= "pp.description  description, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_details det ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid) ";
        $sel .= "GROUP BY det.uid ";
        $sel .= "ORDER BY det.uid ";
    } else {
        $sel = "SELECT det.uid   uid, ";
        $sel .= "min(det.pid)    pid, ";
        $sel .= "pp.description  description, ";
        $sel .= "pp.display_name display_name ";
        $sel .= "FROM picture_details det ";
        $sel .= "JOIN picture_groups g ";
        $sel .= "ON (g.uid = det.uid ";
        $sel .= "AND g.group_id='$in_group_id') ";
        $sel .= "JOIN people_or_places pp ";
        $sel .= "ON (det.uid = pp.uid) ";
        $sel .= "GROUP BY det.uid ";
        $sel .= "ORDER BY det.uid ";
    }
    $result = mysql_query ($sel,$cnx);
    $pp_list = array();
    while ( $row = mysql_fetch_array ($result) ) {
        $pp_list[$row["uid"]] = $row["display_name"];
        $pp_desc[$row["uid"]] = $row["description"];
        $pp_pid[$row["uid"]]  = $row["pid"];
    }
    asort($pp_list);
    foreach ($pp_list as $this_uid => $this_name) {
        $this_description = $pp_desc["$this_uid"];
        $this_pid         = $pp_pid["$this_uid"];

        echo "<tr>\n";
        echo " <td valign=\"top\">\n";
        echo "   <a href=\"picture_select.php?in_ring_uid=$this_uid\">";
        echo "[First]</a>";
        echo "&nbsp;&nbsp;";
        echo "<a href=\"picture_select.php"
            . "?in_ring_uid=$this_uid"
            . "&in_slide_show=3000\">";
        echo "[Show]</a>";
        echo "&nbsp;&nbsp;";
        echo "<a href=\"ring_thumbnails.php?in_uid=$this_uid\">";
        echo "[Index]</a>\n";
        echo " </td>\n";
        echo " <td>\n";
        echo " <b>$this_name</b> --- $this_description\n";
        echo " </td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</blockquote>\n";
    echo "<p>\n";
}

if (strlen($_SESSION['prideindustries_directory_user'])>0) {
    echo "<h5><a href=\"index_maint\">Maintenance Menu</a><br>\n";
    echo "<a href=\"$PHP_SELF?in_logout=2\">Logout</a></h5>\n";
} else {
    echo "<h5><a href=\"$PHP_SELF?in_login=2\">Login</a></h5>\n";
}
?>

<p>
<hr>
<h3>Random Notes</h3>
<p>
<blockquote>
<dl>

<dt>Where do those crazy dates come from?</dt>
<dd>Some dates are accurate, some are just a wild guess.  Pictures are ordered
by date and time, so the really important thing is that the dates be in the
correct sequence, not that any individual date be absolutely accurate.  It is
nice if they are close because correlations across rings will make sense, 
but that is not always possible.
</dd>

<dt>Who makes up the descriptions, dates, etc.?</dt>
<dd>At this point all updates are by 
<?php echo $ring_admin;?>.  If you want to update the web site yourself, 
either to add pictures, update descriptions or whatever contact 
<?php echo $ring_admin;?>.
</dd>

</dl>
</blockquote>

</Body>
</html>

<script language="JavaScript">
<?php if (strlen($chk_pref_yes) < 1) {
    echo "getDom(\"preferencesDisplay\").style.display = 'none';\n";
}
?>
</script>
