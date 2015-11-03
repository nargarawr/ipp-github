<?

class EmailController extends BaseController {

    public function init() {
        parent::init();
        $this->view->isExternal = false;
    }

    public function emailAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $response = $this->sendEmail(
            'cxk01u@gmail.com', 'abxow1@nottingham.ac.uk',
            'Confirm email address',
            'Test email from Niceway.to'
        );

        print_r($response);
    }

    public function confirmemailAction() {
    }

    public function forgotpasswordAction() {
    }

    public function sendannouncementAction() {
    }

    public function sendEmail($to, $subject, $body) {
        $url = 'https://api.sendgrid.com/';
        $user = 'nicewayto';
        $pass = '12QWASzx';
        $params = array(
            'api_user' => $user,
            'api_key'  => $pass,
            'to'       => $to,
            'subject'  => $subject,
            'html'     => $body,
            'text'     => $body,
            'from'     => 'noreply@niceway.to',
        );

        $request = $url . 'api/mail.send.json';
        $session = curl_init($request);

        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $params);
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        curl_close($session);

        return $response;
    }
}