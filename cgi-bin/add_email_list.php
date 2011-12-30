<?php
// File: add_email_list.php
// Author: Bill MacAllister

require('inc_ring_init.php');

?>
<html>
<head>
<title>Add Picture to Email List</title>
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

<h3>Add Picture to Email List</h3>
<form name="addEmailList" action="add_email_list.php">
<input type="submit" name="btn_submit" value="Set Grade">
<input type="hidden" name="in_pid" value="<?php echo $id;?>">
</form>

</body>
</html>

<?php
if (strlen($in_pid) > 0 && $in_pid > 0 )  {

    $_SESSION['email_list'] .= $in_pid.' ';
    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";

}
?>