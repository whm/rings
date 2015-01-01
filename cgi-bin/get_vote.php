<?php
// ----------------------------------------------------------
// File: get_VOTE.php
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require ('inc_ring_init.php');

// Get form or URL inputs
$in_id            = get_request('in_id');
$in_pid           = get_request('in_pid');
$in_grade         = get_request('in_grade');
$in_username      = get_request('in_username');
$in_button_submit = get_request('in_button_submit');
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
    <td rowspan="4"><?php echo "$in_id $username";?></td>
</tr>
<tr><td align="center"><input type="radio" name="in_grade" value="B">B</td>
</tr>
<tr><td align="center"><input type="radio" name="in_grade" value="C">C</td>
</tr>
<tr><td align="center"><input type="submit" name="in_button_submit"
                              value="Set Grade">
</td>
</tr>
</table>

<input type="hidden" name="in_pid" value="<?php echo $in_id;?>">
<input type="hidden" name="in_username" value="<?php echo $in_username;?>">
</form>

</body>
</html>

<?php
if (isset($in_pid) && preg_match("/[ABC]/",$in_grade))  {

    $cmd = "INSERT INTO picture_grades SET ";
    $cmd .= "pid = $in_pid, ";
    $cmd .= "uid = '$in_username', ";
    $cmd .= "grade = '$in_grade', ";
    $cmd .= "date_last_maint = NOW(), ";
    $cmd .= "date_added = NOW() ";
    $cmd .= "ON DUPLICATE KEY UPDATE ";
    $cmd .= "grade = '$in_grade', ";
    $cmd .= "date_last_maint = NOW() ";
    $result = $DBH->query($cmd);

    $sel = "SELECT count(*), grade FROM picture_grades ";
    $sel .= "WHERE pid = $in_pid ";
    $sel .= "GROUP BY grade ORDER BY count(*) ";
    $sel .= "LIMIT 0,1 ";
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $high_grade = $row['grade'];
    }

    $cmd = "UPDATE pictures_information SET ";
    $cmd .= "grade = '$high_grade', ";
    $cmd .= "date_last_maint = NOW() ";
    $cmd .= "WHERE pid = $in_pid ";
    $result = $DBH->query($cmd);

    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";

}
?>
