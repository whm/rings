<?PHP
// -------------------------------------------------------------
// rings.php
// author: Bill MacAllister
// date: December 31, 2001
//

// Open a session
require('whm_php_sessions.inc');
require('whm_php_auth.inc');
require('inc_auth_policy.php');

if ($in_logout>0) {session_destroy();}

require ('/etc/whm/rings_dbs.php');

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
  $msg = $msg . "<br>Error connecting to MySQL host $mysql_host";
  echo "$msg";
  exit;
}
$result = mysql_select_db($mysql_db);
if (!$result) {
  $msg = $msg . "<br>Error connecting to MySQL db $mysql_db";
  echo "$msg";
  exit;
}

?>
<html>
<head>
<title>Rings</title>
<?php include('ring_style.css');?>
</head>

<body bgcolor="#eeeeff">

<?php
$next_links = array();

if (isset($in_ring_pid)) {

  // display picture and links

  $sel = "SELECT * ";
  $sel .= "FROM pictures_information ";
  $sel .= "WHERE pid=$in_ring_pid ";
  $result = mysql_query ($sel);
  if ($result) {
    $row = mysql_fetch_array($result);
    $this_type = trim($row["picture_type"]);
    $this_pid = $row["pid"];
    $this_date_taken = $row["date_taken"];
    $this_fullbytes = sprintf ('%7.7d', strlen($row["picture"])/1024);
    echo "<img src=\"/rings/display.php?in_pid=$this_pid&in_size=large\">\n";
    echo "<p>\n";
    $sel = "SELECT det.uid, ";
    $sel .= "person.display_name ";
    $sel .= "FROM picture_details det, people_or_places person ";
    $sel .= "WHERE pid=$in_ring_pid ";
    $sel .= "AND det.uid = person.uid ";
    $result = mysql_query ($sel);
    if ($result) {
      while ($row = mysql_fetch_array($result)) {
        $this_uid          = $row["uid"];
        $this_display_name = $row["display_name"];
        $nxt_sel = "SELECT p.pid ";
        $nxt_sel .= "FROM picture_details det, pictures_information p ";
        $nxt_sel .= "WHERE det.pid = p.pid ";
        $nxt_sel .= "AND det.uid = '$this_uid' ";
        $nxt_sel .= "AND p.date_taken > '$this_date_taken' "; 
        $nxt_sel .= "ORDER BY p.date_taken ";
        $nxt_sel .= "LIMIT 1 ";
        $nxt_result = mysql_query ($nxt_sel);
        if ($nxt_result) {
          if ( $nxt_row = mysql_fetch_array($nxt_result) ) {
            $new_pid = $nxt_row["pid"];
            $next_links[$this_display_name] = $new_pid;
          } else {
            $nxt_sel = "SELECT p.pid pid ";
            $nxt_sel .= "FROM picture_details det, pictures_information p ";
            $nxt_sel .= "WHERE det.pid = p.pid ";
            $nxt_sel .= "AND det.uid = '$this_uid' ";
            $nxt_sel .= "ORDER BY p.date_taken ";
            $nxt_sel .= "LIMIT 1 ";
            $nxt_result = mysql_query ($nxt_sel);
            if ( $nxt_row = mysql_fetch_array($nxt_result) ) {
              $new_pid = $nxt_row["pid"];
              $next_links["$this_display_name ."] = $new_pid;
            } else {
              $in_ring_pid = '';
            }
          }
        }
      }
    }
  } else {
    $in_ring_pid = '';
  }
}

$display_rings = 0;
if (strlen($in_ring_pid) == 0) {
  $display_rings = 1;
}

if ($display_rings>0) {

  // ------------------------------------------
  // display ring choices

  echo "<h1>Pick a Picture Ring</h1>\n";
  echo "<p>\n";
  echo "<table border=\"0\">\n";
  $sel = "SELECT uid, display_name, description ";
  $sel .= "FROM people_or_places ";
  $sel .= "ORDER BY display_name ";
  $result = mysql_query ($sel);
  if ($result) {
    while ($row = mysql_fetch_array($result)) {
      $this_uid = $row["uid"];
      $this_name = $row["display_name"];
      $this_description = $row["description"];
      $nxt_sel = "SELECT p.pid pid ";
      $nxt_sel .= "FROM picture_details det, pictures_information p ";
      $nxt_sel .= "WHERE det.pid = p.pid ";
      $nxt_sel .= "AND det.uid = '$this_uid' ";
      $nxt_sel .= "ORDER BY p.date_taken ";
      $nxt_sel .= "LIMIT 1 ";
      $nxt_result = mysql_query ($nxt_sel);
      if ($nxt_result) {
        $nxt_row = mysql_fetch_array($nxt_result);
        $new_pid = $nxt_row["pid"];
        if (strlen($new_pid) > 0) {
          echo "<tr bgcolor=\"#000066\">\n";
          echo "  <td colspan=\"2\"><img src=\"/rings-images/shim.gif\"></td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
          echo "  <td><b><a href=\"rings.php?in_ring_pid=$new_pid\">";
          echo "$this_name";
          echo "</a></b></td> \n";
          echo "  <td>";
          echo "$this_description";
          echo "</td>\n";
          echo "</tr>\n";
        } else {
          echo "<tr>\n";
          echo "<td><b>$this_name</b></td><td> $this_description</td>\n";
          echo "</tr>\n";
        }
      }
    }
  }
  echo "</table>\n";
  echo "<p>\n";
  echo "<h5><a href=\"picture_maint.php\">Picture Maintenance</a></h5>\n";

} else {

  // ------------------------------------------
  // display a picture

  if (count($next_links)>0) {
    asort($next_links);
    foreach ($next_links as $thisName => $thisID) {
      $urlName = urlencode($thisName);
      echo "<a href=\"rings.php?in_ring_pid=$thisID\">";
      echo "<img src=\"button.php?in_button=$urlName\"></a><br>\n";
    }
  }
  echo "<p>\n";
  echo "<a href=\"rings?in_ring_pid=\">Pick a new Ring</a>\n";
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  echo "<a href=\"/rings/display.php?in_pid=$this_pid\" ";
  echo "target=\"_blank\">Image Full Size ($this_fullbytes Kbytes)</a>\n";
  if (strlen($_SESSION['whm_directory_user'])>0) {
    echo "<p>\n";
    echo "<h5><a href=\"picture_maint?in_pid=$this_pid\" ";
    echo "target=\"_blank\">$this_pid</a> ";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"rings.php?in_logout=1\">Logout</a></h5>\n";
  }
}

?>

</Body>
</html>

