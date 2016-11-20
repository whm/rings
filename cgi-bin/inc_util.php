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
    return isset($_REQUEST[$idx]) ? $_REQUEST[$idx] : $default;
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
    syslog(LOG_ERROR, $txt);
    return;
}
    
?>