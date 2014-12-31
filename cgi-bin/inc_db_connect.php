<?PHP
// connect to the database
$DBH = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($DBH->connect_errno) {
    $_SESSION['msg'] .= "\n<br>ERROR: connecting to MySQL host $mysql_host";
    $_SESSION['msg'] .= "\n<br>MySQL ERROR: " . $DBH->connect_error;
}
?>