<?PHP
// Open a session and check for authorization
require('inc_auth_policy.php');
require('/etc/whm/rings_dbs.php');

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL host $mysql_host";
}
$cnx_result = mysql_select_db($mysql_db);
if (!$cnx_result) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL db $mysql_db";
}

?>