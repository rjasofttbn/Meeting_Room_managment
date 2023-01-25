<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'MailSend.php';
use codeworxtech\MailSend;

//$mail = new codeworxtech\MailSend;
$mail = new MailSend;

$mail->SMTP_Debug = 0; // 0 = off, 1 = basic, 2 = advanced (server)

$mail->SetSender( ['yourname@yourdomain.com'=>'Full Name'] );
$mail->SetConfirmRead('confirmname@yourdomain.com');

$mail->AddRecipient( ['recipient@domain.com'=>'Recipient Name'] );
//$mail->AddCC( ['cc@domain.com'=>'CC1 Name','cc2@domain.com'=>'CC2 Name'] );
//$mail->AddBCC( ['bcc@domain.com'=>'BCC1 Name','bcc2@domain.com'=>'BCC2 Name'] );

$mail->SetSubject('Example mail');
$mail->MessageText = 'Example plain-content!';
$mail->MessageHTML = 'embedded.html';

$mail->AddAttachment('screen_shot_sample_form.gif');

/*
// above will send via smtp with no authentication without the variables below
// use the variables below to specify a different SMTP server (requires authentication)
$mail->SetSMTPhost('');
$mail->SetSMTPuser('yourname@domain.com');
$mail->SetSMTPpass('yourpassword');
/* */

if ($mail->Send()) {
  echo "Email sent successfully.<br>";
} else {
  echo "Oops! Error sending email.<br>";
}

echo 'Done!<br>';

?>