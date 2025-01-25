<?php
// ----------------------------------------------------------
// File: add_date.php
// Author: Bill MacAllister

require('inc_ring_init.php');

// Form or URL input
$in_picture_date  = get_request('in_picture_date');
$in_button_submit = get_request('in_button_submit');
$in_noaction      = get_request('in_noaction');
$in_pid           = get_request('in_pid');
$in_seq           = get_request('in_seq');

?>
<html>
<head>
<title>Add Picture to Email List</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>

<?php

######################################################################
# Subroutines                                                        #
######################################################################

// ------------------------------
// get current picture date
//
function get_current_date ($this_pid) {
    global $DBH;

    $sel = 'SELECT picture_date, picture_sequence FROM pictures_information ';
    $sel .= 'WHERE pid = ? ';
    if (!$sth = $DBH->prepare($sel)) {
        sys_err("Prepare failed: "
                . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $sel");
    }
    $sth->bind_param('i', $this_pid);
    if (!$sth->execute()) {
        sys_err('Execute failed: '
               . $DBH->error . '(' . $DBH->errno . ')');
        sys_err("Problem statement: $cmd");
    }
    $sth->bind_result($p1, $p2);
    if ($sth->fetch()) {
        $current_date  = $p1;
        $current_seq   = $p2;
    }
    $sth->close();
    return array($current_date, $current_seq);

}

######################################################################
# Main routine                                                       #
######################################################################

// Bail out if requested
if ( !empty($in_noaction) )  {
    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";
    exit;
}

if ( !empty($in_button_submit) ) {
    if ( !empty($in_picture_date) ) {
        // update the date
        $_SESSION['pic_last_datetime'] = $in_picture_date;
    }
    list($current_date, $current_seq) = get_current_date($in_pid);
    if ($current_date != $in_picture_date || $current_seq != $in_seq) {
        $new_seq = get_picture_sequence($in_picture_date, $in_seq);
        $sql_cmd = 'UPDATE pictures_information SET ';
        $sql_cmd .= 'picture_date = ?, ';
        $sql_cmd .= 'picture_sequence = ?, ';
        $sql_cmd .= 'date_last_maint = NOW() ';
        $sql_cmd .= "WHERE pid = ? ";
        if (!$sth = $DBH->prepare($sql_cmd)) {
            $m = 'Prepare failed: ' . $DBH->error
               . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $sql_cmd";
            sys_err($m);
        }
        $sth->bind_param('sii',
                         $in_picture_date,
                         $in_seq,
                         $in_pid);
        if (!$sth->execute()) {
            $m = 'Execute failed: ' . $DBH->error
               . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $sql_cmd";
            sys_err($m);
        }
        $sth->close();
    }
}

$next_datetime = '';
if (!empty($_SESSION['pic_last_datetime'])) {
    $next_datetime = increment_time($_SESSION['pic_last_datetime']);
}
list($current_date, $current_seq) = get_current_date($in_pid);
?>

<script>
function setDatetime() {
  var f;
  f = document.updateDate;

  f.in_picture_date.value = f.next_datetime.value;
  f.set_date.checked = false;
  return false;

}
</script>

</head>

<body class="vs">

<h3>Update Picture Date</h3>

<form name="updateDate" action="add_date.php">
Picture ID: <?php echo $in_pid;?>
<br/>
<input type="text" name="in_picture_date" size="30"
       value="<?php print $current_date; ?>">

<?php
if (!empty($next_datetime)) { ?>
    <input type="hidden"
           name="next_datetime"
           value="<?php echo $next_datetime;?>">
    <br/>
    <input type="checkbox"
           name="set_date"
           onClick="setDatetime()">
           Set Date to <?php echo $next_datetime; ?>
<?php } ?>
<br/>
<input type="submit" name="in_button_submit" value="Update Picture Date">
<input type="submit" name="in_noaction" value="Cancel">
<input type="hidden" name="in_pid" value="<?php echo $in_pid;?>">
<input type="hidden" name="in_seq" value="<?php echo $current_seq;?>">
<div class="check " id="check"/>
</form>

</body>
</html>

<?php
if (!empty($in_button_submit)) {
    echo "<script language=\"JavaScript\">\n";
    echo " document.getElementById('check').innerHTML='Picture Selected';\n";
    echo " setTimeout(() => {  window.close(); }, 2000);\n";
    echo "</script>\n";
}
?>
