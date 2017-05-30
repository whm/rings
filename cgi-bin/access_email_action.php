<?php
// ----------------------------------------------------------
// File: picture_email_action.php
// Author: Bill MacAllister
// Date: 26-Nov-2004

// Open a session, connect to the database, load convenience routines,
// and initialize the message area.
require('inc_ring_init.php');
require 'libphp-phpmailer/PHPMailerAutoload.php';

// Form or URL inputs
$in_button    = get_request('in_button');
$in_comments  = htmlentities(get_request('in_comments'));
$in_firstname = htmlentities(get_request('in_firstname'));
$in_lastname  = htmlentities(get_request('in_lastname'));
$in_email     = htmlentities(get_request('in_email'));

##############################################################################
# Main Routine
##############################################################################

$from_addr = $in_email;
$to = 'bill@ca-zephyr.org';
$subject = "Access request from $in_firstname $in_lastname";

// get the picture information
if ($in_button == 'Send') {

    // make a mail message
    $mailMsg = new phpMailer;

    // From address
    sys_msg('From:' . $in_email);
    $mailMsg->setFrom($from_addr);

    // Set the to address
    sys_msg('To: Administrator');
    $mailMsg->addAddress($to);

    // Add subject header
    sys_msg('Subject:' . $subject);
    $mailMsg->Subject = $subject;

    // Add mailer header
    $xhdr = 'The Rings (http://www.ca-zephyr.org/rings)';
    $mailMsg->addCustomHeader('X-Mailer', $xhdr);

    // message body
    $mailMsg->WordWrap = 80;
    $mailMsg->IsHTML(false);

    $mail_msg = "First Name: $in_firstname\n";
    $mail_msg .= "Last Name: $in_lastname\n";
    $mail_msg .= "eMail Address: $in_email\n";
    
    if (!empty($in_comments)) {
        $mail_msg .= "\n";
        $mail_msg .= "Comments:\n";
        $mail_msg .= $in_comments;
    }

    $mailMsg->Body = $mail_msg;

    if (!$mailMsg->send()) {
        sys_err('ERROR: ' . $mailMsg->ErrorInfo);
    } else {
        sys_msg('Message sent!');
    }
} else {
    // Send them to the home page if they should not be here
    http_redirect();
    exit;
}

?>
<html>
<head>
<title>Request Result</title>
<?php require('inc_page_head.php'); ?>
<LINK href="/rings-styles/ring_style.css" rel="stylesheet" type="text/css">
</head>

<body bgcolor="#eeeeff">
<a href="index.php">
<img src="/rings-images/icon-home.png" border="0"
     alt="Pick a new Picture Ring">
</a>

<h2>Request Result</h2>
<p>

<?php sys_display_msg(); ?>

</body>
</html>
