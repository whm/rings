<?php
// ----------------------------------------------------------
// File: people_maint_action.php
// Author: Bill MacAllister
// Date: 31-Dec-2001

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_group_id        = get_request('in_group_id');
$in_type            = get_request('in_type');
$in_group_uid       = get_request('in_group_uid');
$in_deluids         = get_request('in_deluids');
$in_uid             = get_request('in_uid');
$in_newuids         = get_request('in_newuids');
$in_button_add      = get_request('in_button_add');
$in_button_update   = get_request('in_button_update');
$in_button_delete   = get_request('in_button_delete');

//-------------------------------------------------------------
// construct flds and vals for an insert
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function mkin ($a_fld, $a_val, $in_type) {

  global $flds, $vals;

  $a_val = trim ($a_val);
  $c = "";
  if (strlen($flds) > 0) {$c = ",";}
  $flds = $flds . $c . $a_fld;
  if ( $in_type != "n" ) {
    $a_val = str_replace ("'", "\\'", $a_val);
    $vals = $vals . $c . "'$a_val'";
  } else {
    $vals = $vals . $c . $a_val;
  }
  return;
}

// ----------------------------------------------------
// Main Routine

$now = date ('Y-m-d H:i:s');

// No spaces allowed in the identifier
$in_uid = preg_replace ('/\s+/', '', $in_uid);

// how to get back
$next_url      = "group_maint.php";
$next_header   = "REFRESH: 0; URL=$next_url";
$next_group_id = $in_group_id;

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete

$update_flag = $add_flag = 0;

if ( !empty($in_button_update) ) {

    // Try and get the old user record
    $sel = "SELECT * FROM groups WHERE group_id='$in_group_id'";
    $result = $DBH->query ($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_group = $row['group_id'];
        $fld_cnt = $result->field_count;
    }

    $update_flag = 1;
    $add_flag    = 0;
    if (empty($this_group)) {
        // no old record, they must want a new one for this id
        $add_flag    = 1;
        $update_flag = 0;
    }
}

if ( $update_flag ) {

    // -- Change an Existing record ----------------------

    $comma      = '';
    $cmd        = '';
    $update_cnt = 0;

    for ($i=0; $i<$fld_cnt; $i++) {
        $fld_info = $result->fetch_field_direct($i);
        $db_fld   = $fld_info->name;
        if ($db_fld == "date_added") {
            continue;
        }
        if ($db_fld == "date_last_maint") {
            $in_val = $now;
        } else {
            $in_val = trim(get_request("in_$db_fld"));
        }
        if (trim($in_val) != trim($row[$db_fld])) {
            $in_val = str_replace ("'", "\\'", $in_val);
            $cmd .= "$comma $db_fld='$in_val' ";
            $comma = ',';
            $update_cnt++;
            msg_okay("$db_fld updated.");
        }
    }

    if ($update_cnt>1) {
        // Make the changes
        $sql_cmd = "UPDATE groups SET $cmd ";
        $sql_cmd .= "WHERE group_id = '$this_group'";
        $result = $DBH->query($sql_cmd);
    }

    // -- add people to group

    if (is_array($in_newuids)) {
        foreach ($in_newuids as $i => $a_uid) {
            $flds = '';
            $vals = '';
            mkin ('group_id',        $in_group_id, 's');
            mkin ('uid',             $a_uid,       's');
            mkin ('date_last_maint', $now,         's');
            mkin ('date_added',      $now,         's');
            $sql_cmd = "INSERT INTO picture_groups ($flds) VALUES ($vals)";
            $result = $DBH->query($sql_cmd);
            msg_okay("'$a_uid' added");
        }
    }

    // -- delete people from group

    if (is_array($in_deluids)) {
        foreach ($in_deluids as $i => $a_uid) {
            $sql_cmd = "DELETE FROM picture_groups ";
            $sql_cmd .= "WHERE group_id='$in_group_id' ";
            $sql_cmd .= "AND uid='$a_uid' ";
            $result = $DBH->query($sql_cmd);
            msg_okay("'$a_uid' removed");
        }
    }

} elseif ( $add_flag || (!empty($in_button_add)) ) {

    // -- Add a new record -------------------------------

    $sel = "SELECT group_id FROM groups WHERE group_id='$in_group_uid'";
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_group = $row['group_id'];
    }

    if ( strlen($this_group) > 0) {
        msg_err("Group already exists!");
        msg_err("New entry NOT Added.");
    } else {

        // -- create the group
        $flds = '';
        $vals = '';
        $fld_names = get_fld_names('groups');
        foreach ($fld_names as $db_fld) {
            if ($db_fld == "date_last_maint" || $db_fld == 'date_last_maint') {
                $in_val = $now;
            } else {
                $in_val = trim(get_request("in_$db_fld"));
            }
            mkin ($db_fld, $in_val, 's');
        }
        $sql_cmd = "INSERT INTO groups ($flds) VALUES ($vals)";
        $result = $DBH->query($sql_cmd);
        msg_okay("Group '$in_group_id' added");

        // -- add people to group

        if (is_array($in_newuids)) {
            foreach ($in_newuids as $i => $a_uid) {
                $flds = '';
                $vals = '';
                mkin ('group_id',        $in_group_id, 's');
                mkin ('uid',             $a_uid,       's');
                mkin ('date_last_maint', $now,         's');
                mkin ('date_added',      $now,         's');
                $sql_cmd = "INSERT INTO picture_groups ($flds) VALUES ($vals)";
                $result = $DBH->query($sql_cmd);
                msg_okay("'$a_uid' added");
            }
        }
    }
    $next_uid = $in_uid;

} elseif ( !empty($in_button_delete) ) {

    // -- Delete a record -------------------------------

    $sql_cmd = "DELETE FROM groups WHERE group_id='$in_group_id'";
    $result = $DBH->query($sql_cmd);
    if ($result) {
        sys_msg("Group '$in_group_id' dropped from people.");
    } else {
        msg_err("Problem deleting $in_group_id");
        msg_err("Problem SQL: $sql_cmd");
    }
    $sql_cmd = "DELETE FROM picture_groups WHERE group_id='$in_group_id'";
    $result = $DBH->query($sql_cmd);
    if ($result) {
        sys_msg("Picture references dropped from people.");
    } else {
        msg_err("Problem deleting $in_group_id");
        msg_err("Problem SQL: $sql_cmd");
    }
    $next_group_id = 'CLEARFORM';

} else {

    echo "Ooops, this should never happen!<br>\n";

}

header ("$next_header?in_group_id=$next_group_id");

?>
