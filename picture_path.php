<?PHP
// -------------------------------------------------------------
// picture_sort.php
// author: Bill MacAllister
// date: October 2011
//
// Hack of picture sort to display pictures with null group_path

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
    echo '  <td>'.$r['picture_date']."</td>\n";
    echo "  <td>$plist\n";
    echo "  </td>\n";
    echo "  <td>".$r['group_path']."  </td>\n";
    echo "</tr>\n";
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

if (strlen($button_next)>0) {
    
    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] + $pics_per_page;
    
} elseif (strlen($button_back)>0) {
    
    $in_pid = $_SESSION['s_pid'];
    $_SESSION['s_start_row'] = $_SESSION['s_start_row'] - $pics_per_page;
    if ($_SESSION['s_start_row'] < 0) {$_SESSION['s_start_row'] = 0;}
    
} else {

    $sel = "SELECT p.pid, ";
    $sel .= "p.key_words, ";
    $sel .= "p.picture_date, ";
    $sel .= "p.taken_by, ";
    $sel .= "p.description, ";
    $sel .= "p.file_name, ";
    $sel .= "p.group_path, ";
    $sel .= "pop.uid, ";
    $sel .= "pop.display_name ";
    $sel .= "FROM pictures_information p ";
    $sel .= "LEFT OUTER JOIN picture_details d ";
    $sel .= "ON (p.pid = d.pid) ";
    $sel .= "LEFT OUTER JOIN people_or_places pop ";
    $sel .= "ON (d.uid = pop.uid) ";
    $sel .= "WHERE p.group_path IS NULL ";
    $_SESSION['s_list_select'] = $sel;
    $_SESSION['s_start_row'] = 0;
    
    // find the number of rows
    $result = mysql_query ($_SESSION['s_list_select']);
    if ($result) {
        $_SESSION['s_num_user_rows'] = mysql_num_rows($result); 
    } else {
        $_SESSION['s_num_user_rows'] = 0;
    }
    
    $sel = $_SESSION['s_list_select'] . ' LIMIT ' 
        . $_SESSION['s_start_row'] . ",$pics_per_page ";
    $end_row = $_SESSION['s_start_row'] + $pics_per_page;
    if ($end_row > $_SESSION['s_num_user_rows']) {
        $end_row = $_SESSION['s_num_user_rows'];
    }
}
?>

<p>
<form method="post" action="<?php print $PHP_SELF;?>">

<p>
<input type="submit" name="button_refresh" value="Refresh">
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


<table border="1">
  <tr>
    <th>&nbsp;</th>
    <th>Picture ID</th>
    <th>Date Taken</th>
    <th>Picture Details</th>
    <th>Path</th>
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
