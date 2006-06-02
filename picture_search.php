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
<title>Picture Search</title>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Picture Search';
require ('page_top.php');

  // how many to display
  if (strlen($in_count) > 0) {
    if (!session_is_registered('sear_count')) {session_register('sear_count');}
    $_SESSION['sear_count'] = $in_count;
  } else {
    $in_count = $_SESSION['sear_count'];
  }
  if ($in_count == 0) {$in_count = 30;}

  // Set up if we have been here before
  if (strlen($button_find)>0) {
    $word = "WHERE";
    $condition = '';
    if (!session_is_registered('sear_key')) {
      session_register('sear_key');
      $_SESSION['sear_key'] = '';
    }
    if (!session_is_registered('sear_date_taken')) {
      session_register('sear_date_taken');
      $_SESSION['sear_date_taken'] = '';
    }
    if (!session_is_registered('sear_taken_by')) {
      session_register('sear_taken_by');
      $_SESSION['sear_taken_by'] = '';
    }
    $sear_picture_type = '';
    $sear_picture_description = '';
    if (strlen($in_key) > 0) {
      $condition .= "$word key_words LIKE '%$in_key%' ";
      $_SESSION['sear_key'] = $in_key;
      $word = "AND";
    }
    if (strlen($in_date_taken) > 0) {
      $condition .= "$word date_taken LIKE '%$in_date_taken%' ";
      $_SESSION['sear_date_taken'] = $in_date_taken;
      $word = "AND";
    }
    if (strlen($in_taken_by) > 0) {
      $condition .= "$word taken_by LIKE '%$in_taken_by%' ";
      $_SESSION['sear_taken_by'] = $in_taken_by;
      $word = "AND";
    }
    if (strlen($in_description) > 0) {
      $condition .= "$word description LIKE '%$in_description%' ";
      if (!session_is_registered('sear_description')) {
        session_register('sear_description');
      }
      $sear_description = $in_description;
      $word = "AND";
    }
    if (!session_is_registered('s_list_select')) {
      session_register('s_list_select');
    }
    $_SESSION['s_list_select'] = "SELECT pid, "
              . "key_words, "
              . "date_taken, "
              . "taken_by, "
              . "description "
              . "FROM pictures "
              . "$condition "
              . "ORDER BY date_taken, pid ";
    if (!session_is_registered("s_start_row")) {
      session_register("s_start_row");
    }
    $_SESSION['s_start_row'] = 0;
    // find the number of rows
    $result = mysql_query ($_SESSION['s_list_select']);
    if (!session_is_registered("s_num_user_rows")) {
      session_register("s_num_user_rows");
    }
    if ($result) {
      $_SESSION['s_num_user_rows'] = mysql_num_rows($result); 
    } else {
      $_SESSION['s_num_user_rows'] = 0;
    }
  } elseif (strlen($button_next)>0) {
    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] + $in_count;
  } elseif (strlen($button_back)>0) {
    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] - $in_count;
    if ($_SESSION['s_start_row'] < 0) {$_SESSION['s_start_row'] = 0;}
  }

  $sel = $_SESSION['s_list_select'] . ' LIMIT '
       . $_SESSION['s_start_row'] . ",$in_count ";
  $end_row = $_SESSION['s_start_row'] + $in_count;
  if ($end_row > $_SESSION['s_num_user_rows']) {
    $end_row = $_SESSION['s_num_user_rows'];
  }
?>

<p>
<form method="post" action="<?php print $PHP_SELF;?>">

<div align="center">
<table>
<tr><td align="right">Keywords:</td>
    <td> 
    <input type="text" name="in_key" 
           value="<?php print $_SESSION['sear_key']; ?>">
    </td>
</tr>
<tr>
  <td align="right">Date Taken:</td>
  <td>
  <input type="text" name="in_date_taken" 
         value="<?php print $_SESSION['sear_date_taken']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Taken By:</td>
  <td>
  <input type="text" name="in_taken_by" 
         value="<?php print $_SESSION['sear_taken_by']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Description:</td>
  <td>
  <input type="text" name="in_description" 
         value="<?php print $sear_description; ?>">
  </td>
</tr>
<tr>
  <td align="right">Count:</td>
  <td>
  <input type="text" name="in_count" size=6 
         value="<?php print $in_count; ?>">
  </td>
</tr>
<tr>
  <td colspan="2" align="center">
  <input type="submit" name="button_find" value="Find">
  </td>
</tr>
</table>

<?php 
   if ($_SESSION['s_num_user_rows']>0) {
?>
<table border="1">

<?php if (($end_row != $_SESSION['s_num_user_rows']) 
      || ((strlen($_SESSION['s_start_row'])>0) 
          && ($_SESSION['s_start_row'] > 0)) ) {
?>
  <tr>
    <td colspan="6">
    <table width="100%" border="0">
      <tr>
      <td>
        <?php if ($_SESSION['s_start_row']
                  +$in_count<$_SESSION['s_num_user_rows']) { ?>
        <input type="submit" name="button_next" value="Next Page">
        <?php } ?>
      </td>
      <td align="center">
        Records <?php print $_SESSION['s_start_row']; ?> through
        <?php print $end_row; ?> of <?php print $_SESSION['s_num_user_rows'];?>
      </td>
      <td align="right">
        <?php if ((strlen($_SESSION['s_start_row'])>0) 
                  && ($_SESSION['s_start_row'] > 0)) { ?>
        <input type="submit" name="button_back" value="Previous Page"> 
        <?php } ?>
      </td>
      </tr>
    </table>
    </td>
  </tr>
<?php } ?>
  <tr>
    <th>&nbsp;</th>
    <th>Picture ID</th>
    <th>Date Taken</th>
    <th>Taken By</th>
    <th>Keywords</th>
    <th>Description</th>
  </tr>
<?php
    $result = mysql_query ($sel);
    if ($result) {
      while ($row = mysql_fetch_array($result)) {
        $pid = $row["pid"];
        $pic_href = "<a href=\"picture_maint?in_pid=$pid\" target=\"_blank\">";
        $thumb = "<img src=\"display.php?in_pid=$pid&in_size=small\">";
        echo " <tr>\n";
        echo "  <td>$thumb</td>\n";
        echo "  <td>".$pic_href.$row["pid"]."</a></td>\n";
        echo "  <td>".$row["date_taken"]."</td>\n";
        echo "  <td>".$row["taken_by"]."</td>\n";
        echo "  <td>".$row["key_words"]."</td>\n";
        echo "  <td>".$row["description"]."</td>\n";
        echo " <tr>\n";
      }
    }
    echo "</table>\n";
  } else {
    if (strlen($button_find) > 0) {
      echo "$sel<br>\n";
      echo "<font color=\"#ff0000\">Nothing found!</font>\n";
      echo "<p>\n";
      echo "Two Suggestions:\n";
      echo "<blockquote>\n";
      echo "<ul>\n";
      echo "<li> Broaden your search and sort the results ";
      echo "using the column heading links.\n";
      echo "<li>Use a wild card.  The percent, \"%\", is ";
      echo "the wild card character.\n";
      echo "</ul>\n";
      echo "</blockquote>\n";
    }
  }
?>
</form>
</div>

<?php require('page_bottom.php'); ?>
</body>
</html>
