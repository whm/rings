<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_pid  = $_REQUEST['in_pid'];
$in_setdate  = $_REQUEST['in_setdate'];
$in_button_find  = $_REQUEST['in_button_find'];
$upload = $_REQUEST['upload'];
// ----------------------------------------------------------
//

// File: picture_load.php
// Author: Bill MacAllister

require ('inc_page_open.php');

?>

<html>
<head>
<title>Re-Load a Picture</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Re-load a Picture into the Rings';
require ('page_top.php');

$ok   = '<font color="green">';
$warn = '<font color="red">';
$em   = "</font><br>\n";

// -- main routine

openlog($_SERVER['PHP_SELF'], LOG_PID | LOG_PERROR, LOG_LOCAL0);

// database pointers
require ('/etc/whm/rings_dbs.php');

// connect to the database
$cnx = mysqli_connect ( $mysql_host, $mysql_user, $mysql_pass, $mysql_db );
if (!$cnx) {
    $_SESSION['msg'] .= "<br>Error connecting to MySQL host $mysql_host";
}

?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<table border="0">
<tr><td>Picture ID:</td>
    <td>
    <input type="text" name="in_pid" value="<?php echo $in_pid;?>" size="8">
    </td>
</tr>
<tr><td colspan="2" align="center">
    <input type="submit" name="in_button_find" value="Find Picture">
    </td>
</tr>
</table>

<?php if ($in_pid > 0) { ?>
<img src="/rings/display.php?in_pid=<?php echo $in_pid;?>&in_size=large"><br>
<?php } ?>

</form>

<?php

if ($in_pid > 0) {
    if (!isset($upload)) {

        // -- Display the upload form

        echo "<form enctype=\"multipart/form-data\" method=\"post\" ";
        echo 'action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
        echo '<input type="hidden" name="in_pid" value="'.$in_pid.'">'."\n";
        echo "<table border=\"1\">\n";
        echo "<tr>\n";
        echo " <th><font face=\"Arial, Helvetica, sans-serif\">\n";
        echo "     Picture File Name</font></th>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<tr>\n";
        echo " <td>\n";
        echo "  <font size=\"-1\" face=\"Arial, Helvetica, sans-serif\">\n";
        echo "  <input type=\"file\" size=\"60\" name=\"in_filename\">\n";
        echo "  </font>\n";
        echo " </td>\n";
        echo "</tr>\n";
        echo "</table>\n";
        echo '<input type="checkbox" name="in_setdate" value="Y">'
            . 'Set Date from Picture Information<br>'."\n";
        echo "<input type=\"submit\" name=\"upload\" value=\"Upload\">\n";
        echo "</form>\n";

    } else {
        
        // -- Do the work

        $file_id = 'in_filename';
        $tmp_file = $_FILES[$file_id]['tmp_name'];
        if ($_FILES[$file_id]['error'] !=0
            && $_FILES[$file_id]['error'] !=4) {
            echo "Error uploading ".$_FILES[$file_id]["name"]."<br>\n";
        }
        if (!isset($tmp_file) || strlen($tmp_file) == 0) {
            $_SESSION['msg'] .= "<br>$warn No file uploaded.</font>\n";
        } else {
            echo "<h1>Upload results</h1>\n";
            echo "<p>\n";
            $original_file      = $_FILES[$fileID]["name"];
            $content_type       = $_FILES[$fileID]["type"];
            $original_file_size = $_FILES[$fileID]["size"];
            $a_date  = date("Y-m-d H:i:s");
            $z = strrpos ($original_file, ".");
            $tmp = substr ($original_file, 0, $z);
            
            $the_file_contents = fread(fopen($tmp_file,'r'), 5000000);

            $cmd = "UPDATE pictures_information SET ";
            $cmd .= "raw_picture_size = ".strlen($the_file_contents).", ";
            $cmd .= "date_last_maint = NOW() ";
            $cmd .= "WHERE pid = $in_pid ";
            $sth = mysqli_prepare ($cnx, $cmd);
            if (mysqli_errno($cnx)) {
                $_SESSION['msg'] .= $warn."MySQL prepare error:".
                    mysqli_error($cnx).$em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }
            mysqli_stmt_execute($sth);
            if (mysqli_errno($cnx)) {
                $_SESSION['msg'] .= $warn."MySQL execute error:".
                    mysqli_error($cnx).$em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }

            $cmd = "INSERT INTO pictures_raw SET ";
            $cmd .= "pid = $in_pid, ";
            $cmd .= "picture_type = '$content_type', ";
            $cmd .= "picture = ?, ";
            $cmd .= "date_last_maint = NOW(), ";
            $cmd .= "date_added = NOW() ";
            $cmd .= "ON DUPLICATE KEY UPDATE ";
            $cmd .= "picture_type = '$content_type', ";
            $cmd .= "picture = ?, ";
            $cmd .= "date_last_maint = NOW() ";
            $sth = mysqli_prepare ($cnx, $cmd);
            if (mysqli_errno($cnx)) {
                $_SESSION['msg'] .= $warn."MySQL prepare error:".
                    mysqli_error($cnx).$em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }
            mysqli_stmt_bind_param($sth, "ss",
                                   $the_file_contents,
                                   $the_file_contents);
            mysqli_stmt_execute($sth);
            if (mysqli_errno($cnx)) {
                $_SESSION['msg'] .= $warn."MySQL execute error:".
                    mysqli_error($cnx).$em;
                $_SESSION['msg'] .= $warn."SQL:$cmd$em";
            }
            
            echo "$in_pid uploaded. ";
            echo "<a href=\"picture_maint.php?in_pid=$in_pid\" "
                . "target=\"_blank\">Update Picture Details.</a>";
            echo "<br>\n";
            
            unlink ($tmp_file);
            
            $sh_cmd = "/usr/bin/perl /usr/bin/ring-resize.pl";
            $sh_cmd .= " --start=$in_pid";
            $sh_cmd .= " --end=$in_pid";
            $sh_cmd .= " --host=$mysql_host";
            $sh_cmd .= " --user=$mysql_user";
            $sh_cmd .= " --db=$mysql_db";
            $sh_cmd .= " --update";
            if (strlen($in_setdate)>0) {$sh_cmd .= " --dateupdate";}
            syslog(LOG_INFO, "Executing:$sh_cmd");
            $sh_cmd .= " --pass=$mysql_pass";
            system($sh_cmd);
        }
    }
}

mysqli_close($cnx);

if (strlen($_SESSION['msg']) > 0) {
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
}

?>

<?php require('page_bottom.php'); ?>
</body>
</html>
