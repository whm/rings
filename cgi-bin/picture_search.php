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
require ('/etc/whm/rings_dbs.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
if (!mysql_select_db($mysql_db, $db_link)) {
  echo "<font color=\#ff0000\">";
  echo "Error selecting database $mysql_db";
  echo "</font><br>\n";
}

// ------------------------------------------------------------
// format an sql condition clause

function set_search ($fld, $sess_fld, $op, $val, $cond) {

    if (strlen($cond) > 0) {
        $word = 'AND';
    } else {
        $word = 'WHERE';
    }

    $new = '';
    if (strlen($val) > 0) {
        if ($op == '=') {
            if (preg_match('/%/', $val)) {
                $new .= "$word p.$fld LIKE '$val' ";
            } else {
                $new .= "$word p.$fld = '$val' ";
            }
        } else {
            $new = "$word p.$fld $op '$val' ";
        }
    }
    $_SESSION["sear_$sess_fld"] = $val;

    return $new;
}

?>

<html>
<head>
<title>Picture Search</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Picture Search';
require ('page_top.php');

  // how many to display
  if (strlen($in_count) > 0) {
    $_SESSION['sear_count'] = $in_count;
  } else {
    $in_count = $_SESSION['sear_count'];
  }
  if ($in_count == 0) {$in_count = 30;}

  // Set up if we have been here before
  if (strlen($button_find)>0) {

    $condition = '';
    $condition .= set_search ('key_words',      'key',        '=',$in_key,        $condition);
    $condition .= set_search ('picture_date',   'start_date', '>',$in_start_date, $condition);
    $condition .= set_search ('picture_date',   'end_date',   '<',$in_end_date,   $condition);
    $condition .= set_search ('taken_by',       'taken_by',   '=',$in_taken_by,   $condition);
    $condition .= set_search ('description',    'description','=',$in_description,$condition);
    $condition .= set_search ('date_last_maint','start_maint','>',$in_start_maint,$condition);
    $condition .= set_search ('date_last_maint','end_maint',  '<',$in_end_maint,  $condition);

    $_SESSION['s_order_by'] = $in_order;

    $_SESSION['s_list_select'] = "SELECT pid, "
              . "key_words, "
              . "picture_date, "
              . "taken_by, "
              . "description "
              . "FROM pictures_information p "
              . "$condition ";
    if ($_SESSION['s_order_by'] == 'p.pid') {
        $_SESSION['s_list_select'] .= "ORDER BY pid ";
    } else {
        $_SESSION['s_list_select'] .= "ORDER BY picture_date, pid ";
    }

    $_SESSION['s_start_row'] = 0;
    // find the number of rows
    $result = mysql_query ($_SESSION['s_list_select']);
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
  <td align="right">Picture Date Range:</td>
  <td>
  Start:<input type="text" name="in_start_date" 
               value="<?php print $_SESSION['sear_start_date']; ?>">
  End:<input type="text" name="in_end_date" 
               value="<?php print $_SESSION['sear_end_date']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Date Last Maint Range:</td>
  <td>
  Start:<input type="text" name="in_start_maint" 
         value="<?php print $_SESSION['sear_start_maint']; ?>">
  End:<input type="text" name="in_end_maint" 
         value="<?php print $_SESSION['sear_end_maint']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Count:</td>
  <td>
  <input type="text" name="in_count" size=6 
         value="<?php print $in_count; ?>">
  </td>
</tr>

<?php
if ($_SESSION['s_order_by'] == 'p.pid') {
    $sel_pid = 'CHECKED';
} else {
    $sel_date = 'CHECKED';
}
?>
<tr>
  <td align="right">Order By:</td>
  <td>
  Picture Date: <input type="radio" name="in_order" 
              <?php echo $sel_date;?> value="p.picture_date">
  Picture ID: <input type="radio" name="in_order" 
              <?php echo $sel_pid;?> value="p.pid">
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
        echo "  <td>".$row["picture_date"]."</td>\n";
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
<?php print $sel;?>
<?php require('page_bottom.php'); ?>
</body>
</html>
