<?php

// -- main routine

// database pointers
require ('mysql.php');

// connect to the db
$db_link = mysql_connect($mysql_host, $mysql_user, $mysql_pass);

if (strlen($in_size)>0) {
    $sel = "SELECT picture_$in_size,picture_type FROM pictures ";
} else {
    $sel = "SELECT picture,picture_type FROM pictures ";
}
$sel .= "WHERE pid=$in_pid ";

$status = mysql_db_query ($mysql_db, $sel, $db_link);

$ret = mysql_fetch_array($status);

$picture = $ret[0];
$type    = $ret[1];
header("Content-type: $type");
echo $picture;
flush();

mysql_close ($db_link);

?>