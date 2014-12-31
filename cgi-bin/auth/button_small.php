<?php
// Display a small button

require('inc_util.php');

// Form or URL input
$in_button  = get_request('in_button');

// ----------------------------------------------------------
//
$width = 100;
$inwidth = strlen($in_button)*4;
if ($inwidth>$width) {$width = $inwidth;}
header ("Content-type: image/png");
$im = @imagecreate ($width, 15)
     or die ("Cannot Initialize new GD image stream");
$background_color = imagecolorallocate ($im, 255, 255, 255);
$text_color = imagecolorallocate ($im, 233, 14, 91);
imagestring ($im, 2, 10, 1,  $in_button, $text_color);
imagepng ($im);
?>
