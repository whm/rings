<?php
// ----------------------------------------------------------
// File: picture_file_load.php
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
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

//-------------------------------------------------------------
// unzip the file name put the contents away

function unzip_and_load ($zipfile) {

    global $stmt_info;
    global $stmt_raw;

    $tmp_dir = "/tmp/rings-".uniqid();
    $cmd_unzip = "unzip -d $tmp_dir -j -q -o $tmp_file";

    $dir = dir ($tmp_dir);
    while ($file = $dir->read()) {
        if ( preg_match('/\.jpg$/', $file) ) {

            // Store picture information and the picture
            //
            $pid = get_next('pid');
            $original_file = $file;
            $content_type = 'image/jpeg';
            $the_file_contents = fread(fopen($file,'r'), 10000000);
            $original_picture_size = strlen($the_file_contents);

            $flds = $vals = '';
            mkin ('pid',              $pid,                  'n');
            mkin ('raw_picture_size', $original_picture_size,'n');
            mkin ('file_name',        $file,                 's');
            mkin ('date_last_maint',  $a_date,               'd');
            mkin ('date_added',       $a_date,               'd');
            $cmd = "INSERT INTO pictures_information ";
            $cmd .= "($flds) VALUES ($vals) ";
            $result = $DBH->query ($cmd);
            if ($result->errno) {
                $_SESSION['msg'] .= $warn
                    . "MySQL error:" . $result->error . $em;
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
                $_SESSION['msg'] .= $warn
                    . "MySQL error:" . $result->error . $em;
            }

            echo "$pid uploaded. ";
            echo "<a href=\"picture_maint.php?in_pid=$pid\" "
                . "target=\"_blank\">Update Picture Details.</a>";
            echo "<br>\n";

            unlink ($file);
        }
    }
    exec("rm -r $tmpdir");
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

if ($_SESSION['upload_slots'] < 1) {$_SESSION['upload_slots'] = 5;}
if ($in_upload_slots < 1) {$in_upload_slots = $_SESSION['upload_slots'];}
$_SESSION['upload_slots'] = $in_upload_slots;

if (!isset($upload)) {

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
    echo "<input type=\"submit\" name=\"upload\" value=\"upload\">\n";
    echo "<input type=\"hidden\" name=\"in_upload_slots\"\n";
    echo "                       value=\"$in_upload_slots\">\n";
    echo "</form>\n";

} else {

    // -- Do the work

    $noinput = true;
    for ($i=0; $noinput && ($i<$in_upload_slots); $i++) {
        $a_file = $_FILES["in_filename_" . $i];
        if ( ($a_file != 'none') && (strlen($a_file)>0) ) {
            $noinput=false;
        }
    }
    if ($noinput) {
        echo "No Input files selected.\n";
    } else {
        $starting_pid = 0;
        echo "<h1>Upload results</h1>\n";
        echo "<p>\n";
        $noinput = true;

        for ($i=0; $i<$in_upload_slots; $i++) {
            $fileID = "in_filename_" . $i;
            $tmp_file = $_FILES[$fileID]['tmp_name'];
            if ($_FILES[$fileID]['error'] !=0
                && $_FILES[$fileID]['error'] !=4) {
                echo "Error uploading ".$_FILES[$fileID]["name"]."<br>\n";
            }
            if ((strlen($tmp_file)>0) && ($tmp_file != "none")) {

                if ( preg_match("/\.zip$/", $original_file) ) {

                    unzip_and_load ($tmp_file);

                } else {

                    $original_file      = $_FILES[$fileID]["name"];
                    $content_type       = $_FILES[$fileID]["type"];
                    $original_file_size = $_FILES[$fileID]["size"];

                    $a_date            = date("Y-m-d H:i:s");
                    $the_file_contents = fread(fopen($tmp_file,'r'), 10000000);
                    $pid               = get_next('pid');
                    $original_picture_size = strlen($the_file_contents);

                    $flds = $vals = '';
                    mkin ('pid',             $pid,               'n');
                    mkin ('raw_picture_size',strlen($original_picture_size),'n');
                    mkin ('file_name',       $original_file,     's');
                    mkin ('date_last_maint', $a_date,            'd');
                    mkin ('date_added',      $a_date,            'd');
                    $cmd = "INSERT INTO pictures_information ";
                    $cmd .= "($flds) VALUES ($vals) ";
                    $result = $DBH->query ($cmd);
                    if ($result->errno) {
                        $_SESSION['msg'] .= $warn
                            . "MySQL error:" . $DBH->error . $em;
                        $_SESSION['msg'] .= $warn . "SQL:$cmd$em";
                    }

                    $flds = $vals = '';
                    mkin ('pid',             $pid,               'n');
                    mkin ('picture_type',    $content_type,      's');
                    mkin ('picture',         $the_file_contents, 's');
                    mkin ('date_last_maint', $a_date,            'd');
                    mkin ('date_added',      $a_date,            'd');
                    $cmd = "INSERT INTO pictures_raw ($flds) VALUES ($vals) ";
                    $result = $DBH->query ($cmd);
                    if ($result->errno) {
                        $_SESSION['msg'] .= $warn . "MySQL error:" . $result->error
                            . $em;
                    }

                    echo "$pid uploaded. ";
                    echo "<a href=\"picture_maint.php?in_pid=$pid\" "
                        . "target=\"_blank\">Update Picture Details.</a>";
                    echo "<br>\n";

                }
            }
        }
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
