<?php
// Display a picture

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_sig = get_request('in_signature');

// database pointers
require ('/etc/whm/rings_dbs.php');
require ('inc_db_connect.php');

$sel = "SELECT file_path ";
$sel .= "FROM tmp_matching ";
$sel .= "WHERE signature = '$in_sig' ";
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $this_path = $row['file_path'];
    }
}

header("Content-type: image/jpeg");
readfile($this_path);

?>
