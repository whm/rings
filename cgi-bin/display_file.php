<?php
// Display a picture

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

##############################################################################
# subroutines
##############################################################################

function error_pic ($t) {
    $width = 256;
    $inwidth = strlen($t)*8;
    if ($inwidth>$width) {$width = $inwidth;}
    header ("Content-type: image/png");
    $im = @imagecreate ($width, 25)
        or die ("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate ($im, 51, 51, 51);
    $text_color = imagecolorallocate ($im, 255, 255, 255);
    imagestring ($im, 3, 10, 5,  $t, $text_color);
    header("Content-type: image/png");
    imagepng ($im);
    flush();
    exit;
}

##############################################################################
# main routine
##############################################################################

// Form or URL inputs
$in_sig = get_request('in_signature');

// database pointers
require ('/etc/whm/rings_dbs.php');
require ('inc_db_connect.php');

$sel = "SELECT picture_table, side_id ";
$sel .= "FROM picture_sizes ";
$sel .= "WHERE description = 'small' ";
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $this_table   = $row['picture_table'];
        $this_size_id = $row['size_id'];
    }
} else {
    $msg = "Picture size row not found for 'small'";
    sys_err($msg);
    error_pic($msg);
}

$sel = "SELECT info.picture_lot, info.pid, small.mime_type ";
$sel .= "FROM $this_table as small ";
$sel .= "LEFT OUTER JOIN pictures_information as info ";
$sel .= "ON pictures_information.pid = pictures_small.pid ";
$sel .= "WHERE pictures_small.signature = '$in_sig' ";
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $this_lot = $row['picture_lot'];
        $this_pid = $row['pid'];
        $this_type = $row['mime_type'];
    }
} else {
    $msg = "Small picture not found";
    sys_err($msg);
    error_pic($msg);
}

$this_path = picture_path($this_lot, $this_size, $this_pid, $this_type);

header("Content-type: image/jpeg");
readfile($this_path);

?>
