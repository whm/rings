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
require ('inc_dbs.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
if (!mysql_select_db($mysql_db, $db_link)) {
    echo "<font color=\#ff0000\">";
    echo "Error selecting database $mysql_db";
    echo "</font><br>\n";
}

if (strlen($in_start) == 0) {$in_start = 0;}

if ($in_number == 0) {
    if ($_SESSION['s_thumbs_per_page'] > 0) {
        $in_number = $_SESSION['s_thumbs_per_page'];
    } else {
        $in_number = 10 * 7;}
}
$_SESSION['s_thumbs_per_page'] = $in_number;


if (strlen($in_uid) == 0) {
    $in_uid = $_SESSION['s_uid'];
} else {
    $_SESSION['s_uid'] = $in_uid;
}

$thisPerson = "$in_uid";
$sel = "SELECT display_name ";
$sel .= "FROM people_or_places ";
$sel .= "WHERE uid='$in_uid' ";
$result = mysql_query ($sel);
if ($result) {
    if ($row = mysql_fetch_array($result)) {
        $thisPerson = $row['display_name'];
    }
}

$sel = "SELECT count(*) cnt ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
$thisCount = 0;
$result = mysql_query ($sel);
if ($result) {
    if ($row = mysql_fetch_array($result)) {
        $thisCount = $row['cnt'];
    }
}
if (strlen($in_start_date) > 0) {
    $sel = "SELECT count(*) cnt ";
    $sel .= "FROM picture_details d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "WHERE d.uid='$in_uid' ";
    $sel .= "AND p.date_taken>'$in_start_date' ";
    if (strlen($_SESSION['prideindustries_directory_user']) == 0) {
        $sel .= "AND p.public='Y' ";
    }
    $partCount = 0;
    $result = mysql_query ($sel);
    if ($result) {
        if ($row = mysql_fetch_array($result)) {
            $partCount = $row['cnt'];
        }
    }
    if ($partCount > 0) {
        $in_start = $thisCount - $partCount;
        if ($in_start < 0) {$in_start=0;}
    }
}

?>

<html>
<head>
<title>Picture Thumbnails</title>
</head>

<body bgcolor="#eeeeff">

<h2><?php echo $thisPerson;?><h2>
<form method="post" action="<?php echo $PHPSELF;?>">

<table border="0">
<tr><td align="right">Starting Date:</td>
    <td><input type="text" 
               name="in_start_date">
    </td>
    <td><input type="submit" name="btn_refresh" value="Refresh"></td>
</tr>
<tr><td align="right">Number of Pictures to Display:</td>
    <td><input type="text" 
               name="in_number" 
               size="10" 
               value="<?php echo $in_number;?>">
    </td>
    <td></td>
</tr>
</table>
</form>

<?php 

$sel = "SELECT p.date_taken, d.pid ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
if (strlen($_SESSION['prideindustries_directory_user']) == 0) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "ORDER BY p.date_taken ";
$sel .= "LIMIT $in_start, $in_number ";
$result = mysql_query ($sel);
if (!$result) {
  echo "Person '$in_uid' not found.<br>\n";
} else {

  echo "<table border=\"1\"><tr><td>\n";

  echo "<tr><td>";
  echo " <table broder=\"0\" width=\"100%\">\n";
  echo " <tr>\n";
  echo "  <td>\n";
  if ($in_start > 0) {
      $in_prev = $in_start - $in_number;
      if ($in_prev < 0) {$in_prev = 0;}
      if ($in_prev > 0) {
          echo "<a href=\"$PHPSELF?in_start=0\">First</a>";
          echo " - ";
      }
      echo "<a href=\"$PHPSELF?in_start=$in_prev\">Previous</a>";
  } else {
      echo "&nbsp;";
  }
  echo "  </td>\n"; 
  echo "  <td align=\"right\">\n"; 
  $in_next = $in_start + $in_number;
  if ($in_next < $thisCount) {
      if ($in_next+$in_number > $thisCount) {
          $in_next = $thisCount - $in_number;
      }
      echo "<a href=\"$PHPSELF?in_start=$in_next\">Next</a>";
      if ($in_next+$in_number < $thisCount) {
          $in_last = $thisCount - $in_number;
          echo " - ";
          echo "<a href=\"$PHPSELF?in_start=$in_last\">Last</a>";
      }
  } else {
      echo "&nbsp;";
  }
  echo "  </td>\n"; 
  echo " </tr>\n"; 
  echo " </table>\n"; 
  echo "</td></tr>\n";

  echo "<tr><td>";
  $cnt = 20;
  $hr = '';
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
