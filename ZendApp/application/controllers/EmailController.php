<?

/**
 * Class EmailController
 *
 * Class in charge of retrieving email templates
 *
 * @author Craig Knott
 */
class EmailController extends BaseController {

    /**
     * Initialises the controller
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    /**
     * Draws the confirm email email template
     *
     * @author Craig Knott
     */
    public function confirmemailAction() {
        $this->_helper->layout()->disableLayout();
        $this->view->username = $this->getRequest()->getParam('username', null);
    }

    /**
     *
     * Draws the forgotten password email template
     *
     * @author Craig Knott
     */
    public function forgotpasswordAction() {
    }

    /**
     * Draws the announcement email template
     *
     * @author Craig Knott
     */
    public function sendannouncementAction() {
    }

    /**
     * Manages the passing templates into the email factory to have them sent
     *
     * @author Craig Knott
     */
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