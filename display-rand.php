<?php

// -- main routine

// database pointers
require ('/etc/whm/rings_dbs.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);

$thisGroup = 'rrpics';

// Pick a specific ppe from our group
$sel = "SELECT count(*) FROM picture_groups ";
$sel .= "WHERE group_id='$thisGroup' ";
$status = mysql_db_query ($mysql_db, $sel, $db_link);
$ret = mysql_fetch_array($status);
$group_count = $ret[0];

$offset = rand(0,$group_count-1);
$sel = "SELECT uid FROM picture_groups ";
$sel .= "WHERE group_id='$thisGroup' ";
$sel .= "LIMIT $offset, 1 ";
$status = mysql_db_query ($mysql_db, $sel, $db_link);
$ret = mysql_fetch_array($status);
$uid = $ret[0];

// Pick a picture at random
$sel = "SELECT count(*) FROM picture_details ";
$sel .= "WHERE uid='$uid' ";
$status = mysql_db_query ($mysql_db, $sel, $db_link);
$ret = mysql_fetch_array($status);
$picture_count = $ret[0];

$offset = rand(0,$picture_count-1);
$sel = "SELECT pid FROM picture_details ";
$sel .= "WHERE uid='$uid' ";
$sel .= "LIMIT $offset, 1 ";
$status = mysql_db_query ($mysql_db, $sel, $db_link);
$ret = mysql_fetch_array($status);
$pid = $ret[0];

// Finally get the picture
$sel = "SELECT picture_large,picture_type FROM pictures ";
$sel .= "WHERE pid=$pid ";
$status = mysql_db_query ($mysql_db, $sel, $db_link);
$ret = mysql_fetch_array($status);
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