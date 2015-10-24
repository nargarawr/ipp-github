<?

class MemberController extends BaseController {

    public function init() {
        $this->view->isExternal = true;
        parent::init();
    }

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
                if (!$loginSuccesful) {
                    $this->view->errorMessage = '<b>Could not login:</b> Username or password was wrong';
                }
            }
        }

        // If the user was redirected here, explain the situation to them
        if ($redir != "") {
            $this->view->infoMessage = '<b>Please log in</b> before accessing that page';
        }

        // Display the log in form
        $this->view->loginForm = $this->getLoginForm();
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('member/login');
    }

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
                    LoginFactory::createNewUser(
                        $postData["username"],
                        $postData["password"],
                        $postData["email"],
                        $postData["firstName"],
                        $postData["location"]
                    );

                    $this->login($postData["username"], $postData["password"]);
                } else {
                    $this->view->errorMessage = '<b>There was a problem creating your account:</b> That email or username is already registered';
                }
            } else {
                $this->view->errorMessage = '<b>There was a problem creating your account:</b> Some required fields are missing';
            }
        }

        // Display the sign up form
        $this->view->signupForm = $signupForm;
    }

    protected function getAuthAdapter() {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('tb_user')
            ->setIdentityColumn('username')
            ->setCredentialColumn('password')
            ->setCredentialTreatment('MD5(?)');

        return $authAdapter;
    }

    protected function getSignupForm() {
        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('class', 'form-control')
            ->setAttrib('placeholder', 'Username')
            ->setRequired(true);

        $email = new Zend_Form_Element_Text('email');
        $email->setAttrib('class', 'form-control')
            ->setAttrib('placeholder', 'Email')
            ->setLabel(" ")
            ->setRequired(true);

        $firstName = new Zend_Form_Element_Text('firstName');
        $firstName->setAttrib('class', 'form-control')
            ->setAttrib('placeholder', 'First Name (optional)')
            ->setRequired(false);

        $location = new Zend_Form_Element_Text('location');
        $location->setAttrib('class', 'form-control')
            ->setAttrib('placeholder', 'Location (optional)')
            ->setRequired(false);

        $password = new Zend_Form_Element_Password('password');
        $password->setAttrib('class', 'form-control')
            ->setAttrib('placeholder', 'Password')
            ->setRequired(true);

        $submit = new Zend_Form_Element_Submit('signup');
        $submit->setLabel('Sign Up')
            ->setAttrib('class', 'btn btn-primary');

        $signupForm = new Zend_Form();
        $signupForm->setAction('/member/signup')
            ->setMethod('post')
            ->addElement($username)
            ->addElement($email)
            ->addElement($firstName)
            ->addElement($location)
            ->addElement($password)
            ->addElement($submit);

        return $signupForm;
    }

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
        $submit->setLabel('Login')
            ->setAttrib('class', 'btn btn-success');

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

    protected function login($username, $password, $redirect = null) {
        // Check if the user exists and the password is correct
        $authAdapter = $this->getAuthAdapter();
        $authAdapter->setIdentity($username)
            ->setCredential($password);
        $auth = Zend_Auth::getInstance();

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

        return false;
    }

}