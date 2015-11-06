<?

require('sendgrid-php/sendgrid-php/sendgrid-php.php');

/**
 * Class EmailFactory
 *
 * Class responsible for send emails using the sendgrid php library
 *
 * @author Craig Knott
 */
class EmailFactory extends ModelFactory {

    /**
     * Sends an email to the recipients, with the subject and body provided. Uses Send Grids PHP library
     *
     * @author Craig Knott
     *
     * @param array(string) $to      An array of recipients
     * @param string        $subject The email subject line
     * @param string        $body    The body of the email as an HTML string
     *
     * @return object The result of sending the email
     */
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

        $res = $sendgrid->send($email);

        return $res;
    }

    /**
     * Function used to set the is_confirmed flag of a given user hash
     *
     * @author Craig Knott
     *
     * @param string $hash Hash of a user ID and username
     */
    public static function confirmEmailAddress($hash) {
        $sql = "UPDATE tb_user
                SET is_confirmed = 1
                WHERE MD5(CONCAT(pk_user_id, username)) = :hash";
        $params = array(
            ':hash' => $hash
        );

        parent::execute($sql, $params);
    }

}


