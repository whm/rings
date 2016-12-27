<?php
// ----------------------------------------------------------
// File: picture_sort_action.php
// Author: Bill MacAllister
// Date: October 2002

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_button_update  = get_request('in_button_update');
$in_up_picture_cnt = get_request('up_picture_cnt', 0);
// ----------------------------------------------------
// Main Routine

$now = date ('Y-m-d H:i:s');
$up_date_last_maint = $now;

// set update message area
$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for updates only

if ( strlen($btn_update)>0 ) {

    $flds['description']  = 's';
    $flds['grade']        = 's';
    $flds['picture_date'] = 's';

    for ($i=0; $i<$in_up_picture_cnt; $i++) {

        $cmd = 'date_last_maint = NOW()';
        $update_cnt = 0;

        $up_pid = get_request("up_pid_$i");

        // Try and get the old user record
        $sel = "SELECT * ";
        $sel .= "FROM pictures_information WHERE pid=$up_pid ";
        $result = $DBH->query($sel);
        if ($result) {
            $row = $dbh->fetch_array(MYSQLI_ASSOC);
            foreach ($flds as $fld => $type) {
                $up_val = trim(get_request("up_${fld}_${i}"));
                $db_val = trim($row[$fld]);
                if ("$up_val" != "$db_val") {
                    $cmd .= ", $fld='$up_val' ";
                    $update_cnt++;
                    sys_msg("$up_pid/$fld $db_val -> $up_val");
                }
            }
        }

        if ($update_cnt>0) {
            // Update the meta data
            $sql_cmd = "UPDATE pictures_information SET $cmd ";
            $sql_cmd .= "WHERE pid = $up_pid ";
            $result = $DBH->query($sql_cmd);
            if (!$result) {
                sys_err("ERROR:" . $result->error);
                sys_err("Problem SQL:$sql_cmd");
            }
        }

        // check for a rotation request
        $rotation = get_request("up_rotate_${i}");
        $update_cnt = 0;
        if ($rotation == 'LEFT' || $rotation == 'RIGHT') {
            $update_cnt++;
            // request the rotation
            $sh_cmd = "/usr/bin/ring-rotate $up_pid ";
            if ($rotation == 'RIGHT') {
                $sh_cmd .= " --right";
            } else {
                $sh_cmf .= " --left";
            }
            $ret = array();
            $z = exec($sh_cmd, $ret, $ret_status);
            if ($ret_status) {
                sys_err("Command:$sh_cmd");
                foreach ($ret as $v) {
                    sys_err($v);
                }
                sys_err('SCRIPT ERROR');
            }
            // resize everything
            queue_action_set($up_pid, 'SIZE');
        }
        if ($update_cnt>0) {
            sys_msg("$up_pid rotated $rotation");
        }

    }

} else {

    echo "Ooops, this should never happen!<br>\n";

}

$DBH->close;

header ("REFRESH: 0; URL=picture_sort.php");

?>
