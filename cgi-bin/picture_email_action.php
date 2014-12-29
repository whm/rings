<?php
//
// ----------------------------------------------------------
// Register Global Fix
//
$in_subject       = $_REQUEST['in_subject'];
$in_message       = $_REQUEST['in_message'];
$in_from_addr     = $_REQUEST['in_from_addr'];
$in_cc_addr       = $_REQUEST['in_cc_addr'];
$in_to_addr       = $_REQUEST['in_to_addr'];
$in_button_to     = $_REQUEST['in_button_to'];
$in_button_cc     = $_REQUEST['in_button_cc'];
$in_button_send   = $_REQUEST['in_button_send'];
$in_button_cancel = $_REQUEST['in_button_cancel'];
$in_button_email  = $_REQUEST['in_button_email'];
// ----------------------------------------------------------
//

// File: picture_email_action.php
// Author: Bill MacAllister
// Date: 26-Nov-2004

require ('inc_page_open.php');
require ('htmlMimeMail.php');

// ----------------------------------------------------
// Main Routine

// set update message area
$err_msg = '';
$ok   = '<font color="#009900">';
$warn = '<font color="#330000">';
$mend = "</font><br>\n";
$msg  = '';

require ('/etc/whm/rings_dbs.php');
require('inc_db_connect.php');

// get the picture information
if (isset($in_button_send)) {
    
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
    foreach ($email_list as $email_pid) {

        // Skip empty entries.
        if (strlen($email_pid)==0 || $email_pid<1) { continue; }

        // get the picture information
        $sel = "SELECT * FROM pictures_information WHERE pid=$email_pid ";
        $result = $DBH->query($sel);
        if (!$result) {
            $err_msg .= "$warn Problem finding picture information.$mend";
            $err_msg .= "$warn Problem SQL:$sel$mend";
            break;
        } 
    
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $thisFilename = $row['file_name'];
    
        // get the picture
        $sel = "SELECT * FROM pictures_large WHERE pid=$email_pid ";
        $result = $DBH->query ($sel);
        if (!$result) {
            $err_msg .= "$warn Problem finding image.$mend";
            $err_msg .= "$warn Problem SQL:$sel$mend";
            break;
        } else {
        
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $thisPicture = $row['picture'];
            $thisFiletype = $row['picture_type'];
            
            $msg .= "$ok Picture size " . strlen($thisPicture) . "$mend";
        
            // Add the picture
            $mailMsg->addAttachment($thisPicture, 
                                    $thisFilename, 
                                    $thisFiletype);
        }
    }
    if (strlen($err_msg) > 0) {
        $msg .= "$warn Message not sent $mend";
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
if ( strlen($err_msg)>0 ) {
    echo $err_msg;
    echo "<p>\n";
    echo "$warn Mail not sent.$mend";
} elseif (isset($in_button_cancel)) {
    echo '<h3>No mail sent.  List cleared.</h3>';
    $_SESSION['s_email_list'] = '';
} elseif (!$mailResult) {
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

<?php if (isset($in_button_cancel)) { ?>
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
