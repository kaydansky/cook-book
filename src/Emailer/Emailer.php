<?php

namespace Cookbook\Emailer;

use PHPMailer\{PHPMailer\PHPMailer, PHPMailer\Exception};

/**
 * Description of Emailer
 *
 * @author AlexK
 */
class Emailer
{

    private $to;
    private $subject;
    private $body;
    private $altBody;

    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function send()
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = EMAIL_SENDER['host'];
            $mail->SMTPAuth = true;
            $mail->Username = EMAIL_SENDER['username'];
            $mail->Password = EMAIL_SENDER['password'];
            $mail->SMTPSecure = EMAIL_SENDER['protocol'];
            $mail->Port = EMAIL_SENDER['port'];

            //Recipients
            $mail->setFrom(EMAIL_SENDER['username'], 'Cookbook');
            $mail->addAddress($this->to, '');
            $mail->addReplyTo(EMAIL_SENDER['username'], 'Do not reply');

            //Content
            $mail->isHTML(true);
            $mail->Subject = $this->subject;
            $mail->Body = $this->body;
            $mail->AltBody = $this->altBody;

            $mail->send();
        } catch (Exception $e) {
            return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }

        return null;
    }

}
