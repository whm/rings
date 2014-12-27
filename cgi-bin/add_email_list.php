<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_id            = $_REQUEST['in_id'];
$in_pid           = $_REQUEST['in_pid'];
$in_button_submit = $_REQUEST['in_button_submit'];
// ----------------------------------------------------------
//
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
Picture ID: <?php echo $in_id;?>
<input type="submit" name="in_button_submit" value="Select Picture">
<input type="hidden" name="in_pid" value="<?php echo $in_id;?>">
</form>

</body>
</html>

<?php
if ( isset($in_pid) )  {

    $_SESSION['s_email_list'] .= $in_pid.' ';
    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";

}
?>
