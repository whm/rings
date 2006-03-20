<?PHP
// -------------------------------------------------------------
// groups.php
// author: Bill MacAllister
// date: December 31, 2001
//

require ('inc_page_open.php');

require('mysql.php');

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
<title>Group List</title>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Groups';
require ('page_top.php');
?>

<?php
$next_links = array();

$sel = "SELECT * ";
$sel .= "FROM groups ";
$sel .= "ORDER BY group_id ";
$result = mysql_query ($sel);
if ($result) {
  echo "<table border=\"1\">\n";
  echo " <tr>\n";
  echo "  <th>Group ID</th>\n";
  echo "  <th>Group Name</th>\n";
  echo "  <th>Group Description</th>\n";
  echo "  <th>Date Added</th>\n";
  echo "  <th>Date Last Maint</th>\n";
  echo " </tr>\n";
  while ($row = mysql_fetch_array($result)) {
    $this_id          = trim($row["group_id"]);
    $this_name        = trim($row["group_name"]);
    $this_description = trim($row["group_description"]);
    $this_date_maint  = $row["date_last_maint"];
    $this_date_added  = $row["date_added"];
    $maint = "<a href=\"group_maint?in_group_id=$this_id\">";
    echo " <tr>\n";
    echo "  <td>$maint$this_id</a></td>\n";
    echo "  <td>$this_name</td>\n";
    echo "  <td>$this_description</td>\n";
    echo "  <td>$this_date_added</td>\n";
    echo "  <td>$this_date_maint</td>\n";
    echo " </tr>\n";
  }
  echo "</table>\n";
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>

