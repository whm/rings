<?PHP
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_number  = $_REQUEST['in_number'];
$in_start_date  = $_REQUEST['in_start_date'];
$in_last  = $_REQUEST['in_last'];
$in_start  = $_REQUEST['in_start'];
$in_next  = $_REQUEST['in_next'];
$in_prev  = $_REQUEST['in_prev'];
$in_uid  = $_REQUEST['in_uid'];
// ----------------------------------------------------------
//
// -------------------------------------------------------------
// ring_thumbnails.php
// author: Bill MacAllister
// date: 26-Nov-2004
//

// Init session, connect to database
$authNotRequired = 1;
require('inc_ring_init.php');

// -- Print a space or the field
function prt ($fld) {
    $str = trim ($fld);
    if (strlen($str) == 0) {
        $str = "&nbsp;";
    } 
    return $str;
}

// database pointers
require ('/etc/whm/rings_dbs.php');

// ----------------------------------------------------------
// Function to exit without displaying anything and return to 
// the main index page.

function back_to_index () {

    echo "<html>\n";
    echo "<head>\n";
    echo "<meta http-equiv=\"refresh\" ";
    echo '    content="0; URL=http://'.$_SERVER['SERVER_NAME'].'/rings">'."\n";
    echo "<title>Rings of Pictures</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo '<a href="rings">Rings of Pictures</a>'."\n";
    echo "</body>\n";
    echo "</html>\n";
    $_SESSION['s_msg'] .= "Ring Not Found.\n";

    exit;
}

// ============
// Main routine 

if (strlen($_SESSION['display_grade']) == 0) {
    $_SESSION['display_grade'] = 'A';
}
$grade_sel = "(p.grade <= '".$_SESSION['display_grade']."' ";
$grade_sel .= "OR p.grade = '' ";
$grade_sel .= "OR p.grade IS NULL) ";

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

if (strlen($_SESSION['whm_directory_user'])==0 && 
    auth_person_hidden($in_uid) > 0) {
    back_to_index();
}

$thisPerson = "$in_uid";
$sel = "SELECT display_name ";
$sel .= "FROM people_or_places pp ";
$sel .= "WHERE uid='$in_uid' ";
$result = mysql_query ($sel);
if ($result) {
    if ($row = mysql_fetch_array($result)) {
        $thisPerson = $row['display_name'];
    }
}
if (strlen($row['display_name']) < 1) {
    back_to_index();
}

$sel = "SELECT count(*) cnt ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ";
$sel .= "ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
$sel .= "AND $grade_sel ";

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
    $sel .= "AND p.picture_date>'$in_start_date' ";
    $sel .= "AND $grade_sel ";
    if (strlen($_SESSION['whm_directory_user']) == 0) {
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
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<h2><?php echo $thisPerson;?></h2>
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

$sel = "SELECT p.picture_date, d.pid ";
$sel .= "FROM picture_details d ";
$sel .= "JOIN pictures_information p ON (p.pid = d.pid) ";
$sel .= "WHERE d.uid='$in_uid' ";
if (strlen($_SESSION['whm_directory_user']) == 0) {
    $sel .= "AND p.public='Y' ";
}
$sel .= "AND $grade_sel ";
$sel .= "GROUP BY d.pid ";
$sel .= "ORDER BY p.picture_date,p.picture_sequence ";
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
      echo $row['picture_date']."\n";
      echo "<br>\n";
      $cnt = 0;
      $hr = "<hr>\n";
    }
    $pid = $row["pid"];
    $pic_href = "<a href=\"picture_select.php?in_ring_pid=$pid\" target=\"_blank\">";
    $thumb = "<img src=\"display.php?in_pid=$pid&in_size=small\" border=\"0\">";
    echo $pic_href . $thumb . "</a>\n";
    $cnt++;
  }
  echo "</td></tr></table>\n";
}
?>
<br>
<a href="/rings/index.php"><img 
       src="/rings-images/rings.png" 
       alt="Pick a New Ring"
       border="0"></a>
<?php 
if (strlen($_SESSION['s_msg']) > 0) {
    echo "<p>\n";
    echo $_SESSION['s_msg'];
    $_SESSION['s_msg'] = '';
}
?>

</body>
</html>
