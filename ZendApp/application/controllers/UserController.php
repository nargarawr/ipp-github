<?

/**
 * Class UserController
 *
 * Used for the 'My Profile' section of the website
 *
 * @author Craig Knott
 */
class UserController extends BaseController {

    /**
     * Initialises the class
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = false;
    }

    /**
     * Page used to describe and update user details. Uses flash messenger to pass errors and messages to the UI
     *
     * @author Craig Knott
     */
    public function detailsAction() {
        $messages = $this->messageManager->getMessages();
        if (count($messages) > 0) {
            $message = $messages[0];
            if ($message["type"] == "success") {
                $this->view->successMessage = $message["msg"];
            } else if ($message["type"] == "error") {
                $this->view->errorMessage = $message["msg"];
            }
        }

        $this->view->emailConf = $this->getRequest()->getParam('emailconfirmed', 0);
        if ($this->view->emailConf == 1) {
            $this->user->isConfirmed = true;
            Zend_Auth::getInstance()->getIdentity()->is_confirmed = true;
        }

        // Non confirmed email error
        $this->view->nce = $this->getRequest()->getParam('nce', 0);


        // Used to display this page externally to other users
        $displayedUser = $this->user;
        $customUserId = $this->getRequest()->getParam('id', null);
        if (!is_null($customUserId)) {
            $displayedUser = UserFactory::getUser($customUserId);
        }

        // Get usage statistics for the user
        $displayedUser->stats = (object)array(
            'average_rating'    => RatingFactory::getAverageRatingForUser($displayedUser->userId),
            'ratings_given'     => RatingFactory::getAllRatingsFromUser($displayedUser->userId, true),
            'ratings_received'  => RatingFactory::getAllRatingsForUser($displayedUser->userId, true),
            'comments_given'    => CommentFactory::getCommentsFromUser($displayedUser->userId, true),
            'comments_received' => CommentFactory::getCommentsForUser($displayedUser->userId, true),
            'route_count'       => count(RouteFactory::getRoutesForUser($displayedUser->userId, false)),
            'account_age'       => abs(floor((strtotime('now') - strtotime($displayedUser->datetimeCreated)) / 60 / 60 / 24))
        );

        $this->view->displayedUser = $displayedUser;
        $this->view->viewingOwnProfile = $displayedUser->userId == $this->user->userId;

        $routes = RouteFactory::getRoutesForUser($displayedUser->userId);
        $this->view->routes = $routes;
    }

    /**
     * Provides administrative tools to the user
     *
     * @author Craig Knott
     */
    public function adminAction() {

    }

    /**
     * Updates the users account details in the database , including name, email, location and bio. Also updates the
     * user object to reflect these changes
     *
     * @author Craig Knott
     */
    public function updateaccountdetailsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $firstName = $this->getRequest()->getParam('fname');
            $lastName = $this->getRequest()->getParam('lname');
            $email = $this->getRequest()->getParam('email');
            $location = $this->getRequest()->getParam('location');
            $bio = $this->getRequest()->getParam('bio');

            $emailAllowed = UserFactory::checkEmailAllowed($this->user->userId, $email);
            if ($emailAllowed) {
                UserFactory::updateUserDetails(
                    $this->user->userId,
                    $firstName,
                    $lastName,
                    $email,
                    $location,
                    $bio
                );

                // Update the user object to reflect these changes
                Zend_Auth::getInstance()->getIdentity()->fname = $firstName;
                Zend_Auth::getInstance()->getIdentity()->lname = $lastName;
                Zend_Auth::getInstance()->getIdentity()->email = $email;
                Zend_Auth::getInstance()->getIdentity()->location = $location;
                Zend_Auth::getInstance()->getIdentity()->bio = $bio;

                $this->messageManager->addMessage(array(
                    'msg'  => 'Successfully updated your details',
                    'type' => 'success'
                ));

                $this->_helper->redirector('details', 'user', null, array());
            } else {
                $this->messageManager->addMessage(array(
                    'msg'  => 'The email you entered is already registered to another account',
                    'type' => 'error'
                ));

                $this->_helper->redirector('details', 'user', null, array());
            }
        }
    }

    /**
     * Updates the user's password in the database
     *
     * @author Craig Knott
     */
    public function updatepasswordAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $password = $this->getRequest()->getParam('currentPass');
            $newPass1 = $this->getRequest()->getParam('newPass1');
            $newPass2 = $this->getRequest()->getParam('newPass2');

            // Check new password is of sufficient length
            if (strlen($newPass1) < 6 || strlen($newPass2) < 6) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'Password length must be at least 6',
                    'type' => 'error'
                ));
                $this->_helper->redirector('details', 'user', null, array());
            }

            // Check new passwords entered match
            if ($newPass1 !== $newPass2) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'The entered new passwords did not match',
                    'type' => 'error'
                ));
                $this->_helper->redirector('details', 'user', null, array());
            }

            // Check user entered their current password correctly
            $validPassword = UserFactory::checkPassword($this->user->userId, $password);
            if ($validPassword === false) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'You did not enter your current password correctly',
                    'type' => 'error'
                ));
                $this->_helper->redirector('details', 'user', null, array());
            }

            // Update user password
            UserFactory::updatePassword($this->user->userId, $newPass1);

            $this->messageManager->addMessage(array(
                'msg'  => 'Successfully updated your password',
                'type' => 'success'
            ));
            $this->_helper->redirector('details', 'user', null, array());
        }
    }
}
