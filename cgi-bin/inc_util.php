<?php
// file: inc_util.php
// author: Bill MacAllister
//
// Functions defined here should be completely self-contained and
// not depends on any variables or functions outside of this file.

// Some global constants
$sys_msg_ok   = '<font color="green">';
$sys_msg_warn = '<font color="red">';
$sys_msg_end  = "</font><br>\n";

//-------------------------------------------------------------
// get a value from the REQUEST array if it exists

function get_request ($idx, $default = NULL) {
    if (array_key_exists($idx, $_REQUEST)) {
        $val = $_REQUEST[$idx];
    } else {
        if ($default !== '') {
            $val = $default;
        } else {
            $val = '';
        }
    }
    return $val;
}

//-------------------------------------------------------------
// printable date time

function format_date_time ($in) {

    $ret_date = $in;

    if (preg_match("/(\d{4,4}).(\d{2,2}).(\d{2,2}).(\d{2,2}.\d{2,2}.*)/",
                   $in,
                   $matches)) {
        $a_yr   = $matches[1];
        $a_mon  = $matches[2];
        $a_day  = $matches[3];
        $a_time = $matches[4];
        $ret_mon = $a_mon;
        if ($a_mon == 1) {$ret_mon = "Jan";}
        elseif ($a_mon == 2) {$ret_mon = "Feb";}
        elseif ($a_mon == 3) {$ret_mon = "Mar";}
        elseif ($a_mon == 4) {$ret_mon = "Apr";}
        elseif ($a_mon == 5) {$ret_mon = "May";}
        elseif ($a_mon == 6) {$ret_mon = "Jun";}
        elseif ($a_mon == 7) {$ret_mon = "Jul";}
        elseif ($a_mon == 8) {$ret_mon = "Aug";}
        elseif ($a_mon == 9) {$ret_mon = "Sep";}
        elseif ($a_mon == 10) {$ret_mon = "Oct";}
        elseif ($a_mon == 11) {$ret_mon = "Nov";}
        elseif ($a_mon == 12) {$ret_mon = "Dec";}
        $ret_date = "$a_day-$ret_mon-$a_yr";

        $ret_time = $a_time;
        $ret_time = str_replace ('.000', '', $ret_time);
        $ret_time = str_replace ('00:00:00', '', $ret_time);

    }

    return $ret_date.'&nbsp;'.$ret_time;
}

//-------------------------------------------------------------
// Redirect the user to the home page

function http_redirect ($nextURL="index.php") {
    header ("REFRESH: 0; URL=$nextURL");
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Rings</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1><a href=\"$nextURL\">Rings</a></h1>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

//-------------------------------------------------------------
// Read an attribute=value configuation file, store the results
// in an array and return the array.

function read_conf ($conf_file = '/etc/rings/rings.conf') {

    # Bail out if we can find the file
    if (! file_exists($conf_file)) {
        return;
    }

    # Open the file and read it line by line
    $this_conf = array();
    $fh = fopen($conf_file, 'r');
    if (!$fh) {
        syslog(LOG_ERR, "ERROR: reading $conf_file");
        exit("Problem opening $conf_file");
    }
    while (($line = fgets($fh)) != false) {
        if (substr($line, 0, 1) == '#') {
            continue;
        }
        if (preg_match('/^(\S+)\s*=\s*(.*)/', $line, $part)) {
            $this_conf[trim($part[1])] = trim($part[2]);
        }
    }
    fclose($fh);
    return $this_conf;
}

// ------------------------------------------------------------------------
// Message helper routines

function msg_okay ($txt) {
    global $sys_msg_ok;
    global $sys_msg_end;
    return "${sys_msg_ok}${txt}${sys_msg_end}";
}
    
function msg_err ($txt) {
    global $sys_msg_warn;
    global $sys_msg_end;
    return "${sys_msg_warn}ERROR: ${txt}${sys_msg_end}";
}

function sys_msg ($txt) {
    global $sys_msg_ok;
    global $sys_msg_end;
    $_SESSION['msg'] .= "${sys_msg_ok}${txt}${sys_msg_end}";
    syslog(LOG_INFO, $txt);
    return;
}
    
function sys_err ($txt) {
    global $sys_msg_warn;
    global $sys_msg_end;
    $_SESSION['msg'] .= "${sys_msg_warn}ERROR: ${txt}${sys_msg_end}";
    syslog(LOG_ERR, $txt);
    return;
}

function sys_display_msg () {
    if (!empty($_SESSION['msg'])) {
        echo $_SESSION['msg'];
    }
    $_SESSION['msg'] = '';
    return;
}
    
// ------------------------------------------------------------------------
// Assemble the path to a picture

function picture_path ($lot, $size_id, $pid, $file_type) {

    global $CONF;

    if (empty($lot)) {
        $m = "picture_path missing picture_lot ($lot)";
        sys_err($m);
        return $m;
    }
    list ($a_size_id, $a_size_desc, $a_table) = validate_size($size_id);
    if (empty($a_size_id)) {
        $m = "picture_path invalid size_id ($size_id)";
        sys_err($m);
        return $m;
    }
    if ($pid < 1) {
        $m = 'picture_path invalid pid';
        sys_err($m);
        return $m;
    }
    list ($a_file_type, $a_mime_type) = validate_type($file_type);
    if (empty($a_file_type)) {
        $m = "picture_path invalid file_type ($type)";
        sys_err($m);
        return $m;
    }
    
    $pic_dir = $CONF['picture_root'];
    $pic_dir .= '/' . $lot;
    $pic_dir .= '/' . $a_size_id;

    $pic_file = $pic_dir;
    $pic_file .= '/' . $pid;
    $pic_file .= '.' . $a_file_type;

    return array($pic_dir, $pic_file);
}

// ------------------------------------------------------------------------
// Function to exit without displaying anything and return to the main
// index page.

function back_to_index ($msg) {

    if (!empty($msg)) {
        sys_msg_err($msg);
    }
    echo "<html>\n";
    echo "<head>\n";
    echo "<meta http-equiv=\"refresh\" ";
    echo '    content="0; URL=http://'.$_SERVER['SERVER_NAME'].'/rings">'."\n";
    echo "<title>Rings of Pictures</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo '<a href="rings">Rings of Pictures</a>'."\n";
    echo "</body>\n";
    echo "</html>\n";

    exit;
}

?>