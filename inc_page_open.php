<?PHP
// Open a session and check for authorization
require('pi_php_sessions.inc');
require('pi_php_auth.inc');
pi_auth("rings|user");
?>
