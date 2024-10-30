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
$in_type            = get_request('in_type');
$in_uid             = get_request('in_uid');
$in_cn              = get_request('in_cn');
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
        $vals = $vals . $c . sql_quote($a_val, $in_type);
    } else {
        $vals = $vals . $c . $a_val;
    }
    return;
}

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

$now = date ('Y-m-d H:i:s');

// Field default
if (strlen($in_cn) == 0) {
    $in_cn = $in_display_name;
}

// No spaces allowed in the identifier
$in_uid = preg_replace ('/\s+/','',$in_uid);

// how to get back
$next_url    = "people_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete

$update_flag = $add_flag = 0;

if ( !empty($in_button_update) ) {

    // Try and get the old user record
    $sel = "SELECT * FROM people_or_places WHERE uid='$in_uid'";
    $result = $DBH->query ($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_user = $row['uid'];
    }

    $update_flag = 1;
    $add_flag = 0;
    if (empty($this_user)) {
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

    $fld_names = get_fld_names('people_or_places');
    foreach ($fld_names as $db_fld) {
        if ($db_fld == "date_of_birth")   {continue;}
        if ($db_fld == "date_added")      {continue;}
        if ($db_fld == "date_last_maint") {
            $in_val = $now;
        } else {
            $in_val = trim(get_request("in_$db_fld"));
            $in_val = stripslashes($in_val);
        }       
        if (trim($in_val) != trim($row[$db_fld])) {
            $in_val = str_replace ("'", '\'', $in_val);
            $cmd .= "$comma $db_fld=".sql_quote($in_val,'s')." ";
            $comma = ',';
            $update_cnt++;
            sys_msg("$db_fld updated.");
        }
    }

    if ($update_cnt>0) {
        // Make the changes
        $sql_cmd = "UPDATE people_or_places SET $cmd ";
        $sql_cmd .= ', date_last_maint = NOW() ';
        $sql_cmd .= "WHERE uid = '$this_user'";
        $result = $DBH->query($sql_cmd);
        if ($result) {
            sys_msg("'$in_uid' update complete.");
        } else {
            sys_err("Problem updating $in_uid");
            sys_err("Problem SQL: $sql_cmd");
            sys_err('(' . $DBH->errno . ') ' . $DBH->error);
        }
    }
    $next_uid = $in_uid;

} elseif ( $add_flag || (! empty($in_button_add)) ) {

    // -- Add a new record -------------------------------

    $sel = "SELECT uid FROM people_or_places WHERE uid='$in_uid'";
    $result = $DBH->query($sel);
    $this_user = '';
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_user = $row['uid'];
    }

    if ( strlen($this_user) > 0) {
        sys_err("Person already exists!<br>New entry NOT Added.");
    } else {
        $comma = '';
        $cmd = '';
        $update_cnt = 0;

        $update_list[] = 'uid';
        $update_list[] = 'auth_uid';
        $update_list[] = 'cn';
        $update_list[] = 'display_name';
        $update_list[] = 'description';
        $update_list[] = 'visibility';
    
        $fld_names = get_fld_names('people_or_places');
        foreach ($fld_names as $db_fld) {
            $fld_add_flag = 0;
            foreach ($update_list as $this_name) {
                if ($this_name == $db_fld) {
                    $fld_add_flag = 1;
                }
            }
            if ($fld_add_flag == 0) {continue;}
            $in_val = trim(stripslashes(get_request("in_$db_fld")));

            if (trim($in_val) != trim($row[$db_fld])) {
                $cmd .= "$comma $db_fld=" . sql_quote($in_val,'s');
                $comma = ', ';
                $update_cnt++;
                sys_msg("$db_fld added.");
            }
        }
    }
    $sql_cmd = "INSERT INTO people_or_places SET $cmd";
    $sql_cmd .= ', date_last_maint = NOW() ';
    $sql_cmd .= ', date_added = NOW() ';
    $result = $DBH->query($sql_cmd);
    if (!$result) {
        sys_err('Add failed. See syslog for more information');
        syslog(LOG_ERR, 'Problem SQL: ' . $sql_cmd);
        syslog(LOG_ERR, 'SQL error: ' . $DBH->error);
    }
    $next_uid = $in_uid;

} elseif ( !empty($in_button_delete) ) {

    // -- Delete a record -------------------------------

    $sql_cmd = "DELETE FROM people_or_places WHERE uid='$in_uid'";
    $result = $DBH->query($sql_cmd);
    if ($result) {
        sys_msg("Person '$in_uid' dropped from people.");
    } else {
        sys_err("Problem deleting $in_uid");
        sys_err("Problem SQL: $sql_cmd");
        sys_err('(' . $DBH->errno . ') ' . $DBH->error);
    }
    $next_uid = 'CLEARFORM';

} else {

    echo "Ooops, this should never happen!<br>\n";

}

header ("$next_header?in_uid=$next_uid");

?>
