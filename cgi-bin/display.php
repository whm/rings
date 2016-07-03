<?php
// Display a picture

##############################################################################
# Subroutines
##############################################################################

function pic_not_found () {
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Ring Select</title>\n";
    require('inc_page_head.php');
    echo '<LINK href="/rings-styles/ring_style.css '
        . 'rel="stylesheet" '
        . 'type="text/css">' . "\n";
    echo "<h1>Picture not available</h1>\n";
    echo "</head>\n";
    echo "</html>\n";
    exit;
}

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_pid  = get_request('in_pid');
$in_size = get_request('in_size');

$display_warning = auth_picture_invisible($in_pid);
if ($display_warning > 0) {
    // display suppressed.  Give the user a message
    if ($in_size == 'small') {
        $t = ' ';
        $width = 1;
        $inwidth = 1;
    } else {
        $t = 'You must login to view this picture.';
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

$sel = 'SELECT picture_lot FROM pictures_information ';
$sel .= 'WHERE pid=?';
if (!$stmt = $DBH->prepare($sel)) {
    $m = 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
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
    syslog(LOG_ERR, "Picture lot was not returned for pid: $in_pid");
    pic_not_found();
}

$pic_path = $CONF['ring_root']
    . '/' . $picture_lot
    . '/' . $in_size
    . '/' . $in_pid . '.jpg';
syslog(LOG_INFO, "Opening file $pic_path");
if (file_exists($pic_path)) {
    header("Content-type: $type");
    readfile($pic_path);
    flush();
} else {
    pic_not_found();
}
?>
