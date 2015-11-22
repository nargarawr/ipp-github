<?

/**
 * Class AdminController
 *
 * Class in charge of all administrative actions
 *
 * @author Craig Knott
 */
class AdminController extends BaseController {

    /**
     * Initialises the controller. No pages of this controller are ever rendered, so we turn this off here
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    /**
     * Called before any page is loaded. Makes sure that users are admin
     *
     * @author Craig Knott
     */
    public function preDispatch() {
        parent::preDispatch();
        if ($this->user->isAdmin != 1) {
            $this->_helper->redirector('details', 'user', null, array());
        }
    }

    /**
     * Landing page for admin tools, allows admins to perform all administrative actions
     *
     * @author Craig Knott
     */
    public function indexAction() {
        $messages = $this->messageManager->getMessages();
        if (count($messages) > 0) {
            $message = $messages[0];
            if ($message["type"] == "success") {
                $this->view->successMessage = $message["msg"];
            } else if ($message["type"] == "error") {
                $this->view->errorMessage = $message["msg"];
            }
        }

        $this->view->siteAdmin = AdminFactory::getSiteAdmin();
    }

    /**
     * Allows administrators to manage user reports
     *
     * @author Craig Knott
     */
    public function reportsAction(){

    }

    /**
     * Locks, or unlocks the site, preventing users from logging in
     *
     * @author Craig Knott
     */
    public function locksiteAction() {
        $shouldLock = $this->getRequest()->getParam('lock', 0);
        AdminFactory::setSiteLocked($shouldLock);
        AdminFactory::updateAdminLog($this->user->userId, $shouldLock ? "Site Locked" : "Site Unlocked");
        $this->_helper->redirector('index', 'admin', null, array());
    }

    /**
     * Creates a new user
     *
     * @author Craig Knott
     */
    public function createuserAction(){

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        $postData = $request->getPost();

        $uniqueUser = LoginFactory::checkUserUnique($postData["username"], $postData["email"]);
        if ($uniqueUser) {
            $userId = LoginFactory::createNewUser(
                $postData["username"],
                $postData["password"],
                $postData["email"]
            );

            EmailFactory::sendEmail(
                $postData["email"],
                'Please confirm your email address',
                $this->view->action(
                    'confirmemail',
                    'email',
                    null,
                    array(
                        'username' => $postData["username"],
                        'userId'   => $userId
                    )
                )
            );

            SkinFactory::assignStartingSkins($userId);
            AdminFactory::updateAdminLog($this->user->userId, "Create User");

            $this->messageManager->addMessage(array(
                'msg'  => 'The account was successfully created and an email has been sent',
                'type' => 'success'
            ));
        } else {
            $this->messageManager->addMessage(array(
                'msg'  => 'That email or username is already registered',
                'type' => 'error'
            ));
        }

        $this->_helper->redirector('index', 'admin', null, array());

    }

    /**
     * Posts an announcement and, optionally, emails it too
     *
     * @author Craig Knott
     */
    public function postannouncementAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $email = $this->getRequest()->getParam('shouldEmail', 0);
        $message = $this->getRequest()->getParam('message', '');

        AdminFactory::postAnnouncement($message, $email, $this->user->userId);
        AdminFactory::updateAdminLog($this->user->userId, "Announcement Posted");

        if ($email) {
            // Get all users we can email
            $emailAddresses = EmailFactory::getAllEmails('email_on_announcement');

            EmailFactory::sendEmail(
                $emailAddresses,
                'An announcement was posted on Niceway.to!',
                $this->view->action(
                    'newannouncement',
                    'email',
                    null,
                    array(
                        'message' => $message
                    )
                )
            );
        }

        $this->messageManager->addMessage(array(
            'msg'  => 'Announcement posted' . ($email ? ' and email sent' : '') . '!',
            'type' => 'success'
        ));
        $this->_helper->redirector('index', 'admin', null, array());
    }

}
