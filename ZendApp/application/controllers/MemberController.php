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
        if ($request->isPost()) {
            if ($loginForm->isValid($request->getPost())) {
                $authAdapter = $this->getAuthAdapter();
                $username = $loginForm->getValue('username');
                $password = $loginForm->getValue('password');

                $authAdapter->setIdentity($username)
                    ->setCredential($password);
                $auth = Zend_Auth::getInstance();

                $result = $auth->authenticate($authAdapter);

                // If there is a corresponding row in the database, get the user details
                if ($result->isValid()) {
                    $userInfo = $authAdapter->getResultRowObject(null, 'password');
                    $authStorage = $auth->getStorage();
                    $authStorage->write($userInfo);

                    // Take the user to the page they originally attempted to access
                    $redir = $request->getParam("redirect");
                    if ($redir != "") {
                        $redirect = str_replace("-", "/", $redir);
                        $this->_redirect('/' . $redirect);
                    } else {
                        $this->_redirect('/user/details');
                    }
                } else {
                    $this->view->errorMessage = '<b>Could not login:</b> Username or password was wrong';
                }
            }
        }

        $this->view->loginForm = $this->getLoginForm();
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('member/login');
    }

    public function signupAction() {
        $this->view->signupForm = $this->getSignupForm();
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
        $signupForm->setAction('/member/create')
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
            $redirect = $this->_request->getParam("redirect");
        } else {
            $redirect = "/index";
        }

        $loginForm = new Zend_Form();
        $loginForm->setAction($this->_request->getBaseUrl() . '/member/login/redirect/' . $redirect)
            ->setMethod('post')
            ->addElement($username)
            ->addElement($password)
            ->addElement($submit);

        return $loginForm;
    }
}