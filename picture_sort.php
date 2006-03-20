<?PHP
// -------------------------------------------------------------
// picture_sort.php
// author: Bill MacAllister
// date: October 2002
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
require ('mysql.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
if (!mysql_select_db($mysql_db, $db_link)) {
    echo "<font color=\#ff0000\">";
    echo "Error selecting database $mysql_db";
    echo "</font><br>\n";
}

$pics_per_page = 100;

// ------------------------------------------------------------
// print a row of data

function print_row ($n, $r) {
    
    $sel = 'SELECT p.uid, ';
    $sel .= 'p.display_name ';
    $sel .= "FROM picture_details d ";
    $sel .= "LEFT OUTER JOIN people_or_places p ";
    $sel .= "ON (d.uid = p.uid) ";
    $sel .= "WHERE d.pid = '".$r['pid']."' ";
    $sel .= "ORDER BY p.display_name ";
    $result = mysql_query ($sel);
    $plist = '';
    $br = '';
    if ($result) {
        while ($row = mysql_fetch_array($result)) {
            $plist .= $br . $row['display_name'] . "\n";
            $br = "<hr>\n";
        }
    }
    
    $pic_href = '<a href="picture_maint?in_pid='.$r['pid'].'" target="_blank">';
    $thumb = '<img src="display.php?in_pid='.$r['pid'].'&in_size=small">';
    $up_pid = "up_pid_$n";
    echo " <tr>\n";
    echo "  <td>$thumb</td>\n";
    echo '  <td align="center">'.$pic_href.$r['pid']."</a>\n";
    echo '      <input type="hidden"'."\n";
    echo '             name="'.$up_pid.'"'."\n";
    echo '             value="'.$r['pid'].'"'.">\n";
    echo "      <br>\n";
    echo '      '.$r['file_name']."\n";
    echo "  </td>\n";
    echo '  <td><input name="up_date_taken_'.$n.'"'."\n";
    echo '             type="text" size="18"'."\n";
    echo '             value="'.$r['date_taken'].'">'."\n";
    echo "  </td>\n";
    echo "  <td>$plist\n";
    echo "  </td>\n";
    echo "  <td><input name=\"up_taken_by_$n\"\n";
    echo "             type=\"text\" size=\"10\"\n";
    echo '             value="'.$r['taken_by'].'"'.">\n";
    echo "  </td>\n";
    echo "  <td><input name=\"up_key_words_$n\"\n";
    echo "             type=\"text\" size=\"10\"\n";
    echo '             value="'.$r['key_words'].'">'."\n";
    echo "  </td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    echo '  <td colspan="2" align="right">Description:</td>'."\n";
    echo '  <td colspan="4"><textarea name="up_description_'.$n.'" ';
    echo 'rows="2" ';
    echo 'cols="60">'.$r['description']."</textarea>\n";
    echo "  </td>\n";
    echo " <tr>\n";
}

?>

<html>
<head>
<title>Picture Sort</title>

<?php require('inc_select_search.php'); ?>

</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Picture Sort';
require ('page_top.php');

// Set up if we have been here before
if (strlen($button_find)>0) {
    
    $word = "WHERE";
    $condition = '';
    if ((strlen($in_key.$in_date_taken.$in_taken_by.$in_description) > 0)
        || count($in_uids)>0) {
        $_SESSION['sear_key'] = '';
        $_SESSION['sear_date_taken'] = '';
        $_SESSION['sear_taken_by'] = '';
        $_SESSION['sear_description'] = '';
        $_SESSION['sear_uids'] = '';
    }
    if (strlen($in_key) > 0) {
        if (preg_match('/%/', $in_key)) {
            $condition .= "$word p.key_words LIKE '$in_key' ";
        } else {
            $condition .= "$word p.key_words = '$in_key' ";
        }
        $_SESSION['sear_key'] = $in_key;
        $word = "AND";
    }
    if (strlen($in_date_taken) > 0) {
        if (preg_match('/%/', $in_date_taken)) {
            $condition .= "$word p.date_taken LIKE '$in_date_taken' ";
        } else {
            $condition .= "$word p.date_taken = '$in_date_taken' ";
        }
        $_SESSION['sear_date_taken'] = $in_date_taken;
        $word = "AND";
    }
    if (strlen($in_taken_by) > 0) {
        if (preg_match('/%/', $in_date_by)) {
            $condition .= "$word p.taken_by LIKE '$in_taken_by' ";
        } else {
            $condition .= "$word p.taken_by = '$in_taken_by' ";
        }
        $_SESSION['sear_taken_by'] = $in_taken_by;
        $word = "AND";
    }
    if (strlen($in_description) > 0) {
        if (preg_match('/%/', $in_description)) {
            $condition .= "$word p.description LIKE '$in_description' ";
        } else {
            $condition .= "$word p.description = '$in_description' ";
        }
        $_SESSION['sear_description'] = $in_description;
        $word = "AND";
    }
    $uid_condition = '';  
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
    $sel .= "p.key_words, ";
    $sel .= "p.date_taken, ";
    $sel .= "p.taken_by, ";
    $sel .= "p.description, ";
    $sel .= "p.file_name, ";
    $sel .= "pop.uid, ";
    $sel .= "pop.display_name ";
    $sel .= "FROM pictures p ";
    $sel .= "LEFT OUTER JOIN picture_details d ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "LEFT OUTER JOIN people_or_places pop ";
    $sel .= "ON (d.uid = pop.uid) ";
    $sel .= $condition;
    $sel .= "ORDER BY p.date_taken, p.pid ";
    $_SESSION['s_list_select'] = $sel;
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
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] + $pics_per_page;
    
} elseif (strlen($button_back)>0) {
    
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
         value="<?php print $_SESSION['sear_description']; ?>">
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
$result = mysql_query ($cmd);
if ($result) {
    while ($row = mysql_fetch_array($result)) {
        $s = '';
        if ($uid_select[$row['uid']] > 0) {$s = " SELECTED";}
        echo '   <option value="'.$row['uid']."\"$s>".
            $row['uid']."-".$row['display_name']."\n";
    }
}
?>
     </select>
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
  <input type="submit" name="button_find" value="Find">
  </td>
</tr>
</table>

<p>

<?php 
echo "$sel<br>\n";
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
    <th>Taken By</th>
    <th>Keywords</th>
  </tr>
<?php
$cnt = 0;
$result = mysql_query ($sel);
$last_row = array();
$people_list = '';
if ($result) {
    while ($row = mysql_fetch_array($result)) {
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
    echo "<input type=\"submit\" name=\"btn_update\" value=\"Update\">\n";
    echo "<input type=\"hidden\"\n";
    echo "       name=\"up_picture_cnt\"\n";
    echo "       value=\"$cnt\">\n";
    echo "</form>\n";
} else {
    if (strlen($button_find)>0) {
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
