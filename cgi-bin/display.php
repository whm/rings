<?php
// Display a picture

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_pid  = get_request('in_pid');
$in_size = get_request('in_size');

// database pointers
require ('/etc/whm/rings_dbs.php');
require ('inc_db_connect.php');

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
} else {
    // Get the picture in the size requested
    if (strlen($in_size)>0) {
        $sel = "SELECT picture,picture_type FROM pictures_$in_size ";
    } else {
        $sel = "SELECT picture,picture_type FROM pictures_raw ";
    }
    $sel .= "WHERE pid=$in_pid ";
    $result  = $DBH->query($sel);
    $row     = $result->fetch_array(MYSQLI_ASSOC);
    $picture = $row['picture'];
    $type    = $row['picture_type'];
    
    if (strlen($picture) == 0) {
        // fall back to the raw image because the requested size
        // was not found.
        $sel = "SELECT picture,picture_type FROM pictures_raw ";
        $sel .= "WHERE pid=$in_pid ";
        $result  = $DBH->query($sel);
        $row     = $result->fetch_array(MYSQLI_ASSOC);
        $picture = $row['picture'];
        $type    = $row['picture_type'];
    }
    
    if (strlen($picture) == 0) {
        echo "<html>\n";
        echo "<head>\n";
        echo "<title>Ring Select</title>\n";
        require('inc_page_head.php');
        echo '<LINK href="/rings-styles/ring_style.css '
            . 'rel="stylesheet" '
            . 'type="text/css">' . "\n";
        echo "<h1>Picture size not available</h1>\n";
        echo "</head>\n";
        echo "</html>\n";

    } else {
        header("Content-type: $type");
        echo $picture;
        flush();
    }
    
}
?>
