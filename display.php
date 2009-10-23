<?php
// Display a picture

// Init session, connect to database
$authNotRequired = 1;
require('inc_ring_init.php');

$display_warning = auth_picture_invisible($in_pid);
if ($display_warning > 0) {
    // display suppressed.  Give the user a message
    $t = 'You must login to view this picture.';
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
} else {
    // Get the picture in the size requested
    if (strlen($in_size)>0) {
        $sel = "SELECT picture,picture_type FROM pictures_$in_size ";
    } else {
        $sel = "SELECT picture,picture_type FROM pictures_raw ";
    }
    $sel .= "WHERE pid=$in_pid ";
    $status = mysql_db_query ($mysql_db, $sel, $cnx);
    $ret = mysql_fetch_array($status);
    $picture = $ret[0];
    $type    = $ret[1];
    
    if (strlen($picture) == 0) {
        // fall back to the raw image because the requested size
        // was not found.
        $sel = "SELECT picture,picture_type FROM pictures_raw ";
        $sel .= "WHERE pid=$in_pid ";
        $status = mysql_db_query ($mysql_db, $sel, $cnx);
        $ret = mysql_fetch_array($status);
        $picture = $ret[0];
        $type    = $ret[1];
    }
    
    header("Content-type: $type");
    echo $picture;
    flush();
    
}
mysql_close ($cnx);
?>