<?php
// File: get_mail_addresses.php
// Fate: 23-Nov-2004
// Author: Bill MacAllister

// Information about where the addresses are
require ('/etc/whm/rings_dbs.php');

// look up the from address
$ds = ldap_connect($ldap_server);
$return_attr = array('cn','mail');
$ldap_filter = '(&(mail=*)(objectclass=person))';
$sr = @ldap_search ($ds, $ldap_base, $ldap_filter, $return_attr);
$info = @ldap_get_entries($ds, $sr);
$ret_cnt = $info["count"];

  $from_display = '&lt;'.$info[0]["mail"][0].'&gt; ' . $info[0]["cn"][0];
  $from_addr = '<'.$info[0]["mail"][0].'> ' . $info[0]["cn"][0];

?>
<html>
<head>
<title>Pick Some Addresses</title>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
<?php require('inc_page_head.php'); ?>

<script language="JavaScript">

/* ----------------- */
/* Set Address       */
/* ----------------- */

function setAddress () {

  var f = document.selectAddress;
  var val = "";
  var c = "";
  for (var i=0; i<f.addr.length; i++) {
    if (f.addr[i].selected) {
      val += c + f.addr.options[i].value;
      c = ",\n";
    }
  }
  window.opener.document.emailMessage.in_<?php echo $in_type;?>_addr.value 
          = val;
  window.close();
  return false;

}
</script>

</head>

<body bgcolor="#eeeeff">
<h3>Pick Some Addresses</h3>
<form name="selectAddress"
      onsubmit="return setAddress()">
<select name="addr" multiple size="16">
<?php
for ($i=0; $i<$ret_cnt;$i++) {
  $from_display = $info[$i]["cn"][0] . ' &lt;'.$info[$i]["mail"][0].'&gt; ';
  $from_addr = '<'.$info[$i]["mail"][0].'> ' . $info[$i]["cn"][0];
  echo "<option value=\"$from_display\">$from_display\n";
}
?>
</select>
<input type="submit" name="btn_submit" value="Set Address">
<input type="hidden" name="fld_count" value="<?php echo $cnt;?>">
</form>

</body>
</html>