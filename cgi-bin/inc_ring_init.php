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
// Main Routine
// ---------------------------------------------------------------------

// Start a session
session_start();

// Pull in some utility routines
require('inc_util.php');

// Load authoriziation routines
require('inc_auth_policy.php');

// Read the configuration file
$ring_conf = apache_getenv('RINGCONF');
if (empty($ring_conf)) {
    $ring_conf = '/etc/rings/rings.conf';
}
$CONF = read_conf($ring_conf);
set_default('button_position',  'top');
set_default('cookie_id',        'rings-cookie');
set_default('db_name',          'rings');
set_default('db_secret',        '/etc/rings/rings_db.conf');
set_default('debug',            0);
set_default('display_size',     'raw');
set_default('index_size',       '125x125');
set_default('krb_keytab',       '/etc/krb5.keytab');
set_default('krb_principal',    '-U');
set_default('mail_domain',      'ca-zephyr.org');
set_default('mail_size',        '800x600');
set_default('maint_size',       '640x480');
set_default('picture_root',     '/srv/rings');
set_default('ring_admin',       false);
set_default('ring_id',          'rings');

// Setup syslog
openlog('rings-' . $CONF['ring_id'], LOG_PID | LOG_PERROR, LOG_LOCAL3);

// Get database configuration and secrets
$CONF_DB = read_conf($CONF['db_secret']);
if (is_null($CONF_DB)) {
  print("<br/>\n ERROR: Database connection error.\n </br>\n");
  $err_msg = 'ERROR: missing file ' . $CONF['db_secret'];
  syslog(LOG_ERR, $err_msg);
  exit('ERROR: FATAL');
}

// Connect to the database and pull in some useful functions
require('inc_db_connect.php');
require('inc_db_functions.php');

// Check to see if they are trying to login
$in_login = get_request('in_login');
if (!empty($in_login) && $in_login > 0) {
    http_redirect(auth_url($_SERVER['PHP_SELF']));
}

// Initialize the message session variable
if (empty($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}

// Set the user logged in flag
$ring_user = false;
if (isset($_COOKIE['rings-remote-user'])) {
  $ring_user      = true;
  $ring_user_name = $_COOKIE['rings-remote-user'];
}
  
// Set the admin flag
$ring_admin      = false;
$ring_admin_name = '';
if (isset($_SESSION['ring_admin'])) {
    $ring_admin =      $_SESSION['ring_admin'];
    $ring_admin_name = $_SESSION['ring_admin_name'];
} elseif ($ring_user) {
    $cmd = '';
    $cmd .= '/usr/bin/k5start -q ';
    $cmd .= ' -f ' . $CONF['krb_keytab'];
    $cmd .= ' ' . $CONF['krb_principal'];
    $cmd .= ' -- ';
    $cmd .= '/usr/bin/ring-admin ' . $_COOKIE['rings-remote-user'];
    $ring_admin_name = shell_exec($cmd);
    if (!empty($ring_admin_name)) {
        $ring_admin                  = true;
        $_SESSION['ring_admin']      = $ring_admin;
        $_SESSION['ring_admin_name'] = $ring_admin_name;
    } else {
        $ring_admin                  = false;
        $_SESSION['ring_admin']      = '';
        $_SESSION['ring_admin_name'] = '';
    }
}
?>
