<?php
// ----------------------------------------------------------
// File: picture_load.php
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_upload       = get_request('in_upload');
$in_upload_slots = get_request('in_upload_slots');

?>

<html>
<head>
<title>Load a Picture</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Load Pictures into the Rings';
require ('page_top.php');

// -- main routine

if ($_SESSION['upload_slots'] < 1) {
    $_SESSION['upload_slots'] = 5;
}
if ($in_upload_slots < 1) {
    $in_upload_slots = $_SESSION['upload_slots'];
}
$_SESSION['upload_slots'] = $in_upload_slots;

if (empty($in_upload)) {

    // -- Display slots from
    echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
    echo "  <font size=\"-1\" face=\"Arial, Helvetica, sans-serif\">\n";
    echo "   Number of Pictures to upload at once: \n";
    echo "  <input type=\"text\" size=\"3\"\n";
    echo "         value=\"$in_upload_slots\"\n";
    echo "         name=\"in_upload_slots\">\n";
    echo "  <input type=\"submit\" name=\"Refresh\" value=\"Refresh\">\n";
    echo "  </font>\n";
    echo "</form>\n";
    echo "<p>\n";

    // -- Display the upload form

    echo "<form enctype=\"multipart/form-data\" method=\"post\" ";
    echo 'action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
    echo "<table border=\"1\">\n";
    echo "<tr>\n";
    echo " <th><font face=\"Arial, Helvetica, sans-serif\">\n";
    echo "     Picture File Name</font></th>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    for ($i=0; $i<$in_upload_slots; $i++) {
        echo "<tr>\n";
        echo " <td>\n";
        echo "  <font size=\"-1\" face=\"Arial, Helvetica, sans-serif\">\n";
        echo "  <input type=\"file\" size=\"60\" name=\"in_filename_$i\">\n";
        echo "  </font>\n";
        echo " </td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "<input type=\"submit\" name=\"Upload\" value=\"Upload\">\n";
    echo "<input type=\"hidden\" name=\"in_upload\" value=\"in_upload\">\n";
    echo "<input type=\"hidden\" name=\"in_upload_slots\"\n";
    echo "                       value=\"$in_upload_slots\">\n";
    echo "</form>\n";

} else {

    // -- Do the work

    echo "<h1>Upload results</h1>\n";
    echo "<p>\n";

    for ($i=0; $i<$in_upload_slots; $i++) {
        $fld_name = "in_filename_" . $i;
        $tmp_file  = $_FILES[$fld_name]['tmp_name'];
        if (empty($tmp_file)) {
            continue;
        }
        $upload_status = accept_and_store($fld_name, 0);
        if (!empty($upload_status)) {
            $slot = $i + 1;
            echo msg_err("Problem uploading file $slot");
        }
    }
}

sys_display_msg();

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
