<?php

// ----------------------------------------------------------
// Look up people and see if any are invisible.
function auth_picture_invisible ($pid) {
    global $DBH;
    if (isset($_SERVER['REMOTE_USER'])) {
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
    if (isset($_SERVER['REMOTE_USER'])) {
        $hide_person = 0;
    } else {
        $hide_person = 0;
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

// Create a url that will display the current page using ssl.This
// allows an apache configuration that uses webauth for
// authentication.
function auth_url($url) {
    $new_url = $url;
    if (substr($new_url, 0, 7) == 'http://') {
        $new_url = substr($new_url, 7);
    }
    if (substr($new_url, 0, 8) != 'https://') {
        if (substr($new_url, 0, 1) != '/') {
            $new_url = '/' . $new_url;
        }
        $new_url = 'https://' . $_SERVER['HTTP_HOST'] . $new_url;
    }
    return $new_url;
}

?>
