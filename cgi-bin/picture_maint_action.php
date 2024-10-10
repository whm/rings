<?php
// ----------------------------------------------------------
// File: picture_maint_action.php
// Author: Bill MacAllister
// Date: 31-Dec-2001

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_fld                 = get_request('in_fld');
$in_val                 = get_request('in_val');
$in_picture_date        = get_request('in_picture_date');
$in_pid                 = get_request('in_pid');
$in_type                = get_request('in_type');
$in_newuids             = get_request('in_newuids');
$in_button_update       = get_request('in_button_update');
$in_button_rotate_left  = get_request('in_button_rotate_left');
$in_button_rotate_right = get_request('in_button_rotate_right');
$in_button_del          = get_request('in_button_del');
$in_clear_cache         = get_request('in_clear_cache');

//-------------------------------------------------------------
// quote a value for storage
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function sql_quote ($a_val, $in_type) {

    $ret = trim ($a_val);
    if ( $in_type != "n" ) {
        $ret = "'" . str_replace("'", "\'", $ret) . "'";
    }
    return $ret;

}

// ----------------------------------------------------
// Main Routine

// No spaces allowed in the identifier
$in_pid = preg_replace ('/\s+/', '', $in_pid);

// how to get back
$next_url    = "picture_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete
if (!empty($in_button_update)) {

    // Try and get the old user record
    $sel = "SELECT * FROM pictures_information WHERE pid=$in_pid ";
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_picture = $row['pid'];
        $fld_cnt      = $result->field_count;
    }
    $update_flag = 1;
    $add_flag = 0;
    if (empty($this_picture)) {
        // no old record, they must want a new one for this id
        $add_flag = 1;
        $update_flag = 0;
    }
}

if ( $update_flag ) {

    // -- Change an Existing record ----------------------

    $comma = '';
    $cmd = '';
    $update_cnt = 0;

    $update_list[] = 'picture_date';
    $update_list[] = 'description';
    $update_list[] = 'key_words';
    $update_list[] = 'taken_by';
    $update_list[] = 'grade';
    $update_list[] = 'public';

    $_SESSION['maint_last_datetime'] = $in_picture_date;

    $fld_names = get_fld_names('pictures_information');
    foreach ($fld_names as $db_fld) {
        $fld_update_flag = 0;
        foreach ($update_list as $this_name) {
            if ($this_name == $db_fld) {
                $fld_update_flag = 1;
            }
        }
        if ($fld_update_flag == 0) {continue;}
        $in_val = trim(stripslashes(get_request("in_$db_fld")));

        // remember the last entered value
        $sess_fld = "session_$db_fld";
        $_SESSION["$sess_fld"] = $in_val;

        if (trim($in_val) != trim($row[$db_fld])) {
            $cmd .= "$comma $db_fld=" . sql_quote($in_val,'s');
            $comma = ', ';
            $update_cnt++;
            sys_msg("$db_fld updated.");
        }
    }

    if ($update_cnt>0) {
        // Make the changes
        $sql_cmd = "UPDATE pictures_information SET $cmd";
        $sql_cmd .= ', date_last_maint = NOW() ';
        $sql_cmd .= "WHERE pid = $in_pid ";
        $result = $DBH->query($sql_cmd);
        if (!$result) {
            sys_err('Update failed. See syslog for more information');
            syslog(LOG_ERR, 'Problem SQL: ' . $sql_cmd);
            syslog(LOG_ERR, 'SQL error: ' . $DBH->error);
        }
    }
    $next_pid = $in_pid;

    // delete picture details
    for ($i=0; $i<get_request('in_del_cnt', 0); $i++) {
        $a_flag = get_request("in_del_$i");
        if (!empty($a_flag)) {
            $a_uid = get_request("in_del_uid_$i");
            $cmd = "DELETE FROM picture_rings ";
            $cmd .= "WHERE uid = '$a_uid' ";
            $cmd .= "AND pid = $in_pid ";
            $result = $DBH->query($cmd);
            if ($result) {
                $update_cnt++;
                sys_msg("Deleted $a_uid from picture.");
                if (!empty($_SESSION['s_uid_weight'][$a_uid])) {
                    $_SESSION['s_uid_weight'][$a_uid]--;
                    if ($_SESSION['s_uid_weight'][$a_uid] < 0) {
                        $_SESSION['s_uid_weight'][$a_uid] = 0;
                    }
                }
            } else {
                sys_err("Problem deleting picture details.");
                sys_err("Problem SQL: $sql_cmd");
            }
        }
    }

    // Clear the name cache if requested
    if (!empty($in_clear_cache)) {
        foreach ($_SESSION['s_uid_weight'] as $u => $v) {
            unset($_SESSION['s_uid_weight'][$u]);
        }
    }

    // add picture details
    for ($i=0; $i<get_request('in_add_cnt'); $i++) {
        $a_uid = '';
        if (!empty($in_newuids[$i])) {
            $a_uid = $in_newuids[$i];
        }
        if (strlen($a_uid) > 0) {
            $cmd = 'INSERT INTO picture_rings SET ';
            $cmd .= 'uid = ?, ';
            $cmd .= 'pid = ?, ';
            $cmd .= 'date_last_maint = NOW(), ';
            $cmd .= 'date_added = NOW() ';
            if (!$sth = $DBH->prepare($cmd)) {
                $m = 'Prepare failed: ' . $DBH->error
                    . '(' . $DBH->errno . ') ' ;
                $m .= "Problem statement: $cmd";
                sys_err($m);
            }
            $sth->bind_param('si', $a_uid, $in_pid);
            if (!$sth->execute()) {
                $m = 'Execute failed: ' . $DBH->error
                    . '(' . $DBH->errno . ') ' ;
                $m .= "Problem statement: $cmd";
                sys_err($m);
            }
            $sth->close();
            $update_cnt++;
            sys_msg("$a_uid added.");
            if (!empty($_SESSION['s_uid_weight'][$a_uid])) {
                $_SESSION['s_uid_weight'][$a_uid]++;
                if ($_SESSION['s_uid_weight'][$a_uid] > 32767) {
                    $_SESSION['s_uid_weight'][$a_uid] = 32767;
                }
            } else {
                $_SESSION['s_uid_weight'][$a_uid] = 1;
            }
        }
    }
    if ($update_cnt < 1) {
        msg_err('No changes found');
    }

} elseif ( !empty($in_button_del) ) {

    // -- Delete a record -------------------------------

    db_delete_picture($in_pid);
    $next_uid = 'CLEARFORM';

} elseif ( !empty($in_button_rotate_right) || !empty($in_button_rotate_left) ) {

    $sh_cmd = "/usr/bin/ring-rotate $in_pid";
    if (!empty($in_button_rotate_right)) {
        $sh_cmd .= " --right";
    } else {
        $sh_cmd .= " --left";
    }
    $ret = array();
    $z = exec($sh_cmd, $ret, $ret_status);
    if ($ret_status) {
        sys_err("Command:$sh_cmd");
        foreach ($ret as $v) {
            sys_err($v);
        }
        sys_err("SCRIPT ERROR");
    }

    queue_action_set($in_pid, 'SIZE');

    $next_pid = $in_pid;

} else {

    echo "Ooops, this should never happen!<br/>\n";

}

header ("$next_header?in_pid=$next_pid");
?>
<html>
<head>
<title>Picture Mainteance Action</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>
</head>
<body>
<a href="picture_maint.php?in_pid=<?php echo $next_pid;?>">
  Return to Picture Maintenance</a>
</body>
</html>
