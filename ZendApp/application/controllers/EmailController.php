<?



class EmailController extends BaseController {

    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    public function confirmemailAction() {
        $this->_helper->layout()->disableLayout();
        $this->view->username = $this->getRequest()->getParam('username', null);
    }

    public function forgotpasswordAction() {
    }

    public function sendannouncementAction() {
    }

    public function indexAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);


        $result = EmailFactory::sendEmail(
            "cxk01u@googlemail.com",
            'Please confirm your email address',
            $this->view->action(
                'confirmemail',
                'email',
                null,
                array('username' => "craig")
            )
        );
        /*

        $to = $this->getRequest()->getParam('to', array());
        $subject = $this->getRequest()->getParam('subject', '');
        $body = $this->view->action(
            $this->getRequest()->getParam('templateName'),
            'email',
            null,
            $this->getRequest()->getParams()
        );

        $result = EmailFactory::sendEmail($to, $subject, $body);
*/
        echo Zend_Json::encode($result);
        exit;
    }



}