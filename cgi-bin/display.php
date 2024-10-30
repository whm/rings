<?php
// Display a picture

##############################################################################
# Subroutines
##############################################################################
    
function no_picture ($t, $flag) {
    // display suppressed.  Give the user a message
    if ($flag == 'small') {
        $t = ' ';
        $width = 1;
        $inwidth = 1;
    } else {
        $width = 256;
        $inwidth = strlen($t)*8;
    }
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
# Main routine
##############################################################################

require('inc_ring_init.php');

// Form or URL inputs
$in_pid  = get_request('in_pid');
$in_size = get_request('in_size');
if (auth_picture_invisible($in_pid)>0) {
    no_picture('Hidden', $in_size);
}
if (empty($in_size)) {
    $in_size = $CONF['display_size'];
} else {
    list($valid_size, $valid_desc) = validate_size($in_size);
    if (empty($valid_size)) {
        sys_err("Invalid picture size $in_size");
        $in_size = $CONF['display_size'];
    } else {
        $in_size = $valid_size;
    }
}

$sel = 'SELECT picture_lot FROM pictures_information WHERE pid=?';
if ($CONF['debug']) {
    syslog(LOG_DEBUG, $sel);
}
if (!$stmt = $DBH->prepare($sel)) {
    $m = 'Prepare failed: (' . $DBH->errno . ') ' . $DBH->error;
    syslog(LOG_ERR, $m);
    syslog(LOG_INFO, "Problem statement: $sel");
}
$stmt->bind_param('i', $in_pid);
$stmt->execute();
$stmt->bind_result($z);
if ($stmt->fetch()) {
    $picture_lot = $z;
}
$stmt->close();

if (empty($picture_lot)) {
    syslog(LOG_DEBUG, "Failed to find $pic_path");
    no_picture('Picture not available. (picture_lot not found', 'normal');
}

list($mime_type, $file_type) = get_picture_type($in_pid, $in_size);
list($pic_dir, $pic_path)
    = picture_path($picture_lot, $in_size, $in_pid, $file_type);
if ($CONF['debug']) {
    syslog(LOG_INFO, "Opening file $pic_path");
}
if (file_exists($pic_path)) {
    header("Content-type: $mime_type");
    readfile($pic_path);
    flush();
} else {
    syslog(LOG_DEBUG, "Failed to find $pic_path");
    no_picture('Picture not available. (file not found)', 'normal');
}
?>
