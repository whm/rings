<?php
// ----------------------------------------------------------
// File: picture_load.php
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_pid         = get_request('in_pid');
$in_setdate     = get_request('in_setdate');
$in_button_find = get_request('in_button_find');
$upload         = get_request('upload');

##############################################################################
# Main Routine
##############################################################################
?>

<html>
<head>
<title>Re-Load a Picture</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>
</head>

<?php
$thisTitle = 'Re-load a Picture into the Rings';
require ('page_top.php');

?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<table border="0">
<tr><td>Picture ID:</td>
    <td>
    <input type="text" name="in_pid" value="<?php echo $in_pid;?>" size="8">
    </td>
</tr>
<tr><td colspan="2" align="center">
    <input type="submit" name="in_button_find" value="Find Picture">
    </td>
</tr>
</table>

<?php
if ($in_pid > 0) {
    # Defeat the local picture cache by adding a random number to
    # the image tag.
    $i = rand(0, 10000);
    $this_img
        = '<img src="display.php?in_pid=' . $in_pid
        . '&rand=' . $i
        . '">';
    echo $this_img;
}
?>

</form>

<?php

if ($in_pid > 0) {
    if (empty($upload)) {
        // -- Display the upload form
        echo "<form enctype=\"multipart/form-data\" method=\"post\" ";
        echo 'action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
        echo '<input type="hidden" name="in_pid" value="'.$in_pid.'">'."\n";
        echo "<table border=\"1\">\n";
        echo "<tr>\n";
        echo " <th><font face=\"Arial, Helvetica, sans-serif\">\n";
        echo "     Picture File Name</font></th>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<tr>\n";
        echo " <td>\n";
        echo "  <font size=\"-1\" face=\"Arial, Helvetica, sans-serif\">\n";
        echo "  <input type=\"file\" size=\"60\" name=\"in_filename\">\n";
        echo "  </font>\n";
        echo " </td>\n";
        echo "</tr>\n";
        echo "</table>\n";
        echo '<input type="checkbox" name="in_setdate" value="Y">'
            . 'Set Date from Picture Information<br>'."\n";
        echo "<input type=\"submit\" name=\"upload\" value=\"Upload\">\n";
        echo "</form>\n";
    } else {
        // Save the file and request regeneration
        if (accept_and_store('in_filename', $in_pid)) {
            echo "<h2>Upload Failure</h2>\n";
            echo "<p>\n";
        } else {
            echo "<h2>File uploaded</h2>\n";
            echo "<p>\n";
        }
    }
    check_action_queue($in_pid);
}

sys_display_msg();

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
