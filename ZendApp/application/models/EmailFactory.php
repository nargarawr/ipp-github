<?

require('sendgrid-php/sendgrid-php/sendgrid-php.php');

class EmailFactory extends ModelFactory {

    public static function sendEmail($to, $subject, $body) {
        if (!(is_array($to))) {
            $to = array($to);
        }


        $sendgrid = new SendGrid('nicewayto', '12QWASzx');
        $email = new SendGrid\Email();

        $email->setTos($to);
        $email->setFrom('noreply@niceway.to');
        $email->setFromName('Niceway.to support');
        $email->setSubject($subject);
        $email->setHtml($body);
        $email->setTemplateId('95183a9d-042e-4e0f-a6e8-cafcbd501b5b');
        $email->addSubstitution('%DERP%', array('FUCK'));

        $res = $sendgrid->send($email);

        return $res;
    }

}


