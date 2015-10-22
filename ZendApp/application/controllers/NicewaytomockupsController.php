<?

class NicewaytomockupsController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
        $this->view->hasTopBar = true;
        $this->view->isExternal = false;
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

        $loginForm = new Zend_Form();
        $loginForm->setAction('nicewaytomockups/login')
            ->setMethod('post')
            ->addElement($username)
            ->addElement($password)
            ->addElement($submit);

        return $loginForm;
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
        $signupForm->setAction('/login')
            ->setMethod('post')
            ->addElement($username)
            ->addElement($email)
            ->addElement($firstName)
            ->addElement($location)
            ->addElement($password)
            ->addElement($submit);

        return $signupForm;
    }

    public function loginAction() {
        $loginForm = $this->getLoginForm();
        $this->view->loginForm = $loginForm;
    }

    public function signupAction(){
        $signupForm = $this->getSignupForm();
        $this->view->signupForm = $signupForm;
    }

    public function myroutesAction(){

    }

    public function mydetailsAction(){

    }

    public function adminAction(){

    }

    public function routeindexAction(){

    }

    public function routelistingAction(){

    }

    public function routedetailAction(){

    }

    public function routecreateAction() {

    }

    public function navAction() {
        
    }

}

