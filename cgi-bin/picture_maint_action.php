<?php
// ----------------------------------------------------------
// File: picture_maint_action.php
// Author: Bill MacAllister
// Date: 31-Dec-2001

require ('inc_page_open.php');
require('inc_util.php');

// Form or URL inputs
$in_fld                 = get_request('in_fld');
$in_val                 = get_request('in_val');
$in_picture_date        = get_request('in_picture_date');
$in_date_added          = get_request('in_date_added');
$in_pid                 = get_request('in_pid');
$in_type                = get_request('in_type');
$in_date_last_maint     = get_request('in_date_last_maint');
$in_picture_sequence    = get_request('in_picture_sequence');
$in_newuids             = get_request('in_newuids');
$in_button_update       = get_request('in_button_update');
$in_button_rotate_left  = get_request('in_button_rotate_left');
$in_button_rotate_right = get_request('in_button_rotate_right');
$in_button_del          = get_request('in_button_del');

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

function date_dup_check($dt, $pid) {

    global $DBH;

    $new_seq = 0;

    $sel = 'SELECT count(*) cnt FROM pictures_information ';
    $sel .= "WHERE picture_date='$dt' ";
    $sel .= "AND pid != $pid ";
    $result = $DBH->query($sel);
    if (!$result) {
        $_SESSION['msg'] .= "<br>MySQL error executing: $sel";
        return 0;
    }
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        if ($row['cnt'] > 0) {
            $sel = 'SELECT max(picture_sequence) max_seq ';
            $sel .= 'FROM pictures_information ';
            $sel .= "WHERE picture_date='$dt' ";
            $result = $DBH->query($sel);
            if (!$result) {
                $_SESSION['msg'] .= "<br>MySQL error executing: $sel";
                return 0;
            }
            if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $new_seq = $row['max_seq'] + 1;
            }
        }
    }

    return $new_seq;

}

// ----------------------------------------------------
// Main Routine

require('/etc/whm/rings_dbs.php');
require('inc_db_connect.php');
require('inc_db_functions.php');

$now                = date ('Y-m-d H:i:s');
$in_date_last_maint = $now;
$in_date_added      = $now;

// No spaces allowed in the identifier
$in_pid = preg_replace ('/\s+/', '', $in_pid);

// how to get back
$next_url    = "picture_maint.php";
$next_header = "REFRESH: 0; URL=$next_url";

// set update message area
$ok   = 'color="#009900"';
$warn = 'color="#330000"';

// ---------------------------------------------------------
// Processing for specific request, i.e. add, change, delete
if ( isset($in_button_update) ) {

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
    if (!isset($this_picture)) {
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
    $seq = date_dup_check($in_picture_date, $in_pid);
    $_SESSION['maint_last_datetime'] = $in_picture_date;

    $up_msg = '';
    $db_flds = get_fld_names('pictures_information');
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
            $cmd .= "$comma $db_fld=" . sql_quote($in_val,'s') . ' ';
            $comma = ',';
            $update_cnt++;
            $up_msg .= "<font $ok>$db_fld updated.</font><br>";
        }
    }

    if ($update_cnt>1) {
        // Make the changes
        $sql_cmd = "UPDATE pictures_information SET $cmd ";
        $sql_cmd .= "WHERE pid = $in_pid ";
        $result = $DBH->($sql_cmd);
        $_SESSION['msg'] .= $up_msg;
    }
    $next_pid = $in_pid;

    // delete picture details
    for ($i=0; $i<get_request('in_del_cnt', 0); $i++) {
        $a_flag = get_request("in_del_$i");
        if (isset($a_flag)) {
            $a_uid = get_request("in_del_uid_$i");
            $cmd = "DELETE FROM picture_details ";
            $cmd .= "WHERE uid = '$a_uid' ";
            $cmd .= "AND pid = $in_pid ";
            $result = $DBH->($cmd);
            if ($result) {
                $update_cnt++;
                $_SESSION['msg'] .= "<font $ok>"
                    . "Deleted $a_uid from picture . </font><br>";
                $_SESSION['s_uid_weight'][$a_uid]--;
                if ($_SESSION['s_uid_weight'][$a_uid] < 0) {
                    $_SESSION['s_uid_weight'][$a_uid] = 0;
                }
            } else {
                $_SESSION['msg'] .= "Problem deleting picture details.<br>";
                $_SESSION['msg'] .= "Problem SQL: $sql_cmd<br>";
            }
        }
    }

    // add picture details
    for ($i=0; $i<get_request('in_add_cnt'); $i++) {
        $a_uid = '';
        if (isset($in_newuids[$i])) {$a_uid = $in_newuids[$i];}
        if (strlen($a_uid) > 0) {
            $flds = '';
            $vals = '';
            mkin ('uid', $a_uid, 's');
            mkin ('pid', $in_pid, 'n');
            $cmd = "INSERT INTO picture_details ($flds) VALUES ($vals)";
            $add_result = $DBH->query($cmd);
            if ($add_result) {
                $update_cnt++;
                $_SESSION['msg'] .= "<font $ok>$a_uid added.</font><br>";
                $_SESSION['s_uid_weight'][$a_uid]++;
                if ($_SESSION['s_uid_weight'][$a_uid] > 32767) {
                    $_SESSION['s_uid_weight'][$a_uid] = 32767;
                }
            } else {
                $_SESSION['msg'] .= "Problem updating picture details<br>";
                $_SESSION['msg'] .= "Problem SQL: $cmd<br>";
            }
        }
    }
    if ($update_cnt < 2) {
        $_SESSION['msg'] .= "No changes found.<br>";
    }

} elseif ( isset($in_button_del) ) {

    // -- Delete a record -------------------------------

    $del_tables[] = 'pictures_information';
    $del_tables[] = 'pictures_raw';
    $del_tables[] = 'pictures_small';
    $del_tables[] = 'pictures_large';
    $del_tables[] = 'pictures_larger';
    $del_tables[] = 'pictures_1280_1024';

    foreach ($del_tables as $thisTable) {
        $sql_cmd = "DELETE FROM $thisTable WHERE pid=$in_pid ";
        $result = $DBH->query($sql_cmd);
        if ($result) {
            $_SESSION['msg'] .= "<font $ok>Picture '$in_pid' deleted "
                . "from $thisTable.</font><br>";
        } else {
            $_SESSION['msg']
                .= "Problem deleting $in_pid from $thisTable<br>";
            $_SESSION['msg'] .= "Problem SQL: $sql_cmd<br>";
        }
    }

    $next_uid = 'CLEARFORM';

} elseif ( isset($in_button_rotate_right) || isset($in_button_rotate_left) ) {

    $sh_cmd = "/usr/bin/ring-rotate";
    $sh_cmd .= " --start=$in_pid";
    $sh_cmd .= " --end=$in_pid";
    $sh_cmd .= " --update";
    if (isset($in_button_rotate_right)) {
        $sh_cmd .= " --right";
    } else {
        $sh_cmf .= " --left";
    }
    $ret = array();
    $z = exec($sh_cmd, $ret, $ret_status);
    if ($ret_status) {
        $_SESSION['msg'] .= "<font $ok>Command:$sh_cmd</font><br>\n";
        foreach ($ret as $v) $_SESSION['msg'] .= "<font $ok>$v</font><br>\n";
        $_SESSION['msg'] .= "SCRIPT ERROR</br>\n";
    }

    $sh_cmd = "/usr/bin/ring-resize";
    $sh_cmd .= " --start=$in_pid";
    $sh_cmd .= " --end=$in_pid";
    $sh_cmd .= " --update";
    $ret = array();
    $z = exec($sh_cmd, $ret, $ret_status);
    if ($ret_status) {
        $_SESSION['msg'] .= "<font $ok>Command:$sh_cmd</font><br>\n";
        foreach ($ret as $v) $_SESSION['msg'] .= "<font $ok>$v</font><br>\n";
        $_SESSION['msg'] .= "SCRIPT ERROR</br>\n";
    }

    $next_pid = $in_pid;

} else {

    echo "Ooops, this should never happen!<br>\n";

}

header ("$next_header?in_pid=$next_pid");
?>
<html>
<head>
<title>Picture Mainteance Action</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>
<body>
<a href="picture_maint.php?in_pid=<?php echo $next_pid;?>">
  Return to Picture Maintenance</a>
</body>
</html>
