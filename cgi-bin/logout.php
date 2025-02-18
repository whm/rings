<?PHP

session_start();

$this_user = $_COOKIE['rings-remote-user'];

openlog("RINGS", LOG_PID | LOG_NDELAY, LOG_LOCAL0);

foreach ($_COOKIE as $key => $val) {
  if (substr($key, 0, 5) == 'rings') {
    syslog(LOG_NOTICE, "logout of $key for $this_user");
    setcookie($key,
              '',
              1,
             '/',
             'ca-zephyr.org',
             true
    );
  }
}

// Reset local credential variables
$ring_user       = true;
$ring_user_name  = $_COOKIE['rings-remote-user'];
$ring_admin      = false;
$ring_admin_name = '';

// Reset admin credential session variables
$_SESSION['ring_admin']      = $ring_admin;
$_SESSION['ring_admin_name'] = $ring_admin_name;
    
$next_script = htmlspecialchars($_GET['next']);
$next_url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $next_script;
header("location: $next_url");
?>
