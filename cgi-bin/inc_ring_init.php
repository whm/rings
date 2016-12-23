<?PHP
// Start a session
session_start();

// Pull in some utility routines
require('inc_util.php');

// Load authoriziation routines
require('inc_auth_policy.php');

// Read the configuration file
if (empty($_ENV['RINGCONF'])) {
    $ring_conf = '/etc/rings/rings.conf';
} else {
    $ring_conf = $_ENV['RINGCONF'];
}
$CONF = read_conf($ring_conf);
if (empty($CONF['db_name'])) {
    $CONF['db_name'] = 'rings';
}
if (empty($CONF['db_secret'])) {
    $CONF['db_secret'] = '/etc/rings/rings_db.conf';
}
if (empty($CONF['display_size'])) {
    $CONF['default_size'] = 'raw';
}
if (empty($CONF['maint_size'])) {
    $CONF['maint_size'] = '640x480';
}
if (empty($CONF['index_size'])) {
    $CONF['index_size'] = '125x125';
}
if (empty($CONF['mail_size'])) {
    $CONF['mail_size'] = 'large';
}
if (empty($CONF['ring_admin'])) {
    $CONF['ring_admin'] = 'ring_admin';
}
if (empty($CONF['ring_id'])) {
    $CONF['ring_id'] = 'rings';
}
if (empty($CONF['picture_root'])) {
    $CONF['picture_root'] = '/srv/rings';
}
if (empty($CONF['debug'])) {
    $CONF['debug'] = 0;
}
if (empty($CONF['index_size'])) {
    $CONF['index_size'] = '125x125';
}
if (empty($CONF['cookie_id'])) {
    $CONF['cookie_id'] = 'rings-' . $CONF['ring_id'];
}

// Setup syslog
openlog('rings-' . $CONF['ring_id'], LOG_PID | LOG_PERROR, LOG_LOCAL3);

// Get database configuration and secrets
$CONF_DB = read_conf($CONF['db_secret']);

// Connect to the database and pull in some useful functions
require('inc_db_connect.php');
require('inc_db_functions.php');

// Check to see if they are trying to login
$in_login = get_request('in_login');
if (isset($in_login) && $in_login > 0) {
    http_redirect(auth_url($_SERVER['PHP_SELF']));
}

// Initialize the message session variable
if (empty($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}
?>
