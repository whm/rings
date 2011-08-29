<?php
// file: inc_format_date.php
// author: Bill MacAllister

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

    if (date("Y-m-d")==$a_date && strlen($ret_time)>0) {$ret_date = '';}

    return $ret_date.'&nbsp;'.$ret_time;
}

?>