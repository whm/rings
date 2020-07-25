<?php
// ----------------------------------------------------------
// File: add_email_list.php
// Author: Bill MacAllister

require('inc_ring_init.php');

// Form or URL input
$in_id            = get_request('in_id');
$in_pid           = get_request('in_pid');
$in_button_submit = get_request('in_button_submit');
?>
<html>
<head>
<title>Add Picture to Email List</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>

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
if ( !empty($in_pid) )  {

    $_SESSION['s_email_list'] .= $in_pid.' ';
    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";

}
?>
