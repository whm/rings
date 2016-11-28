<?php
// Useful database functions

//-------------------------------------------------------------
// accept a uploaded file and store it

function accept_and_store($fld_name, $in_pid) {

    global $CONF;
    global $DBH;

    $file_id = $fld_name;

    if (
        !isset($_FILES[$file_id]['error']) ||
        is_array($_FILES[$file_id]['error'])
    ) {
        sys_err('Unknown problem uploading file');
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

    if ($in_pid == 0) {
        $pid = get_next('pid');
        $picture_lot = 'upload-' . sql_datetime();
    } else {
        $pid = $in_pid;
        $picture_lot = get_picture_lot($pid);
        if (empty($picture_lot)) {
            sys_err("Picture lot not found for $pid");
            return;
        }
    }

    $tmp_file  = $_FILES[$file_id]['tmp_name'];
    $mime_type = mime_content_type($tmp_file);
    $file_type = validate_mime_type($mime_type);
    if (!$file_type) {
        sys_err("Upload of $mime_type files not allowed");
        return 1;
    }

    $original_file      = $_FILES[$file_id]["name"];
    $content_type       = $_FILES[$file_id]["type"];
    $original_file_size = $_FILES[$file_id]["size"];
    $a_date  = date("Y-m-d H:i:s");
    $z = strrpos ($original_file, ".");
    $tmp = substr ($original_file, 0, $z);

    $the_file_contents = file_get_contents($tmp_file);

    $pic_file = picture_path ($picture_lot, 'raw', $pid, $file_type);
    $bytes_written = file_put_contents($pic_file, $the_file_contents);
    if ($bytes_written == 0) {
        sys_err("Problem writing to $pic_file");
        return;
    }
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
    $sth->bind_param('ii', $raw_size, $pid);
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
    $sth->bind_param('si', $mime_type, $pid);
    $sth->execute();
    if ($sth->errno) {
        sys_err('MySQL exec error: ' . $sth->error);
        sys_err("SQL: $cmd");
    }
    $sth->close();

    unlink ($tmp_file);
    queue_status_set($pid);

    sys_msg("$pid uploaded.");
    sys_msg('<a href="picture_maint.php?in_pid=' . $pid. '" '
    . 'target="_blank">Update Picture Details.</a>');

    return;
}

//-------------------------------------------------------------
// Get the field names for a given table

function get_fld_names ($this_table) {
    global $DBH;

    $sel = "SELECT * FROM $this_table LIMIT 0,1";
    $result = $DBH->query ($sel);
    $names = array();
    if ($result) {
        $fld_cnt = $result->field_count;
        for ($i=0; $i<$fld_cnt; $i++) {
            $fld_info = $result->fetch_field_direct($i);
            $db_fld   = $fld_info->name;
            $names[] = $db_fld;
        }
    }
    return $names;
}

//-------------------------------------------------------------
// get the next id

function get_next ($id) {

    global $DBH;
    global $warn, $em;

    $return_number = 0;

    $sel = "SELECT next_number FROM next_number WHERE id='$id' ";
    $result = $DBH->query ($sel);
    if ($result->errno) {
        $_SESSION['msg'] .= $warn . "MySQL error:" . $result->error . $em;
        $_SESSION['msg'] .= "Problem SQL:$sel<br>\n";
    } else {
        if ($result) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $return_number = $row["next_number"];
        }
    }
    if ($return_number > 0) {
        $nxt = $return_number + 1;
        $cmd = "UPDATE next_number SET next_number=$nxt WHERE id='$id' ";
        $result = $DBH->query($cmd);
        if ($DBH->errno) {
            $_SESSION['msg'] .= $warn . "MySQL error:" . $result->error . $em;
            $_SESSION['msg'] .= "Problem SQL:$cmd<br>\n";
        }
    } else {
        $nxt = 1;
        $cmd = "INSERT INTO  next_number (id,next_number) ";
        $cmd .= "VALUES ('$id',$nxt) ";
        $result = $DBH->query($cmd);
        if ($result->errno) {
            $_SESSION['msg'] .= $warn . "MySQL error:" . $result->error . $em;
            $_SESSION['msg'] .= "Problem SQL:$cmd<br>\n";
        } else {
            if ($result) {
                $return_number = $nxt;
            }
        }
    }

    return $return_number;

}

//-------------------------------------------------------------
// Get the mime type and file extension for a picture

function get_picture_type ($pid, $size_id) {
    global $DBH;
    global $CONF;

    $sel = 'SELECT picture_table FROM picture_sizes WHERE size_id = ? ';
    if ($CONF['debug']) {
        sys_msg("DEBUG: $sel");
    }
    if (!$stmt = $DBH->prepare($sel)) {
        $m = 'Prepare failed: (' . $DBH->errno . ') ' . $DBH->error;
        sys_err($m);
        sys_msg(LOG_INFO, "Problem statement: $sel");
        return;
    }
    $stmt->bind_param('s', $size_id);
    $stmt->execute();
    $stmt->bind_result($z);
    if ($stmt->fetch()) {
        $picture_table = $z;
    }
    $stmt->close();
    if (!empty($picture_table)) {
        $sel = "SELECT ${picture_table}.mime_type, picture_types.file_type ";
        $sel .= "FROM $picture_table ";
        $sel .= 'JOIN picture_types ';
        $sel .= "ON (picture_types.mime_type = ${picture_table}.mime_type) ";
        $sel .= 'WHERE pid = ? ';
        if ($CONF['debug']) {
            sys_msg("DEBUG: $sel");
        }
        if (!$stmt = $DBH->prepare($sel)) {
            $m = 'Prepare failed: (' . $DBH->errno . ') ' . $DBH->error;
            sys_err($m);
            sys_err("Problem statement: $sel");
            return;
        }
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $stmt->bind_result($p1, $p2);
        if ($stmt->fetch()) {
            $mime_type = $p1;
            $file_type = $p2;
        }
        $stmt->close();
    }
    if (empty($mime_type)) {
        $mime_type = 'application/octet-stream';
        $file_type = '';
    }
    return array($mime_type, $file_type);
}

//-------------------------------------------------------------
// Validate the size and return raw if not found

function validate_size ($id) {

    global $DBH;
    global $CONF;

    $this_size = '';
    $this_description = '';
    $sel = 'SELECT size_id,description FROM picture_sizes WHERE size_id = ? ';
    if (!$stmt = $DBH->prepare($sel)) {
        sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
    }
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($p1, $p2);
    if ($stmt->fetch()) {
        $this_size = $p1;
        $this_description = $p2;
    }
    $stmt->close();

    return array($this_size, $this_description);
}

//-------------------------------------------------------------
// Validate the file type

function validate_type ($file_type) {

    global $DBH;
    global $CONF;

    $this_file_type = '';
    $this_mime_type = '';
    $sel = 'SELECT file_type, mime_type ';
    $sel .= 'FROM picture_types WHERE file_type = ? ';
    if (!$stmt = $DBH->prepare($sel)) {
        sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
    }
    $stmt->bind_param('s', $file_type);
    $stmt->execute();
    $stmt->bind_result($p1, $p2);
    if ($stmt->fetch()) {
        $this_file_type = $p1;
        $this_mime_type = $p2;
    }
    $stmt->close();

    return array($this_file_type, $this_mime_type);
}

//-------------------------------------------------------------
// Add entry to the picture resize queue

function queue_status_set ($pid) {

    global $DBH;
    global $CONF;

    $sel = 'INSERT INTO picture_resize_queue SET ';
    $sel .= 'pid = ?, ';
    $sel .= "status = 'PENDING', ";
    $sel .= 'date_last_maint = NOW(), ';
    $sel .= 'date_added = NOW() ';
    $sel .= 'ON DUPLICATE KEY UPDATE ';
    $sel .= "status = 'PENDING', ";
    $sel .= 'date_last_maint = NOW() ';
    if (!$stmt = $DBH->prepare($sel)) {
        sys_err("Problem SQL: $sel");
        sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
        return 1;
    }

    $stmt->bind_param('i', $pid);
    if (!$stmt->execute()) {
        sys_err("Problem SQL: $sel");
        sys_err('Execute failed: (' . $DBH->errno . ') ' . $DBH->error);
        return 1;
    }
    $stmt->close();

    return;
}

//-------------------------------------------------------------
// Check the validity of a mime type and return file type if valid

function validate_mime_type ($mime_type) {

    global $DBH;
    global $CONF;

    $sel = 'SELECT file_type FROM picture_types WHERE mime_type = ? ';
    if (!$stmt = $DBH->prepare($sel)) {
        sys_err("Problem SQL: $sel");
        sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
        return;
    }
    $stmt->bind_param('s', $mime_type);
    if (!$stmt->execute()) {
        sys_err("Problem SQL: $sel");
        sys_err('Execute failed: (' . $DBH->errno . ') ' . $DBH->error);
        return;
    }
    $stmt->bind_result($p1);
    if ($stmt->fetch()) {
        $picture_type = $p1;
    }
    $stmt->close();

    return $picture_type;
}

//-------------------------------------------------------------
// Get the picture_lot so we know where to put the file

function get_picture_lot ($pid) {

    global $DBH;
    global $CONF;

    $sel = 'SELECT picture_lot FROM pictures_information WHERE pid = ?';
    if (!$stmt = $DBH->prepare($sel)) {
        sys_err("Problem SQL: $sel");
        sys_err('Prepare failed: (' . $DBH->errno . ') ' . $DBH->error);
        return;
    }
    $stmt->bind_param('i', $pid);
    if (!$stmt->execute()) {
        sys_err("Problem SQL: $sel");
        sys_err('Execute failed: (' . $DBH->errno . ') ' . $DBH->error);
        return;
    }
    $stmt->bind_result($p1);
    if ($stmt->fetch()) {
        $picture_lot = $p1;
    }
    $stmt->close();

    return $picture_lot;
}
?>
