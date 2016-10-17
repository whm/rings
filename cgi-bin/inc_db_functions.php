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

    $sel = 'SELECT size_id,description FROM picture_sizes WHERE size_id=? ';
    if (!$stmt = $DBH->prepare($sel)) {
        $m = 'Prepare failed: (' . $DBH->errno . ') ' . $DBH->error;
        syslog(LOG_ERR, $m);
        syslog(LOG_INFO, "Problem statement: $sel");
    }
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($p1, $p2);
    if ($stmt->fetch()) {
        $this_size = $p1;
    }
    $stmt->close();
    if (empty($this_size)) {
        $this_size = $CONF['raw_id'];
    }

    return $this_size;
}
?>
