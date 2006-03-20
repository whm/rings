<?PHP
// -------------------------------------------------------------
// ring_thumbnails.php
// author: Bill MacAllister
// date: 26-Nov-2004
//

// Open a session
require('pi_php_auth.inc');
require('pi_php_sessions.inc');

// -- Print a space or the field
function prt ($fld) {
  $str = trim ($fld);
  if (strlen($str) == 0) {
    $str = "&nbsp;";
  } 
  return $str;
}

// database pointers
require ('mysql.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
if (!mysql_select_db($mysql_db, $db_link)) {
  echo "<font color=\#ff0000\">";
  echo "Error selecting database $mysql_db";
  echo "</font><br>\n";
}

$pics_per_page = 100;

?>

<html>
<head>
<title>Picture Thumbnails</title>
</head>

<body bgcolor="#eeeeff">

<?php 

$sel = "SELECT p.date_taken, d.pid ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
$sel .= "ORDER BY p.date_taken ";
$result = mysql_query ($sel);
if (!$result) {
  echo "Person '$in_uid' not found.<br>\n";
} else {
  $cnt = 20;
  $hr = '';
  echo "<table border=\"1\"><tr><td>\n";
  while ($row = mysql_fetch_array($result)) {
    if ($cnt>6) {
      echo "<br>\n";
      echo $hr;
      echo $row['date_taken']."\n";
      echo "<br>\n";
      $cnt = 0;
      $hr = "<hr>\n";
    }
    $pid = $row["pid"];
    $pic_href = "<a href=\"picture_select?in_ring_pid=$pid\" target=\"_blank\">";
    $thumb = "<img src=\"display.php?in_pid=$pid&in_size=small\" border=\"0\">";
    echo $pic_href . $thumb . "</a>\n";
    $cnt++;
  }
  echo "</td></tr></table>\n";
}
?>
<br>
<a href="/rings/index.php"><img 
       src="rings.png" 
       alt="Pick a New Ring"
       border="0"></a>
</body>
</html>
