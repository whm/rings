<?PHP
// Start a session
session_start();

// Load authoriziation routines
require('inc_auth_policy.php');

// Get database configuration and secrets
require('/etc/whm/rings_dbs.php');

// Connect to the database and pull in some useful functions
require('inc_db_connect.php');
require('inc_db_functions.php');

// Pull in some utility routines
require('inc_util.php');

// Check to see if they arey trying to login
$in_login = get_request('in_login');
if (isset($in_login) && $in_login > 0) {
    http_redirect(auth_url($_SERVER['PHP_SELF']));
}

// Initialize the message session variable
if (!isset($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}
?>
