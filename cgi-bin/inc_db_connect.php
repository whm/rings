<?PHP
// connect to the database
$DBH = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
if ($mysqli->connect_error) {
    $_SESSION[msg] .= "<br>Error connecting to MySQL host $mysql_host";
}
?>