<?php
// Display a picture

$sel = "SELECT file_path ";
$sel .= "FROM tmp_matching ";
$sel .= "WHERE signature = '" . $REQUEST['in_signature'] . "' ";
$result = $DBH->query ($sel);
if ($result) {
    if ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $this_path = $row['file_path'];
    }
}

header("Content-type: image/jpeg");
readfile($this_path);

?>
