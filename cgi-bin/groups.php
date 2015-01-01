<?PHP
// -------------------------------------------------------------
// groups.php
// author: Bill MacAllister
// date: December 31, 2001
//

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
?>
<html>
<head>
<title>Group List</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
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
$result = $DBH->query($sel);
if ($result) {
  echo "<table border=\"1\">\n";
  echo " <tr>\n";
  echo "  <th>Group ID</th>\n";
  echo "  <th>Group Name</th>\n";
  echo "  <th>Group Description</th>\n";
  echo "  <th>Date Added</th>\n";
  echo "  <th>Date Last Maint</th>\n";
  echo " </tr>\n";
  while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
    $this_id          = trim($row["group_id"]);
    $this_name        = trim($row["group_name"]);
    $this_description = trim($row["group_description"]);
    $this_date_maint  = $row["date_last_maint"];
    $this_date_added  = $row["date_added"];
    $maint = "<a href=\"group_maint.php?in_group_id=$this_id\">";
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
