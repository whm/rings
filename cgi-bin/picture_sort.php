<?PHP
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_uids        = $_REQUEST['in_uids'];
$in_start_date  = $_REQUEST['in_start_date'];
$in_end_maint   = $_REQUEST['in_end_maint'];
$in_end_date    = $_REQUEST['in_end_date'];
$in_start_maint = $_REQUEST['in_start_maint'];
$in_pid         = $_REQUEST['in_pid'];
$in_description = $_REQUEST['in_description'];
$in_order       = $_REQUEST['in_order'];
$in_new         = $_REQUEST['in_new'];
$in_button_find = $_REQUEST['in_button_find'];
$in_button_next = $_REQUEST['in_button_next'];
$in_button_back = $_REQUEST['in_button_back'];
// ----------------------------------------------------------
//
// -------------------------------------------------------------
// picture_sort.php
// author: Bill MacAllister
// date: October 2002
//

require ('inc_page_open.php');

// database pointers
require ('/etc/whm/rings_dbs.php');
require ('inc_db_connect.php');

$pics_per_page = 100;

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

// ------------------------------------------------------------
// print a row of data

function print_row ($n, $r) {
    global $DBH;

    // get a list of who is in the picture
    $sel = 'SELECT p.uid, ';
    $sel .= 'p.display_name display_name ';
    $sel .= "FROM picture_details d ";
    $sel .= "LEFT OUTER JOIN people_or_places p ";
    $sel .= "ON (d.uid = p.uid) ";
    $sel .= "WHERE d.pid = '".$r['pid']."' ";
    $sel .= "ORDER BY p.display_name ";
    $result = $DBH->query ($sel);
    $plist = '';
    $br = '';
    if ($result) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $plist .= $br . $row['display_name'] . "\n";
            $br = "<hr>\n";
        }
    }

    // check to see if the picture is possibly a duplicate
    $duplicate_list = '';
    $sel = 'SELECT pid ';
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid != ".$r['pid'].' ';
    $sel .= "AND raw_picture_size = ".$r['raw_picture_size']." ";
    $sel .= "AND file_name = '".$r['file_name']."' ";
    $dup_fld_list = array('camera', 'shutter_speed', 'fstop');
    foreach ($dup_fld_list as $fld) {
      if (isset($r[$fld])) {
          $sel .= "AND $fld = '".$r[$fld]."' ";
      } else {
          $sel .= "AND $fld IS NULL ";
      }
    }
    $sel .= "ORDER BY pid ";

    //$duplicate_list .= $sel;
    $result = $DBH->query ($sel);
    $br = '';
    if ($result) {
        $comma = '';
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $duplicate_list .= $comma . $row['pid'];
            $br = ', ';
        }
    }

    $pic_href
        = '<a href="picture_maint.php?in_pid='.$r['pid'].'" target="_blank">';
    $thumb = '<img src="display.php?in_pid='.$r['pid'].'&in_size=small">';
    $up_pid = "up_pid_$n";
    $chk_grade_b = 'CHECKED';
    if ($r['grade'] == 'A') {
        $chk_grade_b = '';
        $chk_grade_a = 'CHECKED';
    } elseif ($r['grade'] == 'C') {
        $chk_grade_b = '';
        $chk_grade_c = 'CHECKED';
    }
    echo " <tr>\n";
    echo "  <td>$pic_href$thumb<a/></td>\n";
    echo '  <td align="center">'.$pic_href.$r['pid']."</a>\n";
    echo '      <input type="hidden"'."\n";
    echo '             name="'.$up_pid.'"'."\n";
    echo '             value="'.$r['pid'].'"'.">\n";
    echo "      <br>\n";
    echo '      '.$r['file_name']."\n";
    if (strlen($duplicate_list) > 0) {
        echo "      <br>Duplicates: $duplicate_list\n";
    }
    echo "  </td>\n";
    echo '  <td><input name="up_picture_date_'.$n.'"'."\n";
    echo '             type="text" size="18"'."\n";
    echo '             value="'.$r['picture_date'].'">'."\n";
    echo "  </td>\n";
    echo "  <td>$plist\n";
    echo "  </td>\n";
    echo "  <td> <input type=\"radio\" name=\"up_rotate_$n\"\n";
    echo "              value=\"LEFT\" >Left &nbsp;\n";
    echo "       <input type=\"radio\" name=\"up_rotate_$n\"\n";
    echo "              value=\"RIGHT\">Right &nbsp;\n";
    echo "       <input type=\"radio\" name=\"up_rotate_$n\"\n";
    echo "              value=\"NONE\" CHECKED>None\n";
    echo "  </td>\n";
    echo "  <td> <input type=\"radio\" name=\"up_grade_$n\"\n";
    echo "              value=\"A\" $chk_grade_a>A &nbsp;\n";
    echo "       <input type=\"radio\" name=\"up_grade_$n\"\n";
    echo "              value=\"B\" $chk_grade_b>B &nbsp;\n";
    echo "       <input type=\"radio\" name=\"up_grade_$n\"\n";
    echo "              value=\"C\" $chk_grade_c>C\n";
    echo "  </td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    echo '  <td colspan="2" align="right">Description:</td>'."\n";
    echo '  <td colspan="5"><textarea name="up_description_'.$n.'" ';
    echo 'rows="2" ';
    echo 'cols="60">'.$r['description']."</textarea>\n";
    echo "  </td>\n";
    echo " <tr>\n";
}

?>

<html>
<head>
<title>Picture Sort</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_select_search.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Picture Sort';
require ('page_top.php');

// Set up if we have been here before
if (isset($in_button_find) || isset($in_new)) {

    $condition = '';
    $condition .= set_search ('picture_date',
                              'start_date', '>', $in_start_date, $condition);
    $condition .= set_search ('picture_date',
                              'end_date',   '<', $in_end_date, $condition);
    $condition .= set_search ('description',
                              'description', '=' ,$in_description ,$condition);
    $condition .= set_search ('date_last_maint',
                              'start_maint', '>', $in_start_maint, $condition);
    $condition .= set_search ('date_last_maint',
                              'end_maint', '<',$in_end_maint, $condition);

    $_SESSION['s_order_by'] = $in_order;

    $word = 'WHERE';
    if (strlen($cond) > 0) {$word = 'AND';}

    # override selections if the special group 'new' is selected
    $uid_condition = '';
    if (isset($in_new)>0) {
        $condition = '';
        $in_uids = array('new');
    }

    if (count($in_uids) > 0) {
        $uid_word .= '(';
        $uid_select = array();
        foreach ($in_uids as $a_uid) {
            if ($a_uid == 'None') {
                $uid_condition = '';
                $uid_select = array();
                break;
            }
            $uid_condition .= "$uid_word d.uid = '$a_uid' ";
            $_SESSION['sear_uids'] .= "$c$a_uid";
            $uid_select[$a_uid] = 1;
            $uid_word = "OR";
        }
        if (strlen($uid_condition) > 0) {
            $uid_condition .= ') ';
            $condition .= "$word  $uid_condition";
            $word = 'AND';
        }
    }
    $sel = "SELECT p.pid, ";
    $sel .= "p.picture_date, ";
    $sel .= "p.description, ";
    $sel .= "p.file_name, ";
    $sel .= "p.grade, ";
    $sel .= "p.raw_picture_size, ";
    $sel .= "p.camera, ";
    $sel .= "p.shutter_speed, ";
    $sel .= "p.fstop, ";
    $sel .= "pop.uid, ";
    $sel .= "pop.display_name ";
    $sel .= "FROM pictures_information p ";
    $sel .= "LEFT OUTER JOIN picture_details d ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "LEFT OUTER JOIN people_or_places pop ";
    $sel .= "ON (d.uid = pop.uid) ";
    $sel .= $condition;
    if ($_SESSION['s_order_by'] == 'p.pid') {
        $sel .= "ORDER BY pid ";
    } else {
        $sel .= "ORDER BY picture_date, pid ";
    }
    $_SESSION['s_list_select'] = $sel;
    $_SESSION['s_start_row'] = 0;

    // find the number of rows
    $result = $DBH->query ($_SESSION['s_list_select']);
    if ($result) {
        $_SESSION['s_num_user_rows'] = $result->num_rows;
    } else {
        $_SESSION['s_num_user_rows'] = 0;
    }

} elseif (isset($in_button_next)) {

    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] + $pics_per_page;

} elseif (isset($in_button_back)) {

    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] - $pics_per_page;
    if ($_SESSION['s_start_row'] < 0) {$_SESSION['s_start_row'] = 0;}

}

$sel = $_SESSION['s_list_select'] . ' LIMIT '
     . $_SESSION['s_start_row'] . ",$pics_per_page ";
$end_row = $_SESSION['s_start_row'] + $pics_per_page;
if ($end_row > $_SESSION['s_num_user_rows']) {
    $end_row = $_SESSION['s_num_user_rows'];
}

?>

<p>
<form method="post" action="<?php print $_SERVER['PHP_SELF'];?>">

<div align="center">
<table border="1">
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
  <td align="right">Description:</td>
  <td>
  <input type="text" name="in_description"
         value="<?php print $_SESSION['sear_description']; ?>">
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
  <td align="right">Person or Place:</td>
  <td>
      <script language="javascript" type="text/javascript">
        var in_group_values  = new Array();
        var in_group_display = new Array();
     </script>
     <input type="text"
            name="in_group_search"
            onkeyup="find_select_items(this, this.form.elements['in_uids[]'], in_group_values, in_group_display);">
     <br>
     <select name="in_uids[]" size="4" multiple>
     <option value="None">None
<?php
$cmd = "SELECT distinct d.uid uid, p.display_name display_name ";
$cmd .= "FROM picture_details d ";
$cmd .= "LEFT OUTER JOIN people_or_places p ";
$cmd .= "ON (p.uid = d.uid) ";
$cmd .= "ORDER BY d.uid ";
$add_cnt = 0;
$result = $DBH->query ($cmd);
if ($result) {
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $s = '';
        if ($uid_select[$row['uid']] > 0) {$s = " SELECTED";}
        echo '   <option value="' . $row['uid'] . "\"$s>"
            . $row['display_name'] . '(' . $row['uid'] . ")\n";
    }
}
?>
     </select>
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
  <input type="submit" name="in_button_find" value="Find">
  </td>
</tr>
</table>

<p>

<?php
echo "$sel<br>\n";
if (strlen($_SESSION['msg']) > 0) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
}

if ($_SESSION['s_num_user_rows']>0) {
    if (($end_row != $_SESSION['s_num_user_rows'])
        || ((strlen($_SESSION['s_start_row'])>0)
            && ($_SESSION['s_start_row'] > 0)) ) {
?>
<table border="1">
<tr><td>
    <table width="100%" border="0">
      <tr>
      <td>
        <?php if ($_SESSION['s_start_row']
                  +$pics_per_page<$_SESSION['s_num_user_rows']) { ?>
        <input type="submit" name="in_button_next" value="Next Page">
        <?php } ?>
      </td>
      <td align="center">
        Records <?php print $_SESSION['s_start_row']; ?> through
        <?php print $end_row; ?> of <?php print $_SESSION['s_num_user_rows'];?>
      </td>
      <td align="right">
        <?php if ((strlen($_SESSION['s_start_row'])>0)
                 && ($_SESSION['s_start_row'] > 0)) { ?>
        <input type="submit" name="in_button_back" value="Previous Page">
        <?php } ?>
      </td>
      </tr>
    </table>
</td></tr>
</table>
<?php
  }
}
?>

</form>


<form method="post" action="picture_sort_action">
<table border="1">
  <tr>
    <th>&nbsp;</th>
    <th>Picture ID</th>
    <th>Date Taken</th>
    <th>Picture Details</th>
    <th>Rotation</th>
    <th>Grade</th>
  </tr>
<?php
$cnt = 0;
$result = $DBH->query ($sel);
$last_row = array();
$people_list = '';
if ($result) {
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($row['pid'] == $last_row['pid']) {
            if (strlen($people_list)>0) {$people_list .= "<br>\n";}
            $people_list .= $row['display_name'];
        } else {
            if ($last_row['pid'] > 0) {
                print_row ($cnt, $last_row);
                $cnt++;
            }
            $last_row = $row;
        }
    }

    if ($last_row['pid'] > 0) {
        print_row ($cnt, $last_row);
    }

    echo "</table>\n";
    echo '<input type="submit" name="in_button_update" value="Update">' . "\n";
    echo "<input type=\"hidden\"\n";
    echo "       name=\"up_picture_cnt\"\n";
    echo "       value=\"$cnt\">\n";
    echo "</form>\n";
} else {
    if (isset($in_button_find)) {
        echo "<font color=\"#ff0000\">Nothing found!</font>\n";
    }
}
if (strlen($_SESSION['s_msg'])>0) {
    echo '<p>'.$_SESSION['s_msg']."\n";
    $_SESSION['s_msg'] = '';
}
?>
</div>

<?php require('page_bottom.php'); ?>
</body>
</html>
