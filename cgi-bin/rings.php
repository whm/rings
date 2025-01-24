<?PHP
// -------------------------------------------------------------
// rings.php
// author: Bill MacAllister
// date: December 31, 2001

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_ring_pid = get_request('in_ring_pid');
?>
<html>
<head>
<title>Rings</title>
<?php include('ring_style.css');?>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$next_links = array();

if (!empty($in_ring_pid)) {

  // display picture and links

    $sel = "SELECT * ";
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid=$in_ring_pid ";
    $result = $DBH->query($sel);
    if ($result) {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $this_type = trim($row["picture_type"]);
        $this_pid = $row["pid"];
        $this_date_taken = $row["date_taken"];
        $this_fullbytes = sprintf ('%7.7d', strlen($row["picture"])/1024);
        echo '<img src="/rings/display.php?in_pid=$this_pid&in_size=large">';
        echo "\n";
        echo "<p>\n";
        $sel = "SELECT det.uid, ";
        $sel .= "person.display_name ";
        $sel .= "FROM picture_details det, people_or_places person ";
        $sel .= "WHERE pid=$in_ring_pid ";
        $sel .= "AND det.uid = person.uid ";
        $result = $DBH->query($sel);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $this_uid          = $row["uid"];
                $this_display_name = $row["display_name"];
                $nxt_sel = "SELECT p.pid ";
                $nxt_sel .= "FROM picture_details det, pictures_information p ";
                $nxt_sel .= "WHERE det.pid = p.pid ";
                $nxt_sel .= "AND det.uid = '$this_uid' ";
                $nxt_sel .= "AND p.date_taken > '$this_date_taken' ";
                $nxt_sel .= "ORDER BY p.date_taken ";
                $nxt_sel .= "LIMIT 1 ";
                $nxt_result = $DBH->query($nxt_sel);
                if ($nxt_result) {
                    if ($nxt_row = $nxt_result->fetch_array(MYSQLI_ASSOC)) {
                        $new_pid = $nxt_row["pid"];
                        $next_links[$this_display_name] = $new_pid;
                    } else {
                        $nxt_sel = 'SELECT p.pid pid ';
                        $nxt_sel .= 'FROM picture_details det, ';
                        $nxt_sel .= 'pictures_information p ';
                        $nxt_sel .= 'WHERE det.pid = p.pid ';
                        $nxt_sel .= 'AND det.uid = "$this_uid" ';
                        $nxt_sel .= 'ORDER BY p.date_taken ';
                        $nxt_sel .= 'LIMIT 1 ';
                        $nxt_result = $DBH->query($nxt_sel);
                        if ($nxt_result) {
                            $nxt_row = $nxt_result->fetch_array(MYSQLI_ASSOC);
                            $new_pid = $nxt_row["pid"];
                            $next_links["$this_display_name ."] = $new_pid;
                        } else {
                            $in_ring_pid = '';
                        }
                    }
                }
            }
        }
    } else {
        $in_ring_pid = '';
    }
}

$display_rings = 0;
if (strlen($in_ring_pid) == 0) {
    $display_rings = 1;
}

if ($display_rings>0) {

    // ------------------------------------------
    // display ring choices

    echo "<h2>Pick a Picture Ring</h2>\n";
    echo "<p>\n";
    echo "<table border=\"0\">\n";
    $sel = "SELECT uid, display_name, description ";
    $sel .= "FROM people_or_places ";
    $sel .= "ORDER BY display_name ";
    $result = $DBH->query($sel);
    if ($result) {
        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
            $this_uid = $row["uid"];
            $this_name = $row["display_name"];
            $this_description = $row["description"];
            $nxt_sel = "SELECT p.pid pid ";
            $nxt_sel .= "FROM picture_details det, pictures_information p ";
            $nxt_sel .= "WHERE det.pid = p.pid ";
            $nxt_sel .= "AND det.uid = '$this_uid' ";
            $nxt_sel .= "ORDER BY p.date_taken ";
            $nxt_sel .= "LIMIT 1 ";
            $nxt_result = $DBH->query($nxt_sel);
            if ($nxt_result) {
                $nxt_row = $nxt_result->fetch_array(MYSQLI_ASSOC);
                $new_pid = $nxt_row["pid"];
                if (strlen($new_pid) > 0) {
                    echo "<tr bgcolor=\"#000066\">\n";
                    echo '  <td colspan="2">'
                        . '<img src="/rings-images/shim.gif"></td>' . "\n";
                    echo "</tr>\n";
                    echo "<tr>\n";
                    echo "  <td><b><a href=\"rings.php?in_ring_pid=$new_pid\">";
                    echo "$this_name";
                    echo "</a></b></td> \n";
                    echo "  <td>";
                    echo "$this_description";
                    echo "</td>\n";
                    echo "</tr>\n";
                } else {
                    echo "<tr>\n";
                    echo "<td><b>$this_name</b></td><td> "
                        . "$this_description</td>\n";
                    echo "</tr>\n";
                }
            }
        }
    }
    echo "</table>\n";
    echo "<p>\n";
    echo "<h5><a href=\"picture_maint.php\">Picture Maintenance</a></h5>\n";

} else {

    // ------------------------------------------
    // display a picture

    if (count($next_links)>0) {
        asort($next_links);
        foreach ($next_links as $thisName => $thisID) {
            $urlName = urlencode($thisName);
            echo "<a href=\"rings.php?in_ring_pid=$thisID\">";
            echo "<img src=\"button.php?in_button=$urlName\"></a><br>\n";
        }
    }
    echo "<p>\n";
    echo "<a href=\"rings?in_ring_pid=\">Pick a new Ring</a>\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"/rings/display.php?in_pid=$this_pid\" ";
    echo "target=\"_blank\">Image Full Size ($this_fullbytes Kbytes)</a>\n";
    if ($ring_user) {
        echo "<p>\n";
        echo "<h5><a href=\"picture_maint?in_pid=$this_pid\" ";
        echo "target=\"_blank\">$this_pid</a> ";
        echo "&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<a href=\"rings.php?in_logout=1\">Logout</a></h5>\n";
    }
}

?>

</Body>
</html>
