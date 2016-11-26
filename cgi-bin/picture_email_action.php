<?php
// ----------------------------------------------------------
// File: picture_email_action.php
// Author: Bill MacAllister
// Date: 26-Nov-2004

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');

require ('htmlMimeMail.php');

// Form or URL inputs
$in_subject       = get_request('in_subject');
$in_message       = get_request('in_message');
$in_from_addr     = get_request('in_from_addr');
$in_cc_addr       = get_request('in_cc_addr');
$in_to_addr       = get_request('in_to_addr');
$in_button_to     = get_request('in_button_to');
$in_button_cc     = get_request('in_button_cc');
$in_button_send   = get_request('in_button_send');
$in_button_cancel = get_request('in_button_cancel');
$in_button_email  = get_request('in_button_email');

// ----------------------------------------------------
// Main Routine

// set update message area
$err_msg = '';
$ok   = '<font color="#009900">';
$warn = '<font color="#330000">';
$mend = "</font><br>\n";
$msg  = '';

// get the picture information
if (!empty($in_button_send)) {

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
        $in_message = 'A picture for you\n';
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
    $xhdr = 'The Rings (http://www.macallister.grass-valley.ca.us/rings)';
    $mailMsg->setHeader('X-Mailer', $xhdr);

    $email_list = explode(" ", $_SESSION['s_email_list']);
    $msg_cnt = 0;
    foreach ($email_list as $email_pid) {

        // Skip empty entries.
        if (empty($email_pid) || $email_pid<1) { continue; }

        // get the picture information
        $sel = "SELECT * FROM pictures_information WHERE pid=$email_pid ";
        $result = $DBH->query($sel);
        if (!$result) {
            $err_msg .= "$warn Problem finding picture information.$mend";
            $err_msg .= "$warn Problem SQL:$sel$mend";
            break;
        }

        $row = $result->fetch_array(MYSQLI_ASSOC);
        $pic_lot  = $row['picture_lot'];
        $pic_size = $CONF['mail_size'];
        $pic_path = picture_path($pic_lot, $pic_size, $email_pid, $file_type);

        list($this_mime_type, $this_file_type)
            = get_picture_type($email_pid, $pic_size);
        if (empty($mime_type)) {
            sys_err("Skipping picture $pid");
            continue;
        }

        $this_picture = file_get_contents($pic_path);
        $this_file    = "${pic_lot}-${email_pid}.${this_file_type}";
        sys_msg("Picture ${this_file}, size " . strlen($this_picture));
            
        // Add the picture
        $mailMsg->addAttachment($this_picture,
                                $this_file,
                                $this_mime_type);
        $msg_cnt++;
    }
    if ($msg_cnt == 0) {
        sys_msg("Message not sent");
    } else {
        $mailResult = $mailMsg->send($to_addrs);
    }
}

?>
<html>
<head>
<title>Email Results</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">
<h2>Email Results</h2>
<p>

<?php
// These errors are only set if you're using SMTP to send the message
if (!empty($in_button_cancel)) {
    echo '<h3>No mail sent.  List cleared.</h3>';
    $_SESSION['s_email_list'] = '';
} elseif (!$mailResult) {
    echo "${sys_msg_warn}\n";
    echo "<pre>\n";
    print_r($mailMsg->errors);
    echo "</pre>\n";
    echo "${sys_msg_end}\n";
} else {
    echo '<h3>Mail sent!</h3>';
    echo "<blockquote>\n";
    echo $_SESSION['msg'];
    $_SESSION['msg'] = '';
    echo "</blockquote>\n";
}
?>

<?php if (!empty($in_button_cancel)) { ?>
<form name="emailMessageAction"
      method="post"
      action="picture_email.php">
<input type="hidden" name="in_to_addr" value="<?php echo $in_to_addr;?>">
<input type="hidden" name="in_cc_addr" value="<?php echo $in_cc_addr;?>">
<input type="hidden" name="in_from_addr" value="<?php echo $in_from_addr;?>">
<input type="hidden" name="in_subject" value="<?php echo $in_subject;?>">
<input type="hidden" name="in_message" value="<?php echo $in_message;?>">
<input type="submit" name="in_button_email" value="Back to Email">
</form>
<?php } ?>

<a href="index.php">
<img src="/rings-images/rings.png" border="0"
     alt="Pick a new Picture Ring">
</a>

</body>
</html>
