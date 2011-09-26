<?PHP
// -------------------------------------------------------------
// picture_email.php
// author: Bill MacAllister
// date: 22-Nov-2004
//

// Open a session
require('whm_php_auth.inc');
require('whm_php_sessions.inc');
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
<LINK href="/rings-styles/pictures.css" rel="stylesheet" type="text/css">

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
             name="btn_to"
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
             name="btn_cc"
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
  <td colspan="2">
      <textarea cols="70" 
                rows="20" 
                wrap="physical"
                name="in_message"><?php echo $in_message;?></textarea>
  </td>
  <td>&nbsp;</td>
</tr>
<tr>
  <td align="center" valign="top" align="right">
    <input type="submit"
         name="btn_send"
         value="send">
  </td>
  <td align="center">
    <img src="display.php?in_pid=<?php echo $in_pid;?>&in_size=large">
  </td>
  <td>&nbsp;</td>
</tr>
</table>

</form

</Body>
</html>

