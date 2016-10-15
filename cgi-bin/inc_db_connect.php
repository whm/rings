<?PHP
// connect to the database
$DBH = new mysqli($CONF['db_host'],
                  $CONF_DB['db_user'],
                  $CONF_DB['db_password'],
                  $CONF['db_name']);
if ($DBH->connect_errno) {
    syslog(LOG_ERR, 'ERROR: connecting to MySQL host ' . $CONF['db_name']);
    syslog(LOG_ERR, 'MySQL ERROR: ' . $DBH->connect_error);
    exit("Problem connection to database");
}
?>