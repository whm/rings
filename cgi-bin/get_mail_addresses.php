<?php
// ----------------------------------------------------------
// File: get_mail_addresses.php
// Date: 23-Nov-2004
// Author: Bill MacAllister

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

// Form or URL inputs
$in_type          = get_request('in_type');
$in_button_submit = get_request('in_button_submit');
?>
<html>
<head>
<title>Pick Some Addresses</title>
<?php require('inc_page_head.php'); ?>
<?php require('inc_page_style_rings.php');?>

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
<?php

// Look up the from address.  This is an anonymous bind.
$ldap_server = $CONF['ldap_server'];
$ldap_base   = $CONF['ldap_base'];
$ldap_filter = '(&(mail=*)(objectclass=person))';
$return_attr = array('cn','mail');

$ds      = ldap_connect($ldap_server);
$sr      = @ldap_search ($ds, $ldap_base, $ldap_filter, $return_attr);
$info    = @ldap_get_entries($ds, $sr);
$ret_cnt = $info["count"];

# Sort the search output
if ($ret_cnt > 0) {
    echo '<form name="selectAddress"' . "\n";
    echo '      onsubmit="return setAddress()">' . "\n";
    echo '<select name="addr" multiple size="16">' . "\n";
    $peeps = array();
    for ($i=0; $i<$ret_cnt;$i++) {
        $peeps[$info[$i]["cn"][0]] = $i;
    }
    ksort($peeps);
    foreach ($peeps as $cn => $i) {
        $from_display = $cn . ' &lt;' . $info[$i]["mail"][0] . '&gt;';
        $from_addr = '<' . $info[$i]["mail"][0] ."> $cn";
        echo "<option value=\"$from_display\">$from_display\n";
    }
    echo '</select>' . "\n";
    echo '<input type="submit" ' . "\n";
    echo '       name="in_button_submit"' . "\n";
    echo '       value="Set Address">' . "\n";
    echo '<input type="hidden" name="fld_count" ' . "\n";
    echo '       value="' . $ret_cnt . '">' . "\n";
    echo '</form>' . "\n";
} else {
    echo 'No entries found';
}
?>

</body>
</html>
