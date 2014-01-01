<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_upload_slots  = $_REQUEST['in_upload_slots'];
$in_type  = $_REQUEST['in_type'];
// ----------------------------------------------------------
//

// File: picture_load.php
// Author: Bill MacAllister

require ('inc_page_open.php');

//-------------------------------------------------------------
// construct an flds and values for an insert
//
//  $in_type == "n" is a number
//  $in_type != "n" anything else is a string

function mkin ($cnx, $a_fld, $a_val, $in_type) {
    
    global $flds;
    global $vals;
    
    if (strlen($a_val) > 0) {
        $c = "";
        if (strlen($flds) > 0) {$c = ",";}
        $flds .= $c . $a_fld;
        if ( $in_type != "n" ) {
            $a_val = mysql_real_escape_string($a_val, $cnx);
            $vals .= $c . "'$a_val'";
        } else {
            $vals .= $c . $a_val;
        }
    }
    
    return;
}

//-------------------------------------------------------------
// get the next id

function get_next ($cnx, $id) {
    
    global $warn, $em;

    $return_number = 0;

    $sel = "SELECT next_number FROM next_number WHERE id='$id' ";
    $result = mysql_query ($sel,$cnx);
    if (mysql_errno()) {
        $_SESSION['msg'] .= $warn."MySQL error:".mysql_error().$em;
        $_SESSION['msg'] .= "Problem SQL:$sel<br>\n";
    } else {
        if ($result) {
            $row = mysql_fetch_array ($result);
            $return_number = $row["next_number"];
        }
    }
    if ($return_number > 0) {
        $nxt = $return_number + 1;
        $cmd = "UPDATE next_number SET next_number=$nxt WHERE id='$id' ";
        $result = mysql_query ($cmd,$cnx);
        if (mysql_errno()) {
            $_SESSION['msg'] .= $warn."MySQL error:".mysql_error().$em;
            $_SESSION['msg'] .= "Problem SQL:$cmd<br>\n";
        }
    } else {
        $nxt = 1;
        $cmd = "INSERT INTO  next_number (id,next_number) ";
        $cmd .= "VALUES ('$id',$nxt) ";
        $result = mysql_query ($cmd,$cnx);
        if (mysql_errno()) {
            $_SESSION['msg'] .= $warn."MySQL error:".mysql_error().$em;
            $_SESSION['msg'] .= "Problem SQL:$cmd<br>\n";
        } else {
            if ($result) {
                $return_number = $nxt;
            }
        }
    }

    return $return_number;

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

// database pointers
require ('/etc/whm/rings_dbs.php');

// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL host $mysql_host";
}
$result = mysql_select_db($mysql_db);
if (!$result) {
    $_SESSION['s_msg'] .= "<br>Error connecting to MySQL db $mysql_db";
}

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
        $a_file = $_FILES["in_filename_$i"] . '';
        if ( ($a_file != 'none') && (strlen($a_file)>0) ) {
            $noinput=false;
            $_SESSION['msg'] .= "$a_file ";
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
                $original_file      = $_FILES[$fileID]["name"];
                $content_type       = $_FILES[$fileID]["type"];
                $original_file_size = $_FILES[$fileID]["size"];
                $a_date  = date("Y-m-d H:i:s");
                $z = strrpos ($original_file, ".");
                $tmp = substr ($original_file, 0, $z);
                
                $the_file_contents = fread(fopen($tmp_file,'r'), 5000000);
                
                $pid = get_next($cnx, "pid");
                
                $flds = $vals = '';

                mkin ($cnx, 'pid',             $pid,               'n');
                mkin ($cnx, 'raw_picture_size',strlen($the_file_contents),'n');
                mkin ($cnx, 'file_name',       $original_file,     's');
                mkin ($cnx, 'date_last_maint', $a_date,            'd');
                mkin ($cnx, 'date_added',      $a_date,            'd');
                $cmd = "INSERT INTO pictures_information ";
                $cmd .= "($flds) VALUES ($vals) ";
                $result = mysql_query ($cmd, $cnx);
                if (mysql_errno()) {
                    $_SESSION['msg'] .= $warn."MySQL error:".mysql_error().$em;
                    $_SESSION['msg'] .= $warn."SQL:$cmd$em";
                }
                
                $flds = $vals = '';
                mkin ($cnx, 'pid',             $pid,               'n');
                mkin ($cnx, 'picture_type',    $content_type,      's');
                mkin ($cnx, 'picture',         $the_file_contents, 's');
                mkin ($cnx, 'date_last_maint', $a_date,            'd');
                mkin ($cnx, 'date_added',      $a_date,            'd');
                $cmd = "INSERT INTO pictures_raw ($flds) VALUES ($vals) ";
                $result = mysql_query ($cmd, $cnx);
                if (mysql_errno()) {
                    $_SESSION['msg'] .= $warn."MySQL error:".mysql_error().$em;
                }
                
                echo "$pid uploaded. ";
                echo "<a href=\"picture_maint.php?in_pid=$pid\" "
                    . "target=\"_blank\">Update Picture Details.</a>";
                echo "<br>\n";

                //        unlink ($tmp_file);
                echo "the_file_contents:".strlen($the_file_contents)."<br>\n";
                echo "tmp_file:$tmp_file<br>\n";

                if ($starting_pid==0) {$starting_pid = $pid;}
            }
        }
        $sh_cmd = "/usr/bin/perl /usr/bin/ring-resize.pl";
        $sh_cmd .= " --start=$starting_pid";
        $sh_cmd .= " --host=$mysql_host";
        $sh_cmd .= " --user=$mysql_user";
        $sh_cmd .= " --db=$mysql_db";
        $sh_cmd .= " --update";
        $sh_cmd .= " --dateupdate";
        echo "Executing command:$sh_cmd<br>\n";
        $sh_cmd .= " --pass=$mysql_pass";
        system($sh_cmd);
    }
    echo "<p>\n";
    echo "<a href=\"picture_load.php\">Back to Load Files</a>\n";
}

mysql_close($cnx);

if (strlen($_SESSION['msg']) > 0) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
