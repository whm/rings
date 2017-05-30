<?PHP
// -------------------------------------------------------------
// access_email.php
// author: Bill MacAllister
// date: 29-May-2017
//
// Send a request for access to the rings

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

?>
<html>
<head>
<title>Request Access to the Rings</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">

</head>

<body bgcolor="#eeeeff">

<a href="index.php">
<img src="/rings-images/icon-home.png" border="0"
     alt="Pick a new Picture Ring">
</a>

<p>
To request access to the rings just fill out the form below.
</p>

<form name="access_request"
      method="post"
      action="access_email_action.php">

  <fieldset>
    <legend>Rings Access Request</legend>
    <div id="accessInputBox">
    <label class="access">First name:</label>
        <input class="access" type="text" name="in_firstname" REQUIRED
               placeholder="Required">
    </div>

    <div id="accessInputBox">
    <label class="access">Last name:</label>
        <input class="access" type="text" name="in_lastname" REQUIRED
               placeholder="Required">
    </div>

    <div id="accessInputBox">
    <label class="access">eMail Address:</label>
        <input class="access" type="email" name="in_email" REQUIRED
               placeholder="Required">
    </div>

    <div id="accessInputBox">
    <label class="access">Comments: </label>
        <textarea id="comments"  name="in_comments" rows="6" cols="70">
        </textarea>
    </div>
    <input type="submit" name="in_button" value="Send">
  </fieldset>
</form>

<p>
It might take a day or two for this to get a response because the administrator
still works for a liviing.
</p>
    
</Body>
</html>
