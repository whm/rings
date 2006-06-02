<?php

// File: picture_email_action.php
// Author: Bill MacAllister
// Date: 26-Nov-2004

require ('inc_page_open.php');
require ('htmlMimeMail.php');

// ----------------------------------------------------
// Main Routine

// set update message area
$err_msg = '';
$ok = '<font color="#009900">';
$warn = '<font color="#330000">';
$mend = "</font><br>\n";
$msg = '';

require('inc_dbs.php');
// connect to the database
$cnx = mysql_connect ( $mysql_host, $mysql_user, $mysql_pass );
if (!$cnx) {
  $err_msg .= "$warn Error connecting to MySQL host $mysql_host$mend";
}
$result = mysql_select_db($mysql_db);
if (!$result) {
  $err_msg .= "$warn Error connecting to MySQL db $mysql_db$mend";
}

// No spaces allowed in the identifier
$in_pid = ereg_replace (" ","",$in_pid);

// get the image first
$sel = "SELECT * FROM pictures WHERE pid=$in_pid ";
$result = mysql_query ($sel,$cnx);
if (!$result) {
  $err_msg .= "$warn Problem finding image.$mend";
  $err_msg .= "$warn Problem SQL:$sel$mend";
} else {

  // get the picture
  $row = mysql_fetch_array ($result);
  $this_picture = $row['picture'];
  $msg .= "$ok Picture size ".strlen($this_picture)."$mend";

  // get to: distribution list
  $to_addrs = array();
  $a_to = trim(strtok($in_to_addr, ','));
  while (strlen($a_to)>0) {
    $msg .= $ok.'To:'.htmlentities($a_to).$mend;
    $to_addrs[] = $a_to;
    $a_to = trim(strtok(','));
  }

  // make a mail message
  $mailMsg = new htmlMimeMail();
  if ( strlen(trim($in_message)) == 0 ) {
    $in_message = 'A picture for your\n';
  }
  $mailMsg->setText($in_message);
  $msg .= $ok.'Message text size:'.strlen($in_message).$mend;

  // CC address
  $in_cc_addr = trim($in_cc_addr);
  if ( strlen($in_cc_addr) > 0 ) {
    $msg .= $ok.'CC:'.htmlentities($in_cc_addr).$mend;
    $mailMsg->setCc($in_cc_addr);
  }

  // From address
  $env_from = $in_from_addr;
  if ( preg_match ('/<(.*?)>/', $in_from_addr, $matches) ) {
    $env_from = $matches[1];
  }
  $msg .= $ok.'Envelope From:'.htmlentities($env_from).$mend;
  $mailMsg->setFrom($env_from);
  //  $msg .= "$ok Header From:". htmlentities($in_from_addr) . $mend;
  //  $mailMsg->setHeader('From', $in_from_addr); 

  // Add subject header
  $msg .= $ok.'Subject:'.htmlentities($in_subject).$mend;
  $mailMsg->setSubject($in_subject);

  // Add mailer header
  $mailMsg->setHeader('X-Mailer', 
		      'The Rings (http://www.macallister.grass-valley.ca.us/rings)');

  // Add the picture
  $mailMsg->addAttachment($this_picture, 
			  $row['file_name'], 
			  $row['picture_type']);

  $mailResult = $mailMsg->send($to_addrs);

}

mysql_close ($cnx);

?>
<html>
<head>
<title>Email Results</title>
</head>

<body bgcolor="#eeeeff">
<h2>Email Results</h2>
<p>

<?php
// These errors are only set if you're using SMTP to send the message
if ( strlen($err_msg)>0 ) {
  echo $err_msg;
  echo "<p>\n";
  echo "$warn Mail not sent.$mend";
} else if (!$mailResult) {
  echo "$warn\n";
  echo "<pre>\n";
  print_r($mailMsg->errors);
  echo "</pre>\n";
  echo $mend;
} else {
  echo '<h3>Mail sent!</h3>';
  echo "<blockquote>\n";
  echo $msg;
  echo "</blockquote>\n";
}
?>

<table border="0" cellpadding="10">

<tr>
<td>
<form name="emailMessageAction"
      method="post"
      action="picture_email.php">
<input type="hidden" name="in_to_addr" value="<?php echo $in_to_addr;?>">
<input type="hidden" name="in_cc_addr" value="<?php echo $in_cc_addr;?>">
<input type="hidden" name="in_from_addr" value="<?php echo $in_from_addr;?>">
<input type="hidden" name="in_pid" value="<?php echo $in_pid;?>">
<input type="hidden" name="in_subject" value="<?php echo $in_subject;?>">
<input type="hidden" name="in_message" value="<?php echo $in_message;?>">
<input type="submit" name="btn_email" value="Back to Email">
</form>
</td>

<td>
<form name="selectPicture"
      method="post"
      action="picture_select.php">
<input type="hidden" name="in_pid" value="<?php echo $in_pid;?>">
<input type="submit" name="btn_email" value="Back to Rings">
</form>
</td>
</tr>

</table>

</body>
</html>
