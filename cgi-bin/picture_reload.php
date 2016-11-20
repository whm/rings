<?php
// ----------------------------------------------------------
// File: picture_load.php
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_pid         = get_request('in_pid');
$in_setdate     = get_request('in_setdate');
$in_button_find = get_request('in_button_find');
$upload         = get_request('upload');

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

$ok   = '<font color="green">';
$warn = '<font color="red">';
$em   = "</font><br>\n";

// -- main routine

openlog($_SERVER['PHP_SELF'], LOG_PID | LOG_PERROR, LOG_LOCAL0);
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

        // -- Do the work

        $file_id = 'in_filename';
        $tmp_file = $_FILES[$file_id]['tmp_name'];
        if ($_FILES[$file_id]['error'] !=0
            && $_FILES[$file_id]['error'] !=4) {
            echo "Error uploading ".$_FILES[$file_id]["name"]."<br>\n";
        }
        if (!isset($tmp_file) || strlen($tmp_file) == 0) {
            $_SESSION['msg'] .= "<br>$warn No file uploaded.</font>\n";
        } else {
            echo "<h1>Upload results</h1>\n";
            echo "<p>\n";
            $original_file      = $_FILES[$fileID]["name"];
            $content_type       = $_FILES[$fileID]["type"];
            $original_file_size = $_FILES[$fileID]["size"];
            $a_date  = date("Y-m-d H:i:s");
            $z = strrpos ($original_file, ".");
            $tmp = substr ($original_file, 0, $z);

            $the_file_contents = fread(fopen($tmp_file,'r'), 5000000);

            $cmd = "UPDATE pictures_information SET ";
            $cmd .= "raw_picture_size = " . strlen($the_file_contents) . ", ";
            $cmd .= "date_last_maint = NOW() ";
            $cmd .= "WHERE pid = $in_pid ";
            $sth = $DBH->query($cmd);
            if ($sth->errno) {
                $_SESSION['msg'] .= $warn."MySQL prepare error:"
                    . $sth->error . $em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }

            $cmd = "INSERT INTO pictures_raw SET ";
            $cmd .= "pid = ?, ";
            $cmd .= "mime_type = ?, ";
            $cmd .= "picture = ?, ";
            $cmd .= "date_last_maint = NOW(), ";
            $cmd .= "date_added = NOW() ";
            $cmd .= "ON DUPLICATE KEY UPDATE ";
            $cmd .= "mime_type = ?, ";
            $cmd .= "picture = ?, ";
            $cmd .= "date_last_maint = NOW() ";
            $sth = $DBH->prepare($cmd);
            $sth->bind_param('i', $in_pid);
            $sth->bind_param('s', $content_type);
            $sth->bind_param('b', $the_file_contents);
            $sth->bind_param('s', $content_type);
            $sth->bind_param('b', $the_file_contents);
            $sth->execute();
            if ($sth->errno) {
                $_SESSION['msg'] .= $warn."MySQL prepare error:"
                    . $sth->error . $em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }

            echo "$in_pid uploaded. ";
            echo "<a href=\"picture_maint.php?in_pid=$in_pid\" "
                . "target=\"_blank\">Update Picture Details.</a>";
            echo "<br>\n";

            unlink ($tmp_file);

            $sh_cmd = "/usr/bin/perl /usr/bin/ring-resize.pl";
            $sh_cmd .= " --start=$in_pid";
            $sh_cmd .= " --end=$in_pid";
            $sh_cmd .= " --host=$mysql_host";
            $sh_cmd .= " --user=$mysql_user";
            $sh_cmd .= " --db=$mysql_db";
            $sh_cmd .= " --update";
            if (strlen($in_setdate)>0) {$sh_cmd .= " --dateupdate";}
            syslog(LOG_INFO, "Executing:$sh_cmd");
            $sh_cmd .= " --pass=$mysql_pass";
            system($sh_cmd);
        }
    }
    check_action_queue($in_pid);
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
