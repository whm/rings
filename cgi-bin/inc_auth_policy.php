<?php

// ----------------------------------------------------------
// Look up people and see if any are invisible.
function auth_picture_invisible ($pid) {
    global $CONF;
    global $DBH;
    $hide_picture = 1;
    if (!empty($_SERVER['REMOTE_USER'])) {
        $hide_picture = 0;
    } else {
        $sel = "SELECT count(*) FROM picture_details pd ";
        $sel .= "JOIN people_or_places pop ON (pop.uid = pd.uid) ";
        $sel .= "WHERE pd.pid = ? ";
        $sel .= "AND pop.visibility = 'INVISIBLE' ";
        if ($CONF['debug']) {
            syslog(LOG_DEBUG, $sel);
        }
        if (!$sth = $DBH->prepare($sel)) {
            sys_err('Prepare failed: ' . $DBH->errno . '-' . $DBH->error);
            sys_err("Problem statement: $sel");
            return 1;
        }
        $sth->bind_param('i', $pid);
        if (!$sth->execute()) {
            sys_err('Execute failed: ' . $DBH->errno . '-' . $DBH->error);
            sys_err("Problem statement: $cmd");
            return 1;
        }
        $sth->bind_result($p1);
        if ($sth->fetch()) {
            if ($p1 == 0) {
                $hide_picture = 0;
            }
        }
        $sth->close();
    }
    return $hide_picture;
}

// Look up a person and see if they are to be displayed.
function auth_person_hidden ($uid) {
    global $CONF;
    global $DBH;
    if (!empty($_SERVER['REMOTE_USER'])) {
        $hide_person = 0;
    } else {
        $hide_person = 0;
        $sel = "SELECT count(*) FROM people_or_places ";
        $sel .= "WHERE uid = ? ";
        $sel .= "AND (visibility = 'INVISIBLE' ";
        $sel .=      "OR visibility = 'HIDDEN') ";
        if ($CONF['debug']) {
            syslog(LOG_DEBUG, $sel);
        }
        if (!$sth = $DBH->prepare($sel)) {
            sys_err('Prepare failed: ' . $DBH->errno . '-' . $DBH->error);
            sys_err("Problem statement: $sel");
            return 1;
        }
        $sth->bind_param('s', $uid);
        if (!$sth->execute()) {
            sys_err('Execute failed: ' . $DBH->errno . '-' . $DBH->error);
            sys_err("Problem statement: $cmd");
            return 1;
        }
        $sth->bind_result($p1);
        if ($sth->fetch()) {
            if ($p1 > 0) {
                $hide_person = 1;
            }
        }
        $sth->close();
    }
    return $hide_person;
}

// Create a url that will display the current page using ssl.This
// allows an apache configuration that uses webauth for
// authentication.
function auth_url($url) {
    global $CONF;
    global $DBH;
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
