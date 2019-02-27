<?PHP
// ---------------------------------------------------------------------
// Helper routine to set initial configuration values
function set_default ($fld, $val) {
    global $CONF;
    if (empty($CONF[$fld])) {
        $CONF[$fld] = $val;
    }
    return;
}

// ---------------------------------------------------------------------
// Routine to set session variables from environment variables.
// Generally these are values from the load balancer.
function set_session2env($fld, $env) {
    global $CONF;
    $val = '';
    if (! empty($_SERVER[ $CONF[$env] ])) {
        $val = $_SERVER[ $CONF[$env] ];
    }
    $_SESSION[$fld] = $val;
    return;
}

// Create a url that will display the current page using ssl.  This
// allows an apache configuration that uses webauth for
// authentication to form url's correctly.
function ring_url() {
    global $CONF;
    $new_url = $CONF['ring_server_url'] . '/' . $_SERVER['PHP_SELF'];
    return $new_url;
}

// ---------------------------------------------------------------------
// Main Routine
// ---------------------------------------------------------------------

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
set_default('cookie_id',        'rings-cookie');
set_default('db_name',          'rings');
set_default('db_secret',        '/etc/rings/rings_db.conf');
set_default('debug',            0);
set_default('display_size',     'raw');
set_default('env_givenname',    'HTTP_X_WEBAUTH_LDAP_GIVENNAME');
set_default('env_groups',       'HTTP_X_WEBAUTH_LDAP_CZPRIVILEGEGROUP');
set_default('env_mail',         'HTTP_X_WEBAUTH_LDAP_MAIL');
set_default('env_remote_user',  'HTTP_X_WEBAUTH_USER');
set_default('env_sn',           'HTTP_X_WEBAUTH_LDAP_SN');
set_default('index_size',       '125x125');
set_default('ldap_server',      'macdir.ca-zephyr.org');
set_default('mail_domain',      'ca-zephyr.org');
set_default('mail_size',        '800x600');
set_default('maint_size',       '640x480');
set_default('picture_root',     '/srv/rings');
set_default('ring_admin_attr',  'czPrivilegeGroup');
set_default('ring_admin_group', 'ring:admin');
set_default('ring_id',          'rings');
set_default('ring_keytab',      '/NOKEYTAB');
set_default('ring_princ',       'service/rings');
set_default('ring_server',      '');
set_default('ring_server_url',  '');
set_default('server_admin',     'Bill MacAllister');

// Set the ring server.  Use the load balancer name if it is
// available.  Otherwise use the local server name.
if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
    $CONF['ring_server'] = $_SERVER['HTTP_X_FORWARDED_SERVER'];
} else {
    $CONF['ring_server'] = $_SERVER['SERVER_NAME'];
}
if (!empty($_SESSION['env_remote_user'])) {
  $url_prefix = 'HTTPS://';
} else {
  $url_prefix = 'HTTP://';
}
$CONF['ring_server_url'] = $url_prefix . $CONF['ring_server'];

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
    http_redirect(auth_url());
}

// Initialize the message session variable
if (empty($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}

// set session variables from the environment 
set_session2env('user_givenname', 'env_givenname');
set_session2env('user_groups',    'env_groups');
set_session2env('user_mail',      'env_mail');
set_session2env('remote_user',    'env_remote_user');
set_session2env('user_sn',        'env_sn');

// Set the admin flag
$_SESSION['ring_admin'] = 0;
$privs = explode('|', $_SERVER[ $CONF['env_groups'] ]);
foreach ($privs as $p) {
    if ($p == $CONF['ring_admin_group']) {
        $_SESSION['ring_admin'] = 1;
        break;
    }
}

?>
