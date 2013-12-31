<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_pid  = $_REQUEST['in_pid'];
$in_grade  = $_REQUEST['in_grade'];
$in_username  = $_REQUEST['in_username'];
// ----------------------------------------------------------
//
// File: get_VOTE.php
// Author: Bill MacAllister

?>
<html>
<head>
<title>Vote for a Grade</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">

<script language="JavaScript">
function closeWindow() {
    window.close();
    return true;
}
</script>

</head>

<body class="vs">

<h3>Set Picture Grade</h3>
<form name="selectGrade">
<table border="0">

<tr><td align="center"><input type="radio" name="in_grade" value="A">A</td>
    <td rowspan="4"><?php echo $id.$username;?></td>
</tr>
<tr><td align="center"><input type="radio" name="in_grade" value="B">B</td>
</tr>
<tr><td align="center"><input type="radio" name="in_grade" value="C">C</td>
</tr>
<tr><td align="center"><input type="submit" name="btn_submit" 
                              value="Set Grade">
</td>
</tr>
</table>

<input type="hidden" name="in_pid" value="<?php echo $id;?>">
<input type="hidden" name="in_username" value="<?php echo $username;?>">
</form>

</body>
</html>

<?php
if (strlen($in_username) > 0 &&
    preg_match("/[ABC]/",$in_grade) &&
    $in_pid > 0 )  {

    // connect to the database
    require ('/etc/whm/rings_dbs.php');
    $cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
    if (!$cnx) {
        $msg = $msg . "<br>Error connecting to MySQL host $mysql_host";
        echo "$msg";
        exit;
    }
    $result = mysql_select_db($mysql_db);
    if (!$result) {
        $msg = $msg . "<br>Error connecting to MySQL db $mysql_db";
        echo "$msg";
        exit;
    }

    $cmd = "INSERT INTO picture_grades SET ";
    $cmd .= "pid = $in_pid, ";
    $cmd .= "uid = '$in_username', ";
    $cmd .= "grade = '$in_grade', ";
    $cmd .= "date_last_maint = NOW(), ";
    $cmd .= "date_added = NOW() ";
    $cmd .= "ON DUPLICATE KEY UPDATE ";
    $cmd .= "grade = '$in_grade', ";
    $cmd .= "date_last_maint = NOW() ";
    $result = mysql_query ($cmd);

    $sel = "SELECT count(*), grade FROM picture_grades ";
    $sel .= "WHERE pid = $in_pid ";
    $sel .= "GROUP BY grade ORDER BY count(*) ";
    $sel .= "LIMIT 0,1 ";
    $result = mysql_query ($sel);
    if ($result) {
        $row = mysql_fetch_array($result);
        $high_grade = $row['grade'];
    }

    $cmd = "UPDATE pictures_information SET ";
    $cmd .= "grade = '$high_grade', ";
    $cmd .= "date_last_maint = NOW() ";
    $cmd .= "WHERE pid = $in_pid ";
    $result = mysql_query ($cmd);

    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";

}
?>
