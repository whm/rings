<?PHP
// -------------------------------------------------------------
// people_search.php
// author: Bill MacAllister
// date: 31-Dec-2001
//

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_displayname = get_request('in_displayname');
$in_description = get_request('in_description');
$in_uid         = get_request('in_uid');
$in_visibility  = get_request('in_visibility');
$in_cn          = get_request('in_cn');
$in_button_find = get_request('in_button_find');
$in_button_next = get_request('in_button_next');
$in_button_back = get_request('in_button_back');

//-------------------------------------------------------------
// Start of main processing for the page
?>

<html>
<head>
<title>People Search</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'People Search';
require ('page_top.php');

// Set up if we have been here before
if (!empty($in_button_find)) {
    $word = "WHERE";
    $condition = '';
    $_SESSION['sp_uid']         = '';
    $_SESSION['sp_displayname'] = '';
    $_SESSION['sp_cn']          = '';
    $_SESSION['sp_dob']         = '';
    $_SESSION['sp_visibility']  = '';
    $_SESSION['sp_description'] = '';
    if (strlen($in_uid)>0) {
        $condition .= "$word uid LIKE '%$in_uid%' ";
        $_SESSION['sp_uid'] = $in_uid;
        $word = "AND";
    }
    if (strlen($in_displayname)>0) {
        $condition .= "$word display_name LIKE '%$in_displayname%' ";
        $_SESSION['sp_displayname'] = $in_displayname;
        $word = "AND";
    }
    if (strlen($in_cn)>0) {
        $condition .= "$word display_name LIKE '%$in_cn%' ";
        $_SESSION['sp_cn'] = $in_cn;
        $word = "AND";
    }
    if (strlen($in_visibility)>0) {
        if ($in_visibility == 'ALL') {
            $_SESSION['sp_visibility'] = '';
        } else {
            $condition .= "$word visibility = '$in_visibility' ";
            $_SESSION['sp_visibility'] = $in_visibility;
            $word = "AND";
        }
    }
    if (strlen($in_description)>0) {
        $condition .= "$word description LIKE '%$in_description%' ";
        $_SESSION['sp_description'] = $in_description;
        $word = "AND";
    }
    $_SESSION['sp_list_select'] = "SELECT * "
        . "FROM people_or_places "
        . "$condition "
        . "ORDER BY uid ";
    $_SESSION['sp_start_row'] = 0;
    // find the number of rows
    $result = $DBH->query($_SESSION['sp_list_select']);
    if ($result) {
        $_SESSION['sp_num_user_rows'] = $result->num_rows;
    } else {
        $_SESSION['sp_num_user_rows'] = 0;
    }
} elseif (!empty($in_button_next)) {
    $in_uid = $_SESSION['sp_uid'];
    $_SESSION['sp_start_row'] = $_SESSION['sp_start_row'] + 30;
} elseif (!empty($in_button_back)) {
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
<form method="post" action="<?php print $_SERVER['PHP_SELF'];?>">

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
  <input type="text" name="in_displayname"
         value="<?php print $_SESSION['sp_displayname']; ?>">
  </td>
</tr>
<tr>
  <td align="right">Common Name:</td>
  <td>
  <input type="text" name="in_cn"
         value="<?php print $_SESSION['sp_cn']; ?>">
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
  <td align="right">Visibility:</td>
  <td>
<?php
$chk_all = $chk_show = $chk_hide = $chk_invis = '';
if (!empty($_SESSION['sp_visibility'])) {
    if ($_SESSION['sp_visibility'] == 'SHOW') {
        $chk_show  = 'CHECKED';
    }
    if ($_SESSION['sp_visibility'] == 'HIDDEN') {
        $chk_hide  = 'CHECKED';
    }
    if ($_SESSION['sp_visibility'] == 'INVISIBLE') {
        $chk_invis = 'CHECKED';
    } else {
        $chk_all   = 'CHECKED';
    }
} else {
    $chk_all   = 'CHECKED';
}
  ?>
  <input type="radio" name="in_visibility" value="ALL" <?php echo $chk_all;?>>All
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_visibility" value="SHOW" <?php echo $chk_show;?>>Show
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_visibility" value="HIDDEN" <?php echo $chk_hide;?>>Hidden
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="in_visibility" value="INVISIBLE" <?php echo $chk_invis;?>>Invisibile
  </td>
</tr>
<tr>
  <td colspan="2" align="center">
  <input type="submit" name="in_button_find" value="Find">
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
    <td colspan="5">
    <table width="100%" border="0">
      <tr>
      <td>
        <?php if ($_SESSION['sp_start_row']+30<$_SESSION['sp_num_user_rows']) {?>
        <input type="submit" name="in_button_next" value="Next Page">
        <?php } ?>
      </td>
      <td align="center">
        Records <?php print $_SESSION['sp_start_row']; ?> through
        <?php print $end_row; ?> of <?php print $_SESSION['sp_num_user_rows'];?>
      </td>
      <td align="right">
        <?php if ($start_row_flag>0) { ?>
        <input type="submit" name="in_button_back" value="Previous Page">
        <?php } ?>
      </td>
      </tr>
    </table>
    </td>
  </tr>
<?php } ?>
  <tr>
    <th>UID</th>
    <th>Common Name</th>
    <th>Display Name</th>
    <th>Description</th>
    <th>Public</th>
  </tr>
<?php
    $result = $DBH->query($sel);
    if ($result) {
      while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $uid       = $row["uid"];
        $user_href = urlencode("$uid");
        $user_href = "<a href=\"people_maint.php?in_uid=$user_href\">";
        echo " <tr>\n";
        echo "  <td>".$user_href.$row["uid"]."</a></td>\n";
        echo "  <td>".$row["cn"]."</td>\n";
        echo "  <td>".$row["display_name"]."</td>\n";
        echo "  <td>".$row["description"]."</td>\n";
        echo "  <td align=\"center\">".$row['visibility']."</td>\n";
        echo " <tr>\n";
      }
    }
    echo "</table>\n";
  } else {
    if (!empty($in_button_find)) {
      echo "<font color=\"#ff0000\">Nothing found!</font>\n";
      echo "<p>\n";
      echo "$sel\n";
    }
  }
?>
</form>

<?php require('page_bottom.php'); ?>
</body>
</html>
