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

##############################################################################
# Subroutines
##############################################################################

// ------------------------------------------------------------------------
// Accept the uploaded file and store it

function store_file($in_pid) {

    global $CONF;
    global $DBH;
    
    $file_id = 'in_filename';

    if (
        !isset($_FILES[$file_id]['error']) ||
        is_array($_FILES[$file_id]['error'])
    ) {
        $msg = 'Unknown problem uploading file';
        $_SESSION['msg'] .= "${warn}ERROR: ${msg}${em}";
        syslog(LOG_ERROR, $msg);
        return 1;
    }

    if ($_FILES[$file_id]['error'] == UPLOAD_ERR_OK) {
        sys_msg('file uploaded');
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_INI_SIZE) {
        sys_err('File exceeds the upload_max_filesize in php.ini');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_FORM_SIZE) {
        sys_err('File exceeds the MAX_FILE_SIZE directive');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_PARTIAL) {
        sys_err('File partially uploaded ... update abandonded');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_ERR_NO_FILE) {
        sys_err('No file was uploaded');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_NO_TMP_DIR) {
        sys_err('Missing temporary folder');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_CANT_WRITE) {
        sys_err('Failed to write file to disk');
        return 1;
    } elseif ($_FILES[$file_id]['error'] == UPLOAD_ERR_EXTENSION) {
        sys_err('A PHP extension stopped the file upload');
        return 1;
    } else {
        sys_err('Unknown upload error ' . $_FILES[$file_id]['error']);
        return 1;
    }

    $picture_lot = get_picture_lot($in_pid);
    if (empty($picture_lot)) {
        sys_err("Picture lot not found for $in_pid");
        return;
    }
    
    $tmp_file  = $_FILES[$file_id]['tmp_name'];
    $mime_type = mime_content_type($tmp_file);
    $file_type = validate_mime_type($mime_type);
    if (!$file_type) {
        sys_err("Upload of $mime_type files not allowed");
        return 1;
    }
    
    $original_file      = $_FILES[$fileID]["name"];
    $content_type       = $_FILES[$fileID]["type"];
    $original_file_size = $_FILES[$fileID]["size"];
    $a_date  = date("Y-m-d H:i:s");
    $z = strrpos ($original_file, ".");
    $tmp = substr ($original_file, 0, $z);
    
    $the_file_contents = file_get_contents($tmp_file);

    $pic_file
      = $CONF['picture_root'] . "/${picture_lot}/raw/${in_pid}.${file_type}";
    $bytes_written = file_put_contents($pic_file, $the_file_contents);
    sys_msg("$bytes_written bytes written to $pic_file");

    $raw_size = strlen($the_file_contents);
    $cmd = 'UPDATE pictures_information SET ';
    $cmd .= 'raw_picture_size = ?, ';
    $cmd .= 'date_last_maint = NOW() ';
    $cmd .= 'WHERE pid = ? ';
    $sth = $DBH->prepare($cmd);
    if ($sth->errno) {
        sys_err('MySQL prepare error: ' . $sth->error);
        sys_err("SQL: $cmd");
        return;
    }
    $sth->bind_param('ii', $raw_size, $in_pid);
    $sth->execute();
    if ($sth->errno) {
        sys_err('MySQL exec error: ' . $sth->error);
        sys_err("SQL: $cmd");
    }
    $sth->close();
    
    $cmd = 'UPDATE pictures_raw SET ';
    $cmd .= 'mime_type = ?, ';
    $cmd .= 'date_last_maint = NOW() ';
    $cmd .= 'WHERE pid = ? ';
    $sth = $DBH->prepare($cmd);
    if ($sth->errno) {
        sys_err('MySQL prepare error: ' . $sth->error);
        sys_err("SQL: $cmd");
        return;
    }
    $sth->bind_param('si', $mime_type, $in_pid);
    $sth->execute();
    if ($sth->errno) {
        sys_err('MySQL exec error: ' . $sth->error);
        sys_err("SQL: $cmd");
    }
    $sth->close();

    unlink ($tmp_file);
    queue_status_set($in_pid);

    echo "$in_pid uploaded. ";
    echo "<a href=\"picture_maint.php?in_pid=$in_pid\" "
        . "target=\"_blank\">Update Picture Details.</a>";
    echo "<br>\n";

    return;
}


##############################################################################
# Main Routine
##############################################################################
?>

<html>
<head>
<title>Re-Load a Picture</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#eeeeff">

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

<?php if ($in_pid > 0) { ?>
<img src="/rings/display.php?in_pid=<?php echo $in_pid;?>&in_size=large"><br>
<?php } ?>

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
        if (store_file($in_pid)) {
            echo "<h2>Upload Failure</h2>\n";
            echo "<p>\n";
        } else {
            echo "<h2>File uploaded</h2>\n";
            echo "<p>\n";
        }
    }
}

if (!empty($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
