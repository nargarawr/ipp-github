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

        $userId = $this->getRequest()->getParam('userId', null);
        $username = $this->getRequest()->getParam('username', null);

        $this->view->hash = md5($userId . $username);
        $this->view->username = $username;
    }

    /**
     *
     * Draws the forgotten password email template
     *
     * @author Craig Knott
     */
    public function forgotpasswordAction() {
        $this->_helper->layout()->disableLayout();

        $userId = $this->getRequest()->getParam('userId', null);
        $email = $this->getRequest()->getParam('email', null);

        $this->view->hash = md5($userId . $email);
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

        $to = $this->getRequest()->getParam('to', array());
        $subject = $this->getRequest()->getParam('subject', '');
        $template = $this->getRequest()->getParam('templateName', '');

        $result = EmailFactory::sendEmail(
            $to,
            $subject,
            $this->view->action(
                $template,
                'email',
                null,
                $this->getRequest()->getParams()
            )
        );

        echo Zend_Json::encode($result);
        exit;
    }


}