<?php
//
// Open a session check authorization
$whm_base = '/usr/share/whm-ldapauth-php/cgi-bin';
require("$whm_base/whm_php_auth.inc");

// Form or URL input
$in_logout = isset($_REQUEST['in_logout']) ? $_REQUEST['in_logout'] : NULL;

// ----------------------------------------------------------
// Look up people and see if any are invisible.
function auth_picture_invisible ($pid) {
    global $DBH;
    if (isset($_SESSION['whm_directory_user'])) {
        $hide_picture = 0;
    } else {
        $sel = "SELECT count(*) hidden_count FROM picture_details pd ";
        $sel .= "JOIN people_or_places pop ON (pop.uid = pd.uid) ";
        $sel .= "WHERE pid=$pid ";
        $sel .= "AND pop.visibility = 'INVISIBLE' ";
        $result = $DBH->query($sel);
        if (!$result) {
            $_SESSION['msg'] .= 'ERROR: ' . $result->error . "<br>\n";
            $_SESSION['msg'] .= "SQL: $sel<br>\n";
            $hide_picture = 1;
        } else {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ($row['hidden_count'] > 0) {
                    $hide_picture = 1;
                }
                break;
            }
        }
    }
    return $hide_picture;
}

// Look up a person and see if they are to be displayed.
function auth_person_hidden ($uid) {
    global $DBH;
    if (isset($_SESSION['whm_directory_user'])) {
        $hide_person = 0;
    } else {
        $sel = "SELECT count(*) hidden_count FROM people_or_places ";
        $sel .= "WHERE uid='$uid' ";
        $sel .= "AND (visibility = 'INVISIBLE' ";
        $sel .=      "OR visibility = 'HIDDEN') ";
        $result = $DBH->query($sel);
        if (!$result) {
            $_SESSION['msg'] .= 'ERROR: ' . $result->error . "<br>\n";
            $_SESSION['msg'] .= "SQL: $sel<br>\n";
            $hide_picture = 1;
        } else {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ($row['hidden_count'] > 0) {
                    $hide_person = 1;
                }
                break;
            }
        }
    }
    return $hide_person;
}

// Redirect the user to the home page
function http_redirect ($nextURL="index.php") {
    header ("REFRESH: 0; URL=$nextURL");
    echo "<html>\n";
    echo "<head>\n";
    echo "<title>Rings</title>\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1><a href=\"$nextURL\">Rings</a></h1>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

if ($in_logout>0) {
    session_destroy();
    $_SESSION['whm_directory_user'] = '';
    http_redirect('http://rings.ca-zephyr.org/rings');
}

if (!isset($authNotRequired)) {
    whm_auth('rings');
}
?>
