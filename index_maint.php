<?PHP
// -------------------------------------------------------------
// people_maint.php
// author: Bill MacAllister
// date: December 31, 2001
//

require ('inc_page_open.php');

//-------------------------------------------------------------
// Start of main processing for the page

?>

<html>
<head>
<title>Maintenance Menu</title>
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
conceptually for rings between lives, places, and events.

<p>

The functions in the navigation menu on the left are for editing and loading
pictures one at a time.  For describing pictures there really is not any
way around this.  

<p>
Loading though is another story.  To load pictures in bulk it is best 
to use the perl script, <a href="ring_load.pl>ring_load.pl</a>, 
developed for that purpose.  Of course, you have to have perl 
installed on your system.  ImageMagick is also required.

</td></tr>
</table>

<?php require('page_bottom.php'); ?>
</body>
</html>
