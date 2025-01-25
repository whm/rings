<?php
// ----------------------------------------------------------
// File: add_comment.php
// Author: Bill MacAllister

require('inc_ring_init.php');

// Form or URL input
$in_button_submit = get_request('in_button_submit');
$in_comment       = get_request('in_comment');
$in_pid           = get_request('in_pid');
$in_next_cid      = get_request('in_next_cid');
$in_noaction      = get_request('in_noaction');
$in_uid           = get_request('in_uid');

// Bail out if requested
if ( !empty($in_noaction) )  {
    echo "<script language=\"JavaScript\">\n";
    echo " window.close();\n";
    echo "</script>\n";
    exit;
}

?>

<html>
<head>
<title>Update Picture Comments</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>

<script language="JavaScript">
function closeWindow() {
    window.close();
    return true;
}
</script>

<?php
$add_sql = '';
$del_sql = '';
if ( !empty($in_button_submit) ) {
    // process deletes
    $sel = "SELECT pid, uid, cid ";
    $sel .= "FROM picture_comments_grades ";
    $sel .= "WHERE pid=$in_pid ";
    $sel .= 'AND uid="' . $in_uid . ' "';
    $result=  $DBH->query($sel);
    if ($result) {
        $del_cmds = [];
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $del_flag = get_request('in_delete_' . $row['cid']);
            if ( !empty($del_flag) ) {
                $del_sql = 'DELETE FROM picture_comments_grades ';
                $del_sql .= 'WHERE pid = ' . $row['pid'] . ' ';
                $del_sql .= 'AND uid = "' . $row['uid'] . '" ';
                $del_sql .= 'AND cid = ' . $row['cid'] . ' ';
               $del_cmds[] = $del_sql;
            }
        }
        if (count($del_cmds) > 0) {
            foreach ($del_cmds as $cmd) {
                $result = $DBH->query($cmd);
                if ($result) {
                    sys_msg('Deleted a comment');
                }
            }
        }
    }

    // Add the new command
    if (!empty($in_comment) ) {
        $add_sql = 'INSERT INTO picture_comments_grades SET ';
        $add_sql .= 'pid = ?, ';
        $add_sql .= 'uid = ?, ';
        $add_sql .= 'cid = ?, ';
        $add_sql .= 'comment = ?, ';
        $add_sql .= 'date_last_maint = NOW(), ';
        $add_sql .= 'date_added = NOW() ';
        if (!$sth = $DBH->prepare($add_sql)) {
            $m = 'Prepare failed: ' . $DBH->error
               . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $add_sql";
            sys_err($m);
        }
        $sth->bind_param('isis',
                         $in_pid,
                         $in_uid,
                         $in_next_cid,
                         $in_comment);
        if (!$sth->execute()) {
            $m = 'Execute failed: ' . $DBH->error
                . '(' . $DBH->errno . ') ' ;
            $m .= "Problem statement: $add_sql";
            sys_err($m);
        }
        $sth->close();
    }
}
?>

</head>

<body class="vs">

<form name="addComment" action="add_comment.php">

<?php

$sel = "SELECT pid, uid, cid, comment ";
$sel .= "FROM picture_comments_grades ";
$sel .= "WHERE pid=$in_pid ";
$sel .= 'AND uid="' . $in_uid . ' "';
$result=  $DBH->query($sel);
$max_cid = 0;
if ($result) {
    echo "<h3>Update Comments</h3>\n";
    echo '<table border="1">' . "\n";
    echo "<tr>\n";
    echo "<th>ID</th><th>Action</th><th>Comment</th>\n";
    echo "</tr>\n";
    while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        echo "<tr>\n";
        echo '<td>' . $row['cid'] . "</td>\n";
        echo '<td><input type="checkbox" '
             , 'name="in_delete_' . $row['cid'] . '">Delete</td>' . "\n";
        echo '<td>' . $row['comment'] . "</td>\n";
        echo "</tr>\n";
        if ($row['cid'] > $max_cid) {
                $max_cid = $row['cid'];
        }
    }
    echo "</table>\n";
}
$next_cid = $max_cid + 1;
?>

<h3>Add Comment</h3>
<TEXTAREA name="in_comment" rows="6" cols="60">
<?php
if (!empty($row["description"])) {
    print $row["description"];
}
?>
</TEXTAREA>

<br/>
<input type="submit" name="in_button_submit" value="Update">
<input type="submit" name="in_noaction"      value="Cancel">
<input type="hidden" name="in_pid"           value="<?php echo $in_pid;?>">
<input type="hidden" name="in_uid"           value="<?php echo $in_uid;?>">
<input type="hidden" name="in_next_cid"      value="<?php echo $next_cid;?>">
</form>

</body>
</html>
