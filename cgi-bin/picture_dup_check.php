<?PHP
// -------------------------------------------------------------
// picture_dup_check.php
// author: Bill MacAllister
// date: August 2025

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_button_refesh = get_request('in_button_refresh');
$in_button_update = get_request('in_button_update');

$pics_per_page = 100;

##############################################################################
# Main Routine
##############################################################################
?>

<html>
<head>
<title>Duplicate Check</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Duplicate Check';
require ('page_top.php');
?>

<p>

This is a potentially dangerous operation since all references
and all picture files are deleted from the Rings database.
Use with care.

<div align="center">
<form method="post" action="<?php print $_SERVER['PHP_SELF'];?>">
  <input type="submit" name="in_button_refresh" value="Refresh">

</form>
</div>
<?php sys_display_msg(); ?>

<?php
$sel = 'SELECT root.pid rootpid';
$sel .= ', leaf.pid leafpid';
$sel .= ', root.raw_signature rootsignature';
$sel .= ', leaf.raw_signature leafsignature';
$sel .= ' FROM pictures_information root';
$sel .= ' LEFT OUTER JOIN pictures_information leaf';
$sel .= ' ON root.pid != leaf.pid';
$sel .= ' AND root.raw_picture_size = leaf.raw_picture_size';
$sel .= ' AND root.raw_signature = leaf.raw_signature';
$sel .= ' WHERE leaf.pid IS NOT NULL';
$sel .= ' AND root.pid < leaf.pid';
$sel .= ' order by rootpid, leafpid';
$sel .= ' LIMIT 0,100';

$result = $DBH->query($sel);
if ($result) {
?>
<form method="post" action="picture_dup_check_action.php">
<table border="1">
  <tr>
    <th>Delete Root</th>
    <th>Root PID</th>
    <th>Root Picture</th>
    <th>Delete Leaf</th>
    <th>Leaf PID</th>
    <th>Leaf Picture</th>
  </tr>
<?php
    $cnt = 0;
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $rootDel =  '<input type="checkbox" name="in_root_' . $cnt .'"';
        $rootDel .= ' value="delete">' . "\n";
        $rootDel .= '<input type="hidden"   name="in_root_pid_' . $cnt . '"';
        $rootDel .= ' value="' . $row['rootpid'] . '">' . "\n";
        $rootImg = '<img src="display.php';
        $rootImg .= '?in_pid=' . $row['rootpid'];
        $rootImg .= '&in_size=' . $CONF['index_size'];
        $rootImg .= '">';
        
        $leafDel =  '<input type="checkbox" name="in_leaf_' . $cnt .'"';
        $leafDel .= ' value="delete">' . "\n";
        $leafDel .= '<input type="hidden"   name="in_leaf_pid_' . $cnt . '"';
        $leafDel .= ' value="' . $row['leafpid'] . '">' . "\n";
        $leafImg = '<img src="display.php';
        $leafImg .= '?in_pid=' . $row['leafpid'];
        $leafImg .= '&in_size=' . $CONF['index_size'];
        $leafImg .= '">';
    
        echo "  <tr>\n";
        echo '    <td align="center">' . $rootDel . "</td>\n";
        echo '    <td>' . $row['rootpid'] . "</td>\n";
        echo '    <td>' . $rootImg . "</td>\n";
        echo '    <td align="center">' . $leafDel . "</td>\n";
        echo '    <td>' . $row['leafpid'] . "</td>\n";
        echo '    <td>' . $leafImg . "</td>\n";
        echo "  </tr>\n";
        $cnt++;
    }
    echo "</table>\n";
    echo '<input type="submit" name="in_button_update"';
    echo ' value="Delete Selected">' . "\n";
    echo '<input type="hidden" name="in_picture_cnt"';
    echo ' value="' . $cnt . '">' . "\n";
    echo "</form>\n";
} else {
    if (!empty($in_button_find)) {
        echo "<font color=\"#ff0000\">Nothing found!</font>\n";
    }
}

if (!empty($_SESSION['msg'])>0) {
    echo '<p>'.$_SESSION['msg']."\n";
    $_SESSION['msg'] = '';
}
?>

<?php require('page_bottom.php'); ?>
</body>
</html>
