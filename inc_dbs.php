<?php
$mysql_host      = "db-master.macallister.grass-valley.ca.us";
$mysql_slavehost = "db-slave.macallister.grass-valley.ca.us";
if ($_ENV['RINGID'] == 'MacAllister') {
    $mysql_user    = "rings";
    $mysql_pass    = "anyoneandeveryone";
    $mysql_db      = "rings";
    $ring_doc_root = '/mac/www/rings';
    $ldap_server   = "ldap.macallister.grass-valley.ca.us";
    $ldap_base     = "dc=macallister,dc=grass-valley,dc=ca,dc=us";
} elseif ($_ENV['RINGID'] == 'neudorfer') {
    $mysql_user    = "ringsvermont";
    $mysql_pass    = "gallery";
    $mysql_db      = "rings-neudorfer";
    $ring_doc_root = '/mac/www/rings';
    $ldap_server   = "ldap.neudorfer.com";
    $ldap_base     = "dc=neudorfer,dc=com";
} else {
    echo "DB Configuration Error<br>\n";
    exit;
}
?>