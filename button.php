<?php
$width = 144;
$inwidth = strlen($in_button)*8;
if ($inwidth>$width) {$width = $inwidth;}
header ("Content-type: image/png");
if ( get_magic_quotes_gpc() ) {$in_button = stripslashes($in_button);}
$im = @imagecreate ($width, 25)
     or die ("Cannot Initialize new GD image stream");
     $background_color = imagecolorallocate ($im, 51, 51, 51);
     $text_color = imagecolorallocate ($im, 255, 255, 255);
     imagestring ($im, 3, 10, 5,  $in_button, $text_color);
     imagepng ($im);
?>