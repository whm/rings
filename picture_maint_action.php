<?php

// File: picture_maint_action.php
// Author: Bill MacAllister
// Date: 31-Dec-2001
// Note: Initially at least this is update and delete only.  Add is
// handled by a separate script.

require ('inc_page_open.php');

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

//-------------------------------------------------------------
// Check for duplicate dates

function date_dup_check($dt, $pid, $cnx) {

    $new_seq = 0;

    $sel = 'SELECT count(*) cnt FROM pictures_information ';
    $sel .= "WHERE picture_date='$dt' ";
    $sel .= "AND pid != $pid ";
    $result = mysql_query ($sel, $cnx);
    if (!$result) {
        $_SESSION['s_msg'] .= "<br>MySQL error executing: $sel";
        return 0;
    }
    if ($row = mysql_fetch_array ($result)) {
        if ($row['cnt'] > 0) {
            $sel = 'SELECT max(picture_sequence) max_seq ';
            $sel .= 'FROM pictures_information ';
            $sel .= "WHERE picture_date='$dt' ";
            $result = mysql_query ($sel, $cnx);
            if (!$result) {
                $_SESSION['s_msg'] .= "<br>MySQL error executing: $sel";
                return 0;
            }
            if ($row = mysql_fetch_array ($result)) {
                $new_seq = $row['max_seq'] + 1;
            }
        }
    }

    return $new_seq;

}

// ----------------------------------------------------
// Main Routine

require('inc_dbs.php');
// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL host $mysql_host";
}
$result = mysql_select_db($mysql_db);
if (!$result) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL db $mysql_db";
}

$now = date ('Y-m-d H:i:s');
$in_date_last_maint = $now;
$in_date_added = $now;

// No spaces allowed in the identifier
$in_pid = ereg_replace (" ","",$in_pid);

// how to get back
$next_url = "picture_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";

// set update message area
if (!session_is_registered('s_msg')) {session_register('s_msg');}
$_SESSION['s_msg'] = '';
$ok = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete
if ( strlen($btn_update)>0 ) {
    
    // Try and get the old user record
    $sel = "SELECT * FROM pictures_information WHERE pid=$in_pid ";
    $result = mysql_query ($sel, $cnx);
    if ($result) {
        $row = mysql_fetch_array ($result);
        $this_picture = $row['pid'];
        $fld_cnt = mysql_num_fields($result);
    }
    $update_flag = 1;
    $add_flag = 0;
    if (strlen($this_picture)==0) {
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
    $update_list[] = 'picture_sequence';
    $update_list[] = 'description';
    $update_list[] = 'key_words';
    $update_list[] = 'taken_by'; 
    $update_list[] = 'grade'; 
    $update_list[] = 'public'; 
    $update_list[] = 'date_last_maint';
    
    # check for duplicate date, sequence
    $seq = date_dup_check($in_picture_date, $in_pid, $cnx);
    if ($seq > 0) {$in_picture_sequence = $seq;}

    $up_msg = '';
    for ($i=0; $i<$fld_cnt; $i++) {
        $db_fld = mysql_field_name ($result, $i);
        $fld_update_flag = 0;
        foreach ($update_list as $thisName) {
            if ($thisName == $db_fld) {$fld_update_flag = 1;}
        }
        if ($fld_update_flag == 0) {continue;}
        $in_fld = "in_$db_fld";
        $in_val = trim(stripslashes($$in_fld));
        
        // remember the last entered value    
        $sess_fld = "session_$db_fld";
        $_SESSION["$sess_fld"] = $in_val;

        if (trim($in_val) != trim($row[$db_fld])) {
            $cmd .= "$comma $db_fld=".sql_quote($in_val,'s'). " ";
            $comma = ',';
            $update_cnt++;
            $up_msg .= "<font $ok>$db_fld updated.</font><br>";
        }
    }
    
    if ($update_cnt>1) {
        // Make the changes 
        $sql_cmd = "UPDATE pictures_information SET $cmd ";
        $sql_cmd .= "WHERE pid = $in_pid ";
        $result = mysql_query ($sql_cmd,$cnx);
        $_SESSION['s_msg'] .= $up_msg;
    }
    $next_pid = $in_pid;
    
    // delete picture details
    for ($i=0; $i<$del_cnt; $i++) {
        $name = "del_$i"; $a_flag = $$name;
        if (strlen ($a_flag) > 0) {
            $name = "del_uid_$i"; $a_uid = $$name;
            $cmd = "DELETE FROM picture_details ";
            $cmd .= "WHERE uid = '$a_uid' ";
            $cmd .= "AND pid = $in_pid ";
            $result = mysql_query ($cmd);
            if ($result) {
                $update_cnt++;
                $_SESSION['s_msg'] .= "<font $ok>Deleted $a_uid from picture.</font><br>";
                $_SESSION['s_uid_weight'][$a_uid]--;
                if ($_SESSION['s_uid_weight'][$a_uid] < 0) {
                    $_SESSION['s_uid_weight'][$a_uid] = 0;
                }
            } else {
                $_SESSION['s_msg'] .= "Problem deleting picture details.<br>";
                $_SESSION['s_msg'] .= "Problem SQL: $sql_cmd<br>";
            }
        }
    }
    
    // add picture details
    for ($i=0; $i<$add_cnt; $i++) {
        $a_uid = '';
        if (isset($in_newuids[$i])) {$a_uid = $in_newuids[$i];}
        if (strlen($a_uid) > 0) {
            $flds = '';
            $vals = '';
            mkin ('uid', $a_uid, 's');
            mkin ('pid', $in_pid, 'n');
            $cmd = "INSERT INTO picture_details ($flds) VALUES ($vals)";
            $add_result = mysql_query ($cmd,$cnx);
            if ($add_result) {
                $update_cnt++;
                $_SESSION['s_msg'] .= "<font $ok>$a_uid added.</font><br>";
                $_SESSION['s_uid_weight'][$a_uid]++;
                if ($_SESSION['s_uid_weight'][$a_uid] > 32767) {
                    $_SESSION['s_uid_weight'][$a_uid] = 32767;
                }
            } else {
                $_SESSION['s_msg'] .= "Problem updating picture details<br>";
                $_SESSION['s_msg'] .= "Problem SQL: $cmd<br>";
            }
        }
    }
    if ($update_cnt < 2) {
        $_SESSION['s_msg'] .= "No changes found.<br>";
    }
    
} elseif ( strlen($btn_del)>0 ) {
    
    // -- Delete a record -------------------------------
    
    $del_tables[] = 'pictures_information';
    $del_tables[] = 'pictures_raw';
    $del_tables[] = 'pictures_small';
    $del_tables[] = 'pictures_large';
    $del_tables[] = 'pictures_larger';
    $del_tables[] = 'pictures_1280_1024';

    foreach ($del_tables as $thisTable) {
        $sql_cmd = "DELETE FROM $thisTable WHERE pid=$in_pid ";
        $result = mysql_query ($sql_cmd,$cnx);
        if ($result) {
            $_SESSION['s_msg'] .= "<font $ok>Picture '$in_pid' deleted "
                . "from $thisTable.</font><br>";
        } else {
            $_SESSION['s_msg'] 
                .= "Problem deleting $in_pid from $thisTable<br>";
            $_SESSION['s_msg'] .= "Problem SQL: $sql_cmd<br>";
        }
    }

    $next_uid = 'CLEARFORM';
    
} else {
    
    echo "Ooops, this should never happen!<br>\n";
    
}

mysql_close ($cnx);

header ("$next_header?in_pid=$next_pid");
?>
<html>
<head>
<title>Picture Mainteance Action</title>
</head>
<body>
<a href="picture_maint?in_pid=$next_pid">Return to Picture Maintenance</a>
</body>
</html>
