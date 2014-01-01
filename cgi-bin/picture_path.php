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
<title>Paths</title>
<?php require('inc_select_search.php'); ?>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Paths';
require ('page_top.php');

if (strlen($start_row) == 0) {$start_row = 0;}

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
$sel .= "LIMIT $start_row,100 ";
?>

<p>
<form method="post" action="<?php print $_SERVER['PHP_SELF'];?>">

<p>
<input type="submit" name="button_refresh" value="Refresh">
<input type="text" name="start_row" value="<?php print $start_row;?>">
<p>

<?php 
echo "$sel<br>\n";
if (strlen($_SESSION['msg']) > 0) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
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
