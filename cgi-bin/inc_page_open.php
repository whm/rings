<?PHP
// Open a session, perform authorization check, and include authorization 
// routines unique to the rings.
session_start();
require('inc_auth_policy.php');

if (!isset($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
}
?>