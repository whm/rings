<?php
// ----------------------------------------------------------
// File: picture_email_action.php
// Author: Bill MacAllister
// Date: 26-Nov-2004

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require('libphp-phpmailer/autoload.php');

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

##############################################################################
# Main Routine
##############################################################################

// get the picture information
if (!empty($in_button_send)) {

    // make a mail message
    $mailMsg = new phpMailer;

    // From address
    sys_msg('Header From:' . htmlentities($in_from_addr));
    $mailMsg->setFrom($in_from_addr);

    // get to: distribution list
    $to_addrs = array();
    $a_to = trim(strtok($in_to_addr, ','));
    while (!empty($a_to)) {
        if (preg_match('/([\w\d\-\.\_]*)[<]([\w\d\-\_\.]+)[>](.*)/',
                       $a_to,
                       $matches)) {
            $addr = $matches[2][0];
            $comment = $matches[1][0] + ' ' + $matches[3][0];
            sys_msg("addr:$addr comment:$comment");
        } else {
            $addr = $a_to;
            $comment = '';
        }
        if ($mailMsg->addAddress($addr, $comment)) {
            sys_msg('To: ' . htmlentities($a_to));
        } else {
            sys_err($mailMsg->ErrorInfo);
        }
        $a_to = trim(strtok(','));
    }

    // CC address
    $in_cc_addr = trim($in_cc_addr);
    if (!empty($in_cc_addr)) {
        sys_msg('CC:' . htmlentities($in_cc_addr));
        $mailMsg->AddCC($in_cc_addr);
    }

    // Add subject header
    if (empty($in_subject)) {
        $in_subject = 'Some pictures for you';
    }
    sys_msg('Subject:' . htmlentities($in_subject));
    $mailMsg->Subject = $in_subject;

    // Add mailer header
    $xhdr = 'The Rings (http://www.ca-zephyr.org/rings)';
    $mailMsg->addCustomHeader('X-Mailer', $xhdr);

    // message body
    $mailMsg->WordWrap = 80;
    $mailMsg->IsHTML(false);
    
    if (empty(trim($in_message))) {
        $in_message = 'A picture for you\n';
    }
    $mailMsg->Body = $in_message;
    sys_msg('Message text size:' . strlen($in_message));

    // Get the table to pull the pictures mime type from
    list($sz_id, $sz_desc, $sz_table) = validate_size($CONF['mail_size']);
    $email_list = explode(" ", $_SESSION['s_email_list']);
    $msg_cnt = 0;
    foreach ($email_list as $email_pid) {

        // Skip empty entries.
        if (empty($email_pid) || $email_pid<1) { continue; }

        // Get the picture lot
        $pic_lot = get_picture_lot($email_pid);

        // Get the mime and file types for this picture
        list($pic_mime_type, $pic_file_type)
            = get_picture_type($email_pid, $sz_id);
        if (empty($pic_mime_type)) {
            sys_err("Skipping picture $pid");
            continue;
        }

        // Assemble the paths to find the picture
        list ($pic_dir, $pic_path)
            = picture_path($pic_lot, $sz_id, $email_pid, $pic_file_type);

        // Add the attachement to the message
        $mailMsg->addAttachment($pic_path);
        sys_msg("Added picture ${email_pid} to message.");
        $msg_cnt++;
    }
    if ($msg_cnt == 0) {
        sys_err('Message not sent');
    } else {
        if (!$mailMsg->send()) {
            sys_err('ERROR: ' . $mailMsg->ErrorInfo);
        } else {
            sys_msg('Message sent!');
        }
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
    sys_err('No mail sent.  List cleared.');
    $_SESSION['s_email_list'] = '';
}
sys_display_msg();
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
<img src="/rings-images/icon-home.png" border="0"
     alt="Pick a new Picture Ring">
</a>

</body>
</html>
