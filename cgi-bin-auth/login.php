<?PHP

session_start();

# 30 day expiration
$auth_expiration = time() + 60*60*24*30;

openlog("RINGS", LOG_PID | LOG_NDELAY, LOG_LOCAL0);
syslog(LOG_NOTICE, 'login for ' . $_SERVER['REMOTE_USER']);

setcookie('rings-remote-user',
           $_SERVER['REMOTE_USER'],
           $auth_expiration,
          '/',
          'ca-zephyr.org',
          true
);

$next_script = htmlspecialchars($_GET['next']);
$next_url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $next_script;
header("location: $next_url");
?>
