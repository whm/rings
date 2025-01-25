<?php
// ----------------------------------------------------------
// File: picture_dup_check_action.php
// Author: Bill MacAllister
// Date: August 2025

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('inc_maint_check.php');

// Form or URL inputs
$in_button_update  = get_request('in_button_update');
$in_picture_cnt    = get_request('in_picture_cnt', 0);

// ----------------------------------------------------
// Main Routine

// ---------------------------------------------------------
// Processing for updates only

if ($in_button_update == 'Delete Selected') {
    $update_cnt = 0;
    for ($i=0; $i<$in_picture_cnt; $i++) {
        $root_del = get_request("in_root_$i");
        $root_pid = get_request("in_root_pid_$i");
        $leaf_del = get_request("in_leaf_$i");
        $leaf_pid = get_request("in_leaf_pid_$i");
        if ($root_del == 'delete' && $leaf_del == 'delete') {
            sys_err("Both $root_pid and $leaf_pid selected.  Skipping...");
        } else {
            if ($root_del == 'delete')  {
                db_delete_files($root_pid);
                db_delete_picture($root_pid);
                sys_msg("Root $root_pid deleted from Rings data base");
                $update_cnt++;
            }
            if ($leaf_del == 'delete')  {
                db_delete_files($leaf_pid);
                db_delete_picture($leaf_pid);
                sys_msg("Leaf $leaf_pid deleted from Rings database");
                $update_cnt++;
            }
        }
    }
    if ($update_cnt == 0) {
        sys_msg("No pictures selected to delete");
    }

} else {

    sys_msg("Ooops, this should never happen!");

}

header ("REFRESH: 0; URL=picture_dup_check.php");

?>
