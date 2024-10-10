<?php
// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

$thisGroup = 'rrpics';

// Pick a specific ppe from our group
$sel = "SELECT count(*) FROM picture_groups ";
$sel .= "WHERE group_id='$thisGroup' ";
$status = $DBH->query($sel);
$ret = $status->fetch_array(MYSQLI_ASSOC);
$group_count = $ret->num_rows;

$offset = rand(0,$group_count-1);
$sel = "SELECT uid FROM picture_groups ";
$sel .= "WHERE group_id='$thisGroup' ";
$sel .= "LIMIT $offset, 1 ";
$status = $DBH->query($sel);
$ret = $status->fetch_array(MYSQLI_ASSOC);
$uid = $ret[0];

// Pick a picture at random
$sel = "SELECT count(*) FROM picture_rings ";
$sel .= "WHERE uid='$uid' ";
$status = $DBH->query ($sel);
$ret = $status->fetch_array(MYSQLI_ASSOC);
$picture_count = $ret[0];

$offset = rand(0,$picture_count-1);
$sel = "SELECT pid FROM picture_rings ";
$sel .= "WHERE uid='$uid' ";
$sel .= "LIMIT $offset, 1 ";
$status = $DBH->query ($sel);
$ret = $status->fetch_array(MYSQLI_ASSOC);
$pid = $ret[0];

// Finally get the picture
$sel = "SELECT picture_large,picture_type FROM pictures ";
$sel .= "WHERE pid=$pid ";
$status = $DBH->query($sel);
$ret = $status->fetch_array(MYSQLI_ASSOC);
$picture = $ret[0];
$type    = $ret[1];

//header("Content-type: $type");
//echo $picture;
//flush();

$pic = @imagecreatefromstring($picture);

$width = 1600;
$height = 1200;
$bg = @imagecreatetruecolor ($width, $height)
     or die ("Cannot Initialize new GD image stream");
$background_color = imagecolorallocate ($bg, 224, 224, 255);
ImageFilledRectangle($bg, 0, 0, $width, $height, $background_color);

imagecopy($bg, $pic, 0, 0, 0, 0, imagesx($pic), imagesy($pic));
header("Content-Type: image/jpeg");
imagejpeg($bg);
imagedestroy($bg);
imagedestroy($pic);

mysql_close ($db_link);

?>