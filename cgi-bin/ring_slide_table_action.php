<?php
// ----------------------------------------------------------
// File: ring_slide_table_actions.php
// Author: Bill MacAllister
// Date: 21-May-2017

require('inc_ring_init.php');

// Form or URL inputs
$in_button_update = get_request('in_button_update');
$in_picture_cnt   = get_request('in_picture_cnt', 0);
$in_start         = get_request('in_start', 0);
$in_uid           = get_request('in_uid');

// ----------------------------------------------------
// Main Routine

// ---------------------------------------------------------
// Processing for updates only

$next_url = 'index.php';

if ( $in_button_update == 'Update' ) {

    for ($i=0; $i<$in_picture_cnt; $i++) {

        $cmd = 'date_last_maint = NOW()';
        $update_cnt = 0;

        $up_pid    = get_request("in_pid_$i");
        $up_od     = get_request("in_od_$i");
        $up_nd     = get_request("in_date_$i");
        $up_delete = get_request("in_delete_$i");
        if ($up_delete == 'delete') {
            db_delete_picture($up_pid);
        } elseif ($up_od != $up_nd) {
            // Update the picture date
            $sql_cmd = 'UPDATE pictures_information SET ';
            $sql_cmd .= "picture_date = ?, ";
            $sql_cmd .= 'date_last_maint = NOW() ';
            $sql_cmd .= "WHERE pid = ? ";
            if (!$stmt = $DBH->prepare($sql_cmd)) {
                sys_err("Problem preparing: $sql_cmd");
                sys_err('(' . $DBH->errno . ') ' . $DBH->error);
                break;
            }
            if (!$stmt->bind_param("si", $up_nd, $up_pid)) {
                sys_err("Problem binding parameter: $up_pid");
                sys_err('(' . $DBH->errno . ') ' . $DBH->error);
                break;
            }
            if (!$stmt->execute()) {
                sys_err("Problem executing: $sql_cmd");
                sys_err('(' . $DBH->errno . ') ' . $DBH->error);
                break;
            }
            sys_msg("Updated $up_pid to " . htmlentities($up_nd));
        }
    }
    $next_url = 'ring_slide_table.php'
        . '?in_uid=' . $in_uid
        . '&in_start=' . $in_start;
} else {
    sys_err("Invalid request to ring_slide_table_action");
}

header ("REFRESH: 0; URL=$next_url");
?>
