<?php
// ----------------------------------------------------------
// File: picture_load.php
// Author: Bill MacAllister

require ('inc_page_open.php');
require('inc_util.php');

// Form or URL inputs
$in_upload       = get_request('in_upload');
$in_upload_slots = get_request('in_upload_slots');
$in_type         = get_request('in_type');

//-------------------------------------------------------------
// construct an flds and values for an insert
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function mkin ($a_fld, $a_val, $in_type) {

    global $DBH;
    global $flds;
    global $vals;

    if (strlen($a_val) > 0) {
        $c = "";
        if (strlen($flds) > 0) {$c = ",";}
        $flds .= $c . $a_fld;
        if ( $in_type != "n" ) {
            $a_val = $DBH->real_escape_string($a_val);
            $vals .= $c . "'$a_val'";
        } else {
            $vals .= $c . $a_val;
        }
    }

    return;
}
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

$ok   = '<font color="green">';
$warn = '<font color="red">';
$em   = "</font><br>\n";

// -- main routine

openlog($_SERVER['PHP_SELF'], LOG_PID | LOG_PERROR, LOG_LOCAL0);

if ($_SESSION['upload_slots'] < 1) {$_SESSION['upload_slots'] = 5;}
if ($in_upload_slots < 1) {$in_upload_slots = $_SESSION['upload_slots'];}
$_SESSION['upload_slots'] = $in_upload_slots;

// database pointers
require('/etc/whm/rings_dbs.php');
require('inc_db_connect.php');
require('inc_db_functions.php');

if (!isset($in_upload)) {

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

    $noinput = true;
    $starting_pid = 0;
    echo "<h1>Upload results</h1>\n";
    echo "<p>\n";

    for ($i=0; $i<$in_upload_slots; $i++) {
        $file_id = "in_filename_" . $i;
        $tmp_file = $_FILES[$file_id]['tmp_name'];
        if ($_FILES[$file_id]['error'] !=0
            && $_FILES[$file_id]['error'] !=4) {
            echo "Error uploading ".$_FILES[$file_id]["name"]."<br>\n";
        }
        if (!isset($tmp_file) || strlen($tmp_file) == 0) {
            continue;
        }
        $original_file      = $_FILES[$file_id]["name"];
        $content_type       = $_FILES[$file_id]["type"];
        $original_file_size = $_FILES[$file_id]["size"];
        $a_date  = date("Y-m-d H:i:s");
        $z = strrpos ($original_file, ".");
        $tmp = substr ($original_file, 0, $z);

        $the_file_contents = fread(fopen($tmp_file,'r'), 5000000);

        $pid = get_next('pid');

        $flds = $vals = '';

        mkin ('pid',             $pid,               'n');
        mkin ('raw_picture_size',strlen($the_file_contents),'n');
        mkin ('file_name',       $original_file,     's');
        mkin ('date_last_maint', $a_date,            'd');
        mkin ('date_added',      $a_date,            'd');
        $cmd = "INSERT INTO pictures_information ";
        $cmd .= "($flds) VALUES ($vals) ";
        $result = $DBH->query ($cmd);
        if ($result->errno) {
            $_SESSION['msg'] .= $warn . 'MySQL error:' . $result->error .$em;
            $_SESSION['msg'] .= $warn . "SQL:$cmd$em";
        }

        $flds = $vals = '';
        mkin ('pid',             $pid,               'n');
        mkin ('picture_type',    $content_type,      's');
        mkin ('picture',         $the_file_contents, 's');
        mkin ('date_last_maint', $a_date,            'd');
        mkin ('date_added',      $a_date,            'd');
        $cmd = "INSERT INTO pictures_raw ($flds) VALUES ($vals) ";
        $result = $DBH->query($cmd);
        if ($result->errno) {
            $_SESSION['msg'] .= $warn . 'MySQL error:' . $result->error . $em;
        }

        echo "$pid uploaded. ";
        echo "<a href=\"picture_maint.php?in_pid=$pid\" "
            . "target=\"_blank\">Update Picture Details.</a>";
        echo "<br>\n";

        unlink ($tmp_file);

        if ($starting_pid==0) {$starting_pid = $pid;}
    }
    if ($starting_pid > 0) {
        $sh_cmd = "/usr/bin/perl /usr/bin/ring-resize.pl";
        $sh_cmd .= " --start=$starting_pid";
        $sh_cmd .= " --host=$mysql_host";
        $sh_cmd .= " --user=$mysql_user";
        $sh_cmd .= " --db=$mysql_db";
        $sh_cmd .= " --update";
        $sh_cmd .= " --dateupdate";
        syslog(LOG_INFO, "Executing:$sh_cmd");
        $sh_cmd .= " --pass=$mysql_pass";
        system($sh_cmd);
    }
    echo "<p>\n";
    echo "<a href=\"picture_load.php\">Back to Load Files</a>\n";
}

if (strlen($_SESSION['msg']) > 0) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
