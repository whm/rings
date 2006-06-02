<?PHP
// -------------------------------------------------------------
// people_search.php
// author: Bill MacAllister
// date: 31-Dec-2001
//

require ('inc_page_open.php');

// -- Print a space or the field
function prt ($fld) {
    $str = trim ($fld);
    if (strlen($str) == 0) {
        $str = "&nbsp;";
    } 
    return $str;
}

//-------------------------------------------------------------
// Start of main processing for the page

// database pointers
require ('inc_dbs.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
if (!mysql_select_db($mysql_db, $db_link)) {
    echo "<font color=\#ff0000\">";
    echo "Error selecting database $mysql_db";
    echo "</font><br>\n";
}
?>

<html>
<head>
<title>People Search</title>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'People Search';
require ('page_top.php');

// Set up if we have been here before
if (isset($button_find)) {
    $word = "WHERE";
    $condition = '';
    $_SESSION['sp_uid'] = '';
    $_SESSION['sp_name'] = '';
    $_SESSION['sp_dob'] = '';
    $_SESSION['sp_description'] = '';
    if (strlen($in_uid)>0) {
        $condition .= "$word uid LIKE '%$in_uid%' ";
        $_SESSION['sp_uid'] = $in_uid;
        $word = "AND";
    }
    if (strlen($in_name)>0) {
        $condition .= "$word display_name LIKE '%$in_name%' ";
        $_SESSION['sp_name'] = $in_name;
        $word = "AND";
    }
    if (strlen($in_dob)>0) {
        $condition .= "$word date_of_birth LIKE '%$in_dob%' ";
        $_SESSION['sp_dob'] = $in_dob;
        $word = "AND";
    }
    if (strlen($in_description)>0) {
        $condition .= "$word description LIKE '%$in_description%' ";
        $_SESSION['sp_description'] = $in_description;
        $word = "AND";
    }
    $_SESSION['sp_list_select'] = "SELECT uid, "
        . "display_name, "
        . "date_of_birth, "
        . "description "
        . "FROM people_or_places "
        . "$condition "
        . "ORDER BY uid ";
    $_SESSION['sp_start_row'] = 0;
    // find the number of rows
    $result = mysql_query ($_SESSION['sp_list_select']);
    if ($result) {
        $_SESSION['sp_num_user_rows'] = mysql_num_rows($result); 
    } else {
        $_SESSION['sp_num_user_rows'] = 0;
    }
} elseif (strlen($button_next)>0) {
    $in_uid = $_SESSION['sp_uid'];
    $_SESSION['sp_start_row'] = $_SESSION['sp_start_row'] + 30;
} elseif (strlen($button_back)>0) {
    $in_uid = $_SESSION['sp_uid'];
    $_SESSION['sp_start_row'] = $_SESSION['sp_start_row'] - 30;
    if ($_SESSION['sp_start_row'] < 0) {$_SESSION['sp_start_row'] = 0;}
}

$sel = $_SESSION['sp_list_select']." LIMIT ".$_SESSION['sp_start_row'].",30 ";
$end_row = $_SESSION['sp_start_row'] + 30;
if ($end_row > $_SESSION['sp_num_user_rows']) {
    $end_row = $_SESSION['sp_num_user_rows'];
}
?>

<p>
<form method="post" action="<?php print $PHP_SELF;?>">

<div align="center">
<table>
<tr><td align="right">UserID:</td>
    <td> 
    <input type="text" name="in_uid" 
           value="<?php print $_SESSION['sp_uid']; ?>">
    </td>
</tr>
<tr>
  <td align="right">Display Name:</td>
  <td>
  <input type="text" name="in_name" 
         value="<?php print $_SESSION['sp_name']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Date of Birth:</td>
  <td>
  <input type="text" name="in_dob" 
         value="<?php print $_SESSION['sp_dob']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Description:</td>
  <td>
  <input type="text" name="in_description" 
         value="<?php print $_SESSION['sp_description']; ?>">
  </td>
</tr>
<tr>
  <td colspan="2" align="center">
  <input type="submit" name="button_find" value="Find">
  </td>
</tr>
</table>

<?php 
   if ($_SESSION['sp_num_user_rows']>0) {
?>
<table border="1">

<?php 
$start_row_flag = 0;
if ($_SESSION['sp_start_row'] > 0) {
    $start_row_flag = 1;
}

if ($end_row != $_SESSION['sp_num_user_rows'] || $start_row_flag>0) {
?>
  <tr>
    <td colspan="4">
    <table width="100%" border="0">
      <tr>
      <td>
        <?php if ($_SESSION['sp_start_row']+30<$_SESSION['sp_num_user_rows']) {?>
        <input type="submit" name="button_next" value="Next Page">
        <?php } ?>
      </td>
      <td align="center">
        Records <?php print $_SESSION['sp_start_row']; ?> through
        <?php print $end_row; ?> of <?php print $_SESSION['sp_num_user_rows'];?>
      </td>
      <td align="right">
        <?php if ($start_row_flag>0) { ?>
        <input type="submit" name="button_back" value="Previous Page"> 
        <?php } ?>
      </td>
      </tr>
    </table>
    </td>
  </tr>
<?php } ?>
  <tr>
    <th>UID</th>
    <th>Display Name</th>
    <th>Date of Birth</th>
    <th>Description</th>
  </tr>
<?php
    $result = mysql_query ($sel);
    if ($result) {
      while ($row = mysql_fetch_array($result)) {
        $uid = $row["uid"];
        $user_href = urlencode("$uid");
        $user_href = "<a href=\"people_maint?in_uid=$user_href\">";
        echo " <tr>\n";
        echo "  <td>".$user_href.$row["uid"]."</a></td>\n";
        echo "  <td>".$row["display_name"]."</td>\n";
        echo "  <td>".$row["date_of_birth"]."</td>\n";
        echo "  <td>".$row["description"]."</td>\n";
        echo " <tr>\n";
      }
    }
    echo "</table>\n";
  } else {
    if (strlen($button_find)>0) {
      echo "<font color=\"#ff0000\">Nothing found!</font>\n";
      echo "<p>\n";
      echo "$sel\n";
    }
  }
?>
</form>
</div>

<?php require('page_bottom.php'); ?>
</body>
</html>
