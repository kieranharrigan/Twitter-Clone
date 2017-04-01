<?php
require 'PHPMailer/PHPMailerAutoload.php';

$email = $argv[1];
$body = $argv[2];

sendmail($body, $email);

function sendmail($body, $email)
{
    $mail = new PHPMailer;

    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com';                       // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'cloneoftwitter@gmail.com';        // SMTP username
    $mail->Password = '6bw=.M,JHE^3c0T,J`raB*AF0&cy,cCC3fg:YpMCn*KRemZmi%r3jDVK/ZRYvCL$DJtxEY';
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                                    // TCP port to connect to

    $mail->setFrom('cloneoftwitter@gmail.com', 'Twitter Clone');
    $mail->addBCC($email);
    //$mail->addBCC('');

    $mail->Subject = 'Twitter Clone Verification';
    $mail->Body = $body;

    $mail->send();
}
?>
