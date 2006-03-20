<?PHP
// -------------------------------------------------------------
// ring_select.php
// author: Bill MacAllister
// date: 26-Nov-2004
//

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
$dbh_read = new mysqli ( $mysql_slave_host, $mysql_user, $mysql_pass );
if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
  exit();
}
$dbh_read->select_db($mysql_db);

if (strlen($in_group_id)>0) {
  $_SESSION['group_id'] = $in_group_id;
} else {
  $in_group_id = $_SESSION['group_id'];
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
</script>

</head>

<body bgcolor="#eeeeff">

<h1>Pick a Picture Group</h1>

<blockquote>
<form name="pick_group" action="<?php echo $PHP_SELF;?>">

<select name="in_group_id"
        onChange="gotoGroup()">
 <option value="all-groups">Display Rings From All Groups

<?php

$sel = "SELECT * FROM groups ORDER BY group_name ";
if ($result = $dbh_read->query($sel)) {
  while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
    if (strlen($in_group_id) == 0) {
      $in_group_id = $row['group_id'];
    }
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
<input type="submit" name="btn_group" value="Display Group"<?php echo $s;?>>
</form>
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
  echo "<table border=\"0\" cellpadding=\"2\">\n";
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
  if ( $result = $dbh_read->query($sel) ) {
    if ($dbh_read->errno) {
	echo "Error accessing rings database (".$dbh_read->errno.")<br>\n";
	echo "Error message:".$dbh_read->error."<br>\n";
    }
    while ( $row = $result->fetch_array(MYSQLI_ASSOC) ) {
        $pp_list[$row["uid"]] = $row["display_name"];
        $pp_desc[$row["uid"]] = $row["description"];
        $pp_pid[$row["uid"]]  = $row["pid"];
    }
    asort($pp_list);
    foreach ($pp_list as $this_uid => $this_name) {
      $this_description = $pp_desc["$this_uid"];
      $this_pid         = $pp_pid["$this_uid"];
      echo "<tr bgcolor=\"#000066\">\n";
      echo "  <td colspan=\"3\"><img src=\"shim.gif\"></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
      echo "<td>\n";
      echo " <a href=\"picture_select.php?in_ring_uid=$this_uid\">";
      echo "<img src=\"button_small.php?in_button=First%20Picture\"></a>";
      echo "<br>\n";
      echo "<a href=\"ring_thumbnails.php?in_uid=$this_uid\">";
      echo " <img src=\"button_small.php?in_button=Thumbnails\"></a><br>\n";
      echo "</td>\n";
      echo "<td><b>$this_name</b> --- $this_description</td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
  echo "</blockquote>\n";
  echo "<p>\n";
  if (strlen($_SESSION['prideindustries_directory_user'])>0) {
    echo "<h5><a href=\"picture_sort\">Picture Sort</a><br>\n";
    echo "<a href=\"$PHP_SELF?in_logout=2\">Logout</a></h5>\n";
  } else {
    echo "<h5><a href=\"$PHP_SELF?in_login=2\">Login</a></h5>\n";
  }
}

$dbh_read->close();
?>

<p>
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
<a href="mailto:bill@macallister.grass-valley.ca.us">Billy MacAllister</a>, so 
send your flames to him.  If you want to update the web site yourself, 
either to add pictures, update descriptions or whatever contact Billy.
</dd>

</dl>
</blockquote>

</Body>
</html>

