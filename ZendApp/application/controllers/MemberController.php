<?

class MemberController extends BaseController {

    public function init() {
        parent::init();
    }

    public function debug() {
        $trace = debug_backtrace();
        $rootPath = dirname(dirname(__FILE__));
        $file = str_replace($rootPath, '', $trace[0]['file']);
        $line = $trace[0]['line'];
        $var = $trace[0]['args'][0];
        $lineInfo = sprintf('<div><strong>%s</strong> (line <strong>%s</strong>)</div>', $file, $line);
        $debugInfo = sprintf('<pre>%s</pre>', print_r($var, true));
        print_r($lineInfo.$debugInfo);
    }

    public function loginAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            die(var_dump('already online'));
            // TODO: redirect if logged in
            //$this->_redirect('engage');
        }

        /*
            
            if (!is_null($request->getParam("redirect"))) {
                $redirect = str_replace("-", "/", $request->getParam("redirect"));
            }
            */
            
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
                
                // This line breaks everything
                try {
                    $result = $auth->authenticate($authAdapter);
                } catch (Exception $e) {
                    $this->debug($e);
                }
die();

                if ($result->isValid()) {
                    $userInfo = $authAdapter->getResultRowObject(null, 'password');
                    $authStorage = $auth->getStorage();
                    $authStorage->write($userInfo);

/* where do we go? */
                    if (!is_null($redirect)) {
                        $this->_redirect('/' . $redirect);
                    } else {
                        $this->_redirect('/engage/index');
                    }
                } else {
                    $this->view->errorMessage = 'Could not login: Username or password was wrong';
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

/*
        if (!is_null($this->_request->getParam("redirect"))) {
            $redirect = $this->_request->getParam("redirect");
        } else {
            $redirect = "route/index";
        }
*/

        $loginForm = new Zend_Form();
        $loginForm->setAction($this->_request->getBaseUrl() . '/member/login/')
            ->setMethod('post')
            ->addElement($username)
            ->addElement($password)
            ->addElement($submit);

        return $loginForm;
    }
}