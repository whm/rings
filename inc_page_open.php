<?PHP
// Open a session and check for authorization
require('whm_php_sessions.inc');
require('whm_php_auth.inc');
whm_auth("rings|user");
?>