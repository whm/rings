<?PHP
// -------------------------------------------------------------
// people_maint.php
// author: Bill MacAllister
// date: December 31, 2001
//

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

//-------------------------------------------------------------
// Start of main processing for the page

?>

<html>
<head>
<title>Maintenance Menu</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">

<?php
$thisTitle = 'Maintenance Menu';
require ('page_top.php');
?>

<table width="500" border="0">
<tr><td>
The Rings is really just a picture gallery.  It got its name because it
is possible to pick many paths throught the gallery and the paths 
conceptually form rings between lives, places, and events.

<p>

The functions in the navigation menu on the left are for editing and loading
pictures one at a time.  For describing pictures there really is no way
around doing the pictures one at a time.  

</td></tr>
</table>

<?php require('page_bottom.php'); ?>
</body>
</html>
