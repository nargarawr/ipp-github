<?

class LoginController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasTopBar = false;
        $this->view->hasNavBar = false;
        $this->view->isExternal = true;
    }

    public function indexAction() {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_redirect('engage');
        }

        $request = $this->getRequest();
        if (!is_null($request->getParam("redirect"))) {
            $redirect = str_replace("-", "/", $request->getParam("redirect"));
        }

        $loginForm = $this->getLoginForm();

        if ($request->isPost()) {
            if ($loginForm->isValid($request->getPost())) {

                $authAdapter = AccountFactory::getAuthAdapter();
                $username = $loginForm->getValue('username');
                $password = $loginForm->getValue('password');

                $authAdapter->setIdentity($username)
                    ->setCredential($password);

                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);

                if ($result->isValid()) {
                    $userInfo = $authAdapter->getResultRowObject(null, 'password');
                    $userInfo->suppressions = AccountFactory::getSuppressedApps($userInfo->pk_user_id);
                    $authStorage = $auth->getStorage();
                    $authStorage->write($userInfo);

                    logger('Logged In' , 'LOW', $userInfo->pk_user_id);
                    AccountFactory::updateLoginCount($userInfo->pk_user_id);
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
        $this->view->loginForm = $loginForm;
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('login/index');
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
            $redirect = "engage/index";
        }

        $loginForm = new Zend_Form();
        $loginForm->setAction($this->_request->getBaseUrl() . '/login/index/redirect/' . $redirect)
            ->setMethod('post')
            ->addElement($username)
            ->addElement($password)
            ->addElement($submit);

        return $loginForm;
    }

}
