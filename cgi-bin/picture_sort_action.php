<?php
// ----------------------------------------------------------
// Register Global Fix
//
$in_button_update = $_REQUEST['in_button_update'];
// ----------------------------------------------------------

// File: picture_sort_action.php
// Author: Bill MacAllister
// Date: October 2002

require ('inc_page_open.php');

// ----------------------------------------------------
// Main Routine

require ('/etc/whm/rings_dbs.php');
require ('inc_db_connect.php');

$now = date ('Y-m-d H:i:s');
$up_date_last_maint = $now;

// set update message area
$_SESSION['s_msg'] = '';
$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for updates only

if ( strlen($btn_update)>0 ) {

    $flds['description']  = 's';
    $flds['grade']        = 's';
    $flds['picture_date'] = 's';

    for ($i=0; $i<$_REQUEST['up_picture_cnt']; $i++) {

        $cmd = "date_last_maint='$up_date_last_maint'";
        $update_cnt = 0;

        $up_pid = $_REQUEST["up_pid_$i"];

        // Try and get the old user record
        $sel = "SELECT * ";
        $sel .= "FROM pictures_information WHERE pid=$up_pid ";
        $result = $DBH->query($sel);
        if ($result) {
            $row = $dbh->fetch_array(MYSQLI_ASSOC);
            foreach ($flds as $fld => $type) {
                $up_val = trim($_REQUEST["up_${fld}_${i}"]);
                $db_val = trim($row[$fld]);
                if ("$up_val" != "$db_val") {
                    $cmd .= ", $fld='$up_val' ";
                    $update_cnt++;
                    $_SESSION['s_msg'] .=
                        "<font $ok>$up_pid/$fld $db_val -> $up_val</font><br>";
                }
            }
        }

        if ($update_cnt>0) {
            // Update the meta data
            $sql_cmd = "UPDATE pictures_information SET $cmd ";
            $sql_cmd .= "WHERE pid = $up_pid ";
            $result = $DBH->query($sql_cmd);
            if (!$result) {
                $_SESSION['s_msg'] .=
                    "<font $warn>ERROR:".mysql_error($cnx)."</font><br>\n";
                $_SESSION['s_msg'] .=
                    "<font $warn>Problem SQL:$sql_cmd</font><br>\n";
            }
        }

        // check for a rotation request
        $rotation = $_REQUEST["up_rotate_${i}"];
        $update_cnt = 0;
        if ($rotation == 'LEFT' || $rotation == 'RIGHT') {
            $update_cnt++;
            // request the rotation
            $sh_cmd = "/usr/bin/ring-rotate";
            $sh_cmd .= " --start=$up_pid";
            $sh_cmd .= " --end=$up_pid";
            $sh_cmd .= " --update";
            if ($rotation == 'RIGHT') {
                $sh_cmd .= " --right";
            } else {
                $sh_cmf .= " --left";
            }
            $ret = array();
            $z = exec($sh_cmd, $ret, $ret_status);
            if ($ret_status) {
                $_SESSION['s_msg'] .= "<font $ok>Command:$sh_cmd</font><br>\n";
                foreach ($ret as $v) $_SESSION['s_msg'] .= "<font $ok>$v</font><br>\n";
                $_SESSION['s_msg'] .= "SCRIPT ERROR</br>\n";
            }
            // resize everything
            $sh_cmd = "/usr/bin/ring-resize";
            $sh_cmd .= " --start=$up_pid";
            $sh_cmd .= " --end=$up_pid";
            $sh_cmd .= " --update";
            $ret = array();
            $z = exec($sh_cmd, $ret, $ret_status);
            if ($ret_status) {
                $_SESSION['s_msg'] .= "<font $ok>Command:$sh_cmd</font><br>\n";
                foreach ($ret as $v) $_SESSION['s_msg'] .= "<font $ok>$v</font><br>\n";
                $_SESSION['s_msg'] .= "SCRIPT ERROR</br>\n";
            }
        }
        if ($update_cnt>0) {
            $_SESSION['s_msg'] .= "<font $ok>$up_pid rotated $rotation</font><br>\n";
        }

    }

} else {

    echo "Ooops, this should never happen!<br>\n";

}

$DBH->close;

header ("REFRESH: 0; URL=picture_sort.php");

?>
