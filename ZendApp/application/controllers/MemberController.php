<?

/**
 * Class MemberController
 *
 * Used to manage the all these to do with accessing the system (logging in and out, signing up, forgotten passwords,
 * confirming email address, etc)
 *
 * @author Craig Knott
 */
class MemberController extends BaseController {

    /**
     * Initialises the class
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    /**
     * Displays the log in page to the user, takes their details, and attempts to log them in
     *
     * @author Craig Knott
     */
    public function loginAction() {
        // If the user is already logged in, redirect them to their detail page
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirect('user/details');
        }

        // If user is trying to log in, check their credentials are valid
        $request = $this->getRequest();
        $loginForm = $this->getLoginForm();
        $redir = $request->getParam("redirect");
        if ($request->isPost()) {
            if ($loginForm->isValid($request->getPost())) {
                $username = $loginForm->getValue('username');
                $password = $loginForm->getValue('password');

                // If we can't login, display an error
                $loginSuccesful = $this->login($username, $password, $redir);
                if ($loginSuccesful == 0) {
                    $this->view->errorMessage = '<b>Could not login:</b> Username or password was wrong';
                } else if ($loginSuccesful == 1) {
                    $this->view->errorMessage = '<b>Could not login:</b> This account is banned';
                } else if ($loginSuccesful == 2) {
                    $this->view->errorMessage = '<b>Could not login:</b> This account has been deleted';
                }
            } else {
                $this->view->errorMessage = '<b>Could not log in:</b> Some required fields are missing or invalid';
            }
        }

        // If the user was redirected here, explain the situation to them
        if ($this->getRequest()->getParam("fromRedirect") == 1) {
            $this->view->infoMessage = '<b>Please log in</b> before accessing that page';
        }

        // Display the log in form
        $this->view->loginForm = $this->getLoginForm();
    }

    /**
     * Logs a user out and redirects them to the log in page, or another page if specified
     *
     * @author Craig Knott
     */
    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $redirTo = $this->getRequest()->getParam('redirTo', 'login');
        $fromRedirect = $this->getRequest()->getParam('fromRedirect', 0);

        $redirStr = 'member/' . $redirTo;
        if ($fromRedirect == 1) {
            $redirStr .= '/fromRedirect/1';
        }

        $this->_redirect($redirStr);
    }

    /**
     * Displays a message to the user and prevents them from logging in. Used when is_locked is set to 1
     *
     * @author Craig Knott
     */
    public function lockedAction() {
    }

    /**
     * Displays the sign up page to the user, takes their details, and attempts to create an account for them
     *
     * @author Craig Knott
     */
    public function signupAction() {
        // If the user is already logged in, redirect them to their detail page
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirect('user/details');
        }

        // Check entered information is valid
        $request = $this->getRequest();
        $signupForm = $this->getSignupForm();
        if ($request->isPost()) {
            if ($signupForm->isValid($request->getPost())) {
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

                    $this->login($postData["username"], $postData["password"]);
                } else {
                    $this->view->errorMessage = '<b>There was a problem creating your account:</b> That email or username is already registered';
                }
            } else {
                $this->view->errorMessage = '<b>There was a problem creating your account:</b> Some required fields are missing or invalid';
            }
        }

        // Display the sign up form
        $this->view->signupForm = $signupForm;
    }

    /**
     * Page that users are directed to when asked to confirm their email. Contains a url parameter with a unique hash
     * of their user id and username (concatenated with nothing inbetween).
     *
     * @author Craig Knott
     */
    public function confirmemailAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $hash = $this->getRequest()->getParam('hash', '');

        EmailFactory::confirmEmailAddress($hash);

        $this->_redirect('/user/details/emailconfirmed/1');
    }

    /**
     * Page that allows users to send a reset password link to their email address
     *
     * @author Craig Knott
     */
    public function forgottenpasswordAction() {
        // If the user is already logged in, redirect them to their detail page
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirect('user/details');
        }

        $request = $this->getRequest();
        $passwordForm = $this->getSendPasswordResetForm();
        if ($request->isPost()) {
            if ($passwordForm->isValid($request->getPost())) {
                $postData = $request->getPost();

                // Check email exists in the system
                $emailExists = LoginFactory::checkEmailExists($postData["email"], true);
                if ($emailExists !== false) {
                    EmailFactory::sendEmail(
                        $postData["email"],
                        'Niceway.to password reset',
                        $this->view->action(
                            'forgotpassword',
                            'email',
                            null,
                            array(
                                'email'  => $postData["email"],
                                'userId' => $emailExists->id
                            )
                        )
                    );

                    // Re-enable layout after sending email
                    $this->_helper->layout()->enableLayout();

                    $this->view->successMessage = '<b>An email has been sent to the provided address:</b> You can use this to reset your password';
                } else {
                    $this->view->errorMessage = '<b>There was a problem sending the email:</b> That email is not registered';
                }
            }
        }

        $this->view->passwordForm = $passwordForm;
    }

    /**
     * Page used to change the user's password, without knowing the original password. Cannot be access unless by
     * email (thus the hash)
     *
     * @author Craig Knott
     */
    public function changepasswordAction() {
        $hash = $this->getRequest()->getParam('hash', '');

        $request = $this->getRequest();
        $passwordForm = $this->getPasswordChangeForm($hash);
        if ($request->isPost()) {
            if ($passwordForm->isValid($request->getPost())) {
                $postData = $request->getPost();

                // Check entered passwords match
                if ($postData["pwd1"] === $postData["pwd2"]) {
                    // Check the hash is correct
                    $idOfUser = LoginFactory::checkEmailHash($postData["email"], $postData["hash"]);
                    if ($idOfUser !== false) {
                        UserFactory::updatePassword($idOfUser, $postData["pwd1"]);

                        $this->view->successMessage = '<b>Your password was successfully reset:</b> You can now log in';
                    } else {
                        $this->view->errorMessage = '<b>There was a problem changing your password:</b> The email you entered was not correct';
                    }


                } else {
                    $this->view->errorMessage = '<b>There was a problem changing your password:</b> The entered passwords did not match';
                }
            }
        }

        $this->view->passwordForm = $passwordForm;
    }

    /**
     * Returns a connection to the database and authentication service
     *
     * @author Craig Knott
     */
    protected function getAuthAdapter() {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('tb_user')
            ->setIdentityColumn('username')
            ->setCredentialColumn('password')
            ->setCredentialTreatment('MD5(?)');

        return $authAdapter;
    }

    /**
     * Uses zend_form to generate the form used on the log in page
     *
     * @author Craig Knott
     *
     * @return Zend_Form login form
     */
    protected function getLoginForm() {
        $username = new Zend_Form_Element_Text('username');
        $username->setLabel('Username:')
            ->setAttrib('class', 'form-control')
            ->setRequired(true);

        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Password:')
            ->setAttrib('class', 'form-control')
            ->setRequired(true);

        $submit = new Zend_Form_Element_Submit('login');
        $submit->setAttrib('class', 'hidden');

        if (!is_null($this->_request->getParam("redirect"))) {
            $redirect = '/member/login/redirect/' . $this->_request->getParam("redirect");
        } else {
            $redirect = "/member/login";
        }

        $loginForm = new Zend_Form();
        $loginForm->setAction($this->_request->getBaseUrl() . $redirect)
            ->setMethod('post')
            ->addElement($username)
            ->addElement($password)
            ->addElement($submit);

        return $loginForm;
    }

    /**
     * Uses zend_form to generate the form used on the sign up page
     *
     * @author Craig Knott
     *
     * @return Zend_Form signup form
     */
    protected function getSignupForm() {
        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('class', 'form-control')
            ->addValidator('stringLength', false, array(0, 16))
            ->setLabel('Username:')
            ->setRequired(true);

        $email = new Zend_Form_Element_Text('email');
        $email->setAttrib('class', 'form-control')
            ->addValidator('stringLength', false, array(0, 128))
            ->setLabel('Email:')
            ->setRequired(true);

        $password = new Zend_Form_Element_Password('password');
        $password->setAttrib('class', 'form-control')
            ->addValidator('stringLength', false, array(6))
            ->setLabel('Password:')
            ->setRequired(true);

        $submit = new Zend_Form_Element_Submit('signup');
        $submit->setLabel('Signup')
            ->setAttrib('class', 'hidden');

        $signupForm = new Zend_Form();
        $signupForm->setAction('/member/signup')
            ->setMethod('post')
            ->addElement($username)
            ->addElement($email)
            ->addElement($password)
            ->addElement($submit);

        return $signupForm;
    }

    /**
     * Uses Zend_form to generate the form used on the 'resetpassword' page
     *
     * @author Craig Knott
     *
     * @return Zend_Form password reset form
     */
    protected function getSendPasswordResetForm() {
        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('Email:')
            ->setAttrib('class', 'form-control')
            ->setRequired(true);

        $submit = new Zend_Form_Element_Submit('pword');
        $submit->setLabel('Password')
            ->setAttrib('class', 'hidden');

        $passwordForm = new Zend_Form();
        $passwordForm->setAction('/member/forgottenpassword')
            ->setMethod('post')
            ->addElement($email)
            ->addElement($submit);

        return $passwordForm;
    }

    /**
     * Uses Zend_form to generate the form used on the 'changepassword' page
     *
     * @author Craig Knott
     *
     * @param string $hashString The hash of this users id and their email address
     *
     * @return Zend_Form password change form
     */
    protected function getPasswordChangeForm($hashString) {
        $hash = new Zend_Form_Element_Text('hash');
        $hash->setLabel(' ')
            ->setValue($hashString)
            ->setAttrib('class', 'hidden')
            ->setRequired(true);

        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('Your Email:')
            ->setAttrib('class', 'form-control')
            ->setRequired(true);

        $pwd1 = new Zend_Form_Element_Password('pwd1');
        $pwd1->setLabel('Your New Password:')
            ->setAttrib('class', 'form-control')
            ->addValidator('stringLength', false, array(6))
            ->setRequired(true);

        $pwd2 = new Zend_Form_Element_Password('pwd2');
        $pwd2->setLabel('Your New Password (Again):')
            ->setAttrib('class', 'form-control')
            ->addValidator('stringLength', false, array(6))
            ->setRequired(true);

        $submit = new Zend_Form_Element_Submit('pwordChange');
        $submit->setLabel(' ')
            ->setAttrib('class', 'hidden');

        $passwordForm = new Zend_Form();
        $passwordForm->setAction('/member/changepassword')
            ->setMethod('post')
            ->addElement($email)
            ->addElement($pwd1)
            ->addElement($pwd2)
            ->addElement($hash)
            ->addElement($submit);

        return $passwordForm;
    }

    /**
     * Attempts to log the user into the system with the given user name and password. If successful, the user's login
     * status will be updated, a user object created, and they will be redirected to the page they tried to access
     * originally (if any)
     *
     * @author Craig Knott
     *
     * @param string $username Username of user
     * @param string $password Password of user
     * @param string $redirect A string representing the page the user wished to visit originally, in the form
     *                         'controller-action'
     *
     * @return int Returns 1 if user is banned, and 0 if user details were incorrect
     */
    protected function login($username, $password, $redirect = null) {
        // Check user is not banned
        $isBanned = LoginFactory::checkUserBanned($username);
        if ($isBanned) {
            return 1;
        }

        // Check account hasn't been deleted
        $isDeleted = LoginFactory::checkUserDeleted($username);
        if ($isDeleted) {
            return 2;
        }

        // Check if the user exists and the password is correct
        $authAdapter = $this->getAuthAdapter();
        $authAdapter->setIdentity($username)
            ->setCredential($password);
        $auth = Zend_Auth::getInstance();

        // User logs in for 12 hours before being disconnected
        $namespace = new Zend_Session_Namespace('Zend_Auth');
        $namespace->setExpirationSeconds(12 * 60 * 60);

        // If there is a corresponding row in the database, get the user details
        $result = $auth->authenticate($authAdapter);
        if ($result->isValid()) {
            // If there is a corresponding row in the database, get the user details
            $userInfo = $authAdapter->getResultRowObject(null, 'password');

            // Register the user logging on
            LoginFactory::registerUserLogin($userInfo->pk_user_id);

            // Store user details
            $authStorage = $auth->getStorage();
            $authStorage->write($userInfo);

            // Take the user to the page they originally attempted to access
            if (!is_null($redirect) && $redirect != "") {
                $redirect = str_replace("-", "/", $redirect);
                $this->_redirect('/' . $redirect);
            } else {
                $this->_redirect('/user/details');
            }
        }

        return 0;
    }

}
