<?PHP
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_message  = $_REQUEST['in_message'];
$in_subject  = $_REQUEST['in_subject'];
$in_pid  = $_REQUEST['in_pid'];
$in_cc_addr  = $_REQUEST['in_cc_addr'];
$in_to_addr  = $_REQUEST['in_to_addr'];
$in_button_to  = $_REQUEST['in_button_to'];
$in_button_cc  = $_REQUEST['in_button_cc'];
$in_button_send  = $_REQUEST['in_button_send'];
$in_button_cancel  = $_REQUEST['in_button_cancel'];
$in_button_email  = $_REQUEST['in_button_email'];
// ----------------------------------------------------------
//
// -------------------------------------------------------------
// picture_email.php
// author: Bill MacAllister
// date: 22-Nov-2004
//
// Open a session, perform authorization check, and include authorization 
// routines unique to the rings.
require('inc_auth_policy.php');

require ('/etc/whm/rings_dbs.php');

// look up the from address
$ds = ldap_connect($ldap_server);
$return_attr = array('cn','mail');
$ldap_filter = '(uid='.$_SESSION['whm_directory_user'].')';
$sr = @ldap_search ($ds, $ldap_base, $ldap_filter, $return_attr);
$info = @ldap_get_entries($ds, $sr);
$ret_cnt = $info["count"];
if ($ret_cnt == 1) {
    $from_display = '&lt;'.$info[0]["mail"][0].'&gt; ' . $info[0]["cn"][0];
    $from_addr = '<'.$info[0]["mail"][0].'> ' . $info[0]["cn"][0];
} else {
    $from_addr = 'webmaster@macallister.grass-valley.ca.us';
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
  <td><?php echo $from_display;?>
      <input type="hidden"
             name="in_from_addr"
             value="<?php echo $from_addr;?>">
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
        echo "<img src=\"display.php?in_pid=$email_pid&in_size=small\">\n";
      }
    }
    ?>
  </td>
  <td>&nbsp;</td>
</tr>
</table>

</form

<a href="index.php">
<img src="/rings-images/rings.png" border="0"
     alt="Pick a new Picture Ring">
</a>

</Body>
</html>

