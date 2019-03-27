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
if (empty($_ENV['RINGCONF'])) {
    $ring_conf = '/etc/rings/rings.conf';
} else {
    $ring_conf = $_ENV['RINGCONF'];
}
$CONF = read_conf($ring_conf);
set_default('cookie_id',    'rings-cookie');
set_default('db_name',      'rings');
set_default('db_secret',    '/etc/rings/rings_db.conf');
set_default('debug',            0);
set_default('display_size',     'raw');
set_default('index_size',       '125x125');
set_default('ldap_base',        'dc=macallister,dc=grass-valley,dc=ca,dc=us');
set_default('ldap_server',      'ldap.ca-zephyr.org');
set_default('mail_domain',      'ca-zephyr.org');
set_default('mail_size',        '800x600');
set_default('maint_size',       '640x480');
set_default('picture_root',     '/srv/rings');
set_default('ring_admin',       'ring_admin');
set_default('ring_admin_attr',  'czPrivilegeGroup');
set_default('ring_admin_group', 'ring:admin');
set_default('ring_id',          'rings');
set_default('ring_keytab',      '/NOKEYTAB');
set_default('ring_princ',       'service/rings');

// Setup syslog
openlog('rings-' . $CONF['ring_id'], LOG_PID | LOG_PERROR, LOG_LOCAL3);

// Get database configuration and secrets
$CONF_DB = read_conf($CONF['db_secret']);

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

// Set the admin flag
$ring_admin_group = '';
if (! empty($_SESSION['ring_admin_group'])
    && $_SESSION['ring_admin_group'] > 0)
{
    $ring_admin_group = $_SESSION['ring_admin_group'];
} else {
    $c = 1;
    while (1==1) {
        $this_id = 'WEBAUTH_LDAP_CZPRIVILEGEGROUP' . $c;
        if (empty($_SERVER[$this_id])) {
            break;
        }
        if ($_SERVER[$this_id] == $CONF['ring_admin_group']) {
            $ring_admin_group = $CONF['ring_admin_group'];
            break;
        }
        $c = $c + 1;
    }
}
$_SESSION['ring_admin_group'] = $ring_admin_group;
?>
