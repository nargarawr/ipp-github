<?

class UserController extends BaseController {

    public function init() {
        $this->view->isExternal = false;
        parent::init();
    }

    public function detailsAction() {
        $success = $this->getRequest()->getParam('success', '');
        $error = $this->getRequest()->getParam('error', '');
        if ($success == "details_updated") {
            $this->view->successMessage = "Successfully updated your details";
        } else if ($success == "password_updated") {
            $this->view->successMessage = "Successfully updated your password";
        } else if ($error == "email_already_registered") {
            $this->view->errorMessage = "The email you entered is already registered to another account";
        } else if ($error == "password_incorrect") {
            $this->view->errorMessage = "You did not enter your current password correctly";
        } else if ($error == "passwords_dont_match") {
            $this->view->errorMessage = "The entered new passwords did not match";
        } else if ($error == "password_too_short") {
            $this->view->errorMessage = "Password length must be at least 6";
        }
    }

    public function updateaccountdetailsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $firstName = $this->getRequest()->getParam('fname');
            $lastName = $this->getRequest()->getParam('lname');
            $email = $this->getRequest()->getParam('email');
            $location = $this->getRequest()->getParam('location');

            $emailAllowed = UserFactory::checkEmailAllowed($this->user->userId, $email);
            if ($emailAllowed) {
                UserFactory::updateUserDetails(
                    $this->user->userId,
                    $firstName,
                    $lastName,
                    $email,
                    $location
                );

                // Update the user object to reflect these changes
                Zend_Auth::getInstance()->getIdentity()->fname = $firstName;
                Zend_Auth::getInstance()->getIdentity()->lname = $lastName;
                Zend_Auth::getInstance()->getIdentity()->email = $email;
                Zend_Auth::getInstance()->getIdentity()->location = $location;

                $this->_helper->redirector('details', 'user', null, array(
                    'success' => "details_updated",
                ));
            } else {
                $this->_helper->redirector('details', 'user', null, array(
                    'error' => "email_already_registered",
                ));
            }
        }
    }

    public function updatepasswordAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->getRequest()->isPost()) {
            $password = $this->getRequest()->getParam('currentPass');
            $newPass1 = $this->getRequest()->getParam('newPass1');
            $newPass2 = $this->getRequest()->getParam('newPass2');

            // Check new password is of sufficient length
            if (strlen($newPass1) < 6 || strlen($newPass2) < 6) {
                $this->_helper->redirector('details', 'user', null, array(
                    'error' => 'password_too_short',
                ));
            }

            // Check new passwords entered match
            if ($newPass1 !== $newPass2) {
                $this->_helper->redirector('details', 'user', null, array(
                    'error' => 'passwords_dont_match',
                ));
            }

            // Check user entered their current password correctly
            $validPassword = UserFactory::checkPassword($this->user->userId, $password);
            if ($validPassword === false) {
                $this->_helper->redirector('details', 'user', null, array(
                    'error' => 'password_incorrect',
                ));
            }

            // Update user password
            UserFactory::updatePassword($this->user->userId, $newPass1);
            $this->_helper->redirector('details', 'user', null, array(
                'success' => 'password_updated',
            ));
        }

    }
}