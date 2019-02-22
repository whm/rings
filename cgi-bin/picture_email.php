<?PHP
// -------------------------------------------------------------
// picture_email.php
// author: Bill MacAllister
// date: 22-Nov-2004
//
// Open a session, perform authorization check, and include authorization
// routines unique to the rings.

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Input data
$in_message       = get_request('in_message');
$in_subject       = get_request('in_subject');
$in_pid           = get_request('in_pid');
$in_cc_addr       = get_request('in_cc_addr');
$in_to_addr       = get_request('in_to_addr');
$in_button_to     = get_request('in_button_to');
$in_button_cc     = get_request('in_button_cc');
$in_button_send   = get_request('in_button_send');
$in_button_cancel = get_request('in_button_cancel');
$in_button_email  = get_request('in_button_email');

// look up the from address
if (empty($_SERVER['WEBAUTH_LDAP_MAIL'])) {
    $from_email = $_SESSION['remote_user'] . '@' . $CONF['mail_domain'];
} else {
    $from_email = $_SESSION['user_mail'];
}

?>
<html>
<head>
<title>Email a Picture</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">

<script language="JavaScript">

/* ----------------- */
/* Helper routines   */
/* ----------------- */

/* ------------------------ */
/* Pick some addresses      */
/* ------------------------ */

function get_mail_addresses(addrType) {
  var f = document.emailMessage;
  var win = window.open("get_mail_addresses.php?in_type="+addrType,
                        "PickUsers",
                        "width=400,height=400,status=yes");
  return false;
}

</script>

</head>

<body bgcolor="#eeeeff">

<h1>Email a Picture</h1>

<form name="emailMessage"
      method="post"
      action="picture_email_action.php">

<input type="hidden"
       name="in_pid"
       value="<?php echo $in_pid;?>">

<table border="0" cellpadding="2">
<tr>
  <td align="right">From:</td>
  <td><?php echo $from_email;?>
      <input type="hidden"
             name="in_from_addr"
             value="<?php echo $from_email;?>">
  </td>
  <td>&nbsp;</td>
</tr>
<tr>
  <td align="right">To:</td>
  <td><textarea cols="60"
                rows="3"
                wrap="physical"
                name="in_to_addr"><?php echo $in_to_addr;?></textarea>
  </td>
  <td><input type="button"
             name="in_button_to"
             value="Lookup To Addresses"
             onClick="get_mail_addresses('to')">
  </td>
</tr>
<tr>
  <td align="right">CC:</td>
  <td><textarea cols="60"
                rows="2"
                wrap="physical"
                name="in_cc_addr"><?php echo $in_cc_addr;?></textarea>
  </td>
  <td><input type="button"
             name="in_button_cc"
             value="Lookup CC Addresses"
             onClick="get_mail_addresses('cc')">
  </td>
</tr>
<tr>
  <td align="right">Subject:</td>
  <td><input type="text"
             size="60"
             name="in_subject"
             value="<?php echo $in_subject;?>">
  </td>
  <td>&nbsp;</td>
</tr>
<tr>
  <td align="right">Message:</td>
  <td><textarea cols="70"
                rows="20"
                wrap="physical"
                name="in_message"><?php echo $in_message;?></textarea>
  </td>
  <td>&nbsp;</td>
</tr>
<tr>
  <td align="center" valign="top" align="right">
    <input type="submit"
         name="in_button_send"
         value="send">
    <br>
    <input type="submit"
         name="in_button_cancel"
         value="cancel">
  </td>
  <td align="center">
    <?php
    $email_list = explode(" ", $_SESSION['s_email_list']);
    foreach ($email_list as $email_pid) {
        if ($email_pid > 0) {
            $img = '<img src="display.php'
                . '?in_pid=' . $email_pid
                . '&in_size=' . $CONF['index_size']
                . '">' . "\n";
            echo $img;
        }
    }
    ?>
  </td>
  <td>&nbsp;</td>
</tr>
</table>

</form>

<a href="index.php">
<img src="/rings-images/icon-home.png" border="0"
     alt="Pick a new Picture Ring">
</a>

</Body>
</html>
