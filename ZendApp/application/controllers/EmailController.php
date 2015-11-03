<?

require('sendgrid-php/sendgrid-php/sendgrid-php.php');

class EmailController extends BaseController {

    public function init() {
        parent::init();
        $this->view->isExternal = false;
    }

    public function confirmemailAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $response = $this->sendEmail(
            '95183a9d-042e-4e0f-a6e8-cafcbd501b5b',
            array('cxk01u@gmail.com'),
            'Please confirm your email address',
            '<a href="http://www.google.com"> Click me to confirm your email address, with %DERP%</a>'
        );

        print_r($response);
    }

    public function forgotpasswordAction() {
    }

    public function sendannouncementAction() {
    }

    protected function sendEmail($template, $to, $subject, $body = ' ') {
        $sendgrid = new SendGrid('nicewayto', '12QWASzx');
        $email = new SendGrid\Email();

        $email->setTos($to);
        $email->setFrom('noreply@niceway.to');
        $email->setFromName('Niceway.to support');
        $email->setSubject($subject);
        $email->setHtml($body);
        $email->setTemplateId($template);
        $email->addSubstitution('%DERP%', array('FUCK'));

        $res = $sendgrid->send($email);

        return $res;
    }
}