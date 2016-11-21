<?php
// Useful database functions

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
