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
     * Used to redirect to /user/details
     *
     * @author Craig Knott
     */
    public function indexAction() {
        $this->_helper->redirector('details', 'user', null, array());
    }

    /**
     * Displays user details and route (user /id/x to show public profile for user x)
     *
     * @author Craig Knott
     */
    public function detailsAction() {
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

        if ($customUserId === "0" || $customUserId < 0) {
            $this->_helper->redirector('details', 'user', null, array());
        }

        if (!is_null($customUserId)) {
            $displayedUser = UserFactory::getUser($customUserId);
        }

        if ($customUserId === $this->user->userId || is_null($customUserId)) {
            $this->view->savedRoutes = RouteFactory::getSavedRoutesForUser($this->user->userId);
        }

        // Get usage statistics for the user
        $userStats = SkinFactory::getUserStats($displayedUser->userId);
        $displayedUser->stats = $userStats;

        // Check if the user has any new skins
        SkinFactory::allocateSkins($userStats, $displayedUser->userId);

        // Display user skins
        $this->view->userSkins = SkinFactory::getUserEquippedSkins($displayedUser->userId);

        $this->view->displayedUser = $displayedUser;
        $this->view->viewingOwnProfile = $displayedUser->userId == $this->user->userId;

        $routes = RouteFactory::getRoutesForUser($displayedUser->userId);
        $this->view->routes = $routes;
    }

    /**
     * Page used to update user details
     *
     * @author Craig Knott
     */
    public function settingsAction() {
        $messages = $this->messageManager->getMessages();
        if (count($messages) > 0) {
            $message = $messages[0];
            if ($message["type"] == "success") {
                $this->view->successMessage = $message["msg"];
            } else if ($message["type"] == "error") {
                $this->view->errorMessage = $message["msg"];
            }
        }

        $displayedUser = $this->user;
        $customUserId = $this->getRequest()->getParam('id', null);

        if ($customUserId === "0" || $customUserId < 0) {
            $this->_helper->redirector('settings', 'user', null, array());
        }

        // Allow admins to access other user's settings pages
        if (!is_null($customUserId) && $this->user->isAdmin) {
            $displayedUser = UserFactory::getUser($customUserId);
        }

        $this->view->displayedUser = $displayedUser;
    }

    /**
     * Provides a way for users to see all skins available, and select the ones they wish to have on
     *
     * @author Craig Knott
     */
    public function skinsAction(){
        $allUserSkins = SkinFactory::getAllUserSkins($this->user->userId);
        $allSkins = SkinFactory::getAllSkins($this->user->userId);

        $equippedUserSkins = array();
        foreach ($allUserSkins as $skinType) {
            foreach ($skinType as $skin) {
                if ($skin->equipped) {
                    $equippedUserSkins[] = $skin;
                }
            }
        }

        $this->view->allSkins = $allSkins;

        $this->view->allUserSkins = $allUserSkins;
        $this->view->equippedSkins = $equippedUserSkins;
    }

    /**
     * Changes the currently equipped skins for a given user
     *
     * @author Craig Knott
     */
    public function updateskinsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $requestParams = $this->getRequest()->getParams();
        $skins = array();
        $userId = null;
        foreach ($requestParams as $key => $value) {
            if ($key == "controller" || $key == "action" || $key == "module") {
            } else if ($key == "userId") {
                $userId = $value;
            } else {
                $skins[$key] = $value;
            }
        }

        SkinFactory::updateUserSkins($userId, $skins);

        $this->_helper->redirector('skins', 'user', null, array());
    }

    /**
     * Updates the user account preferences. At the moment this only includes emails, but can be expanded later. User
     * preferences can be found in tb_user_preference
     *
     * @author Craig Knott
     */
    public function updatepreferencesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $emailOnRouteComment = $this->getRequest()->getParam('email_route_comment', 0) === "on" ? 1 : 0;
            $emailOnRouteFork = $this->getRequest()->getParam('email_route_fork', 0) === "on" ? 1 : 0;
            $emailOnRouteRate = $this->getRequest()->getParam('email_route_rate', 0) === "on" ? 1 : 0;
            $emailOnAnnouncement = $this->getRequest()->getParam('email_announcement', 0) === "on" ? 1 : 0;
            $userId = $this->getRequest()->getParam("userId", $this->user->userId);

            UserFactory::updateUserPreferences(
                $userId,
                $emailOnRouteComment,
                $emailOnRouteFork,
                $emailOnRouteRate,
                $emailOnAnnouncement
            );

            $this->messageManager->addMessage(array(
                'msg'  => 'Successfully updated your email preferences',
                'type' => 'success'
            ));

            $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
        }
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
            $dob = $this->getRequest()->getParam('age');
            $location = $this->getRequest()->getParam('location');
            $bio = $this->getRequest()->getParam('bio');
            $userId = $this->getRequest()->getParam("userId", $this->user->userId);

            // Check the fields entered are not too long
            $invalid = $this->getInvalidDetailFields($firstName, $lastName, $email, $location, $bio);
            if (count($invalid) == 0) {
                $emailAllowed = UserFactory::checkEmailAllowed($userId, $email);
                if ($emailAllowed) {

                    if (!is_null($dob)) {
                        list($date,$month,$year) = sscanf($dob, "%d/%d/%d");
                        $dob = "$year-$month-$date";
                    }

                    UserFactory::updateUserDetails(
                        $userId,
                        $firstName,
                        $lastName,
                        $email,
                        $location,
                        $bio,
                        $dob
                    );

                    $this->messageManager->addMessage(array(
                        'msg'  => 'Successfully updated your details',
                        'type' => 'success'
                    ));

                    $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
                } else {
                    $this->messageManager->addMessage(array(
                        'msg'  => 'The email you entered is already registered to another account',
                        'type' => 'error'
                    ));

                    $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
                }
            } else {
                $this->messageManager->addMessage(array(
                    'msg'  => 'The entered ' . $invalid[0]->name . ' was too long. The maximum size is ' . $invalid[0]->size,
                    'type' => 'error'
                ));

                $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
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
            $userId = $this->getRequest()->getParam("userId", $this->user->userId);

            // Check new password is of sufficient length
            if (strlen($newPass1) < 6 || strlen($newPass2) < 6) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'Password length must be at least 6',
                    'type' => 'error'
                ));
                $this->_helper->redirector('settings', 'user', null, array());
            }

            // Check new passwords entered match
            if ($newPass1 !== $newPass2) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'The entered new passwords did not match',
                    'type' => 'error'
                ));
                $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
            }

            // Check user entered their current password correctly
            $validPassword = UserFactory::checkPassword($userId, $password);
            if ($validPassword === false) {
                $this->messageManager->addMessage(array(
                    'msg'  => 'You did not enter your current password correctly',
                    'type' => 'error'
                ));
                $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
            }

            // Update user password
            UserFactory::updatePassword($userId, $newPass1);

            $this->messageManager->addMessage(array(
                'msg'  => 'Successfully updated your password',
                'type' => 'success'
            ));
            $this->_helper->redirector('settings', 'user', null, array('id' => $userId));
        }
    }

    /**
     * Check that the input into the update account details action is valid
     *
     * @author Craig Knott
     *
     * @param string $firstName The first name to check
     * @param string $lastName  The last name to check
     * @param string $email     The email to check
     * @param string $location  The location to check
     * @param string $bio       The bio to check
     *
     * @return array(object(string, string)) Array of invalid fields, along with their max size
     */
    protected function getInvalidDetailFields($firstName, $lastName, $email, $location, $bio) {
        $invalidFields = array();
        if (strlen($firstName) > 32) {
            $invalidFields[] = (object)array('name' => 'first name', 'size' => '32');
        }

        if (strlen($lastName) > 32) {
            $invalidFields[] = (object)array('name' => 'last name', 'size' => '32');
        }

        if (strlen($email) > 128) {
            $invalidFields[] = (object)array('name' => 'email', 'size' => '128');
        }

        if (strlen($location) > 64) {
            $invalidFields[] = (object)array('name' => 'location', 'size' => '64');
        }

        if (strlen($bio) > 1024) {
            $invalidFields[] = (object)array('name' => 'bio', 'size' => '1024');
        }

        return $invalidFields;
    }
}
