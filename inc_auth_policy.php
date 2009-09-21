<?php

if ($in_logout>0) {
    session_destroy();
    $_SESSION['whm_directory_user'] = '';
    http_redirect('http://www.stanford.edu');
}

whm_auth('rings');

?>