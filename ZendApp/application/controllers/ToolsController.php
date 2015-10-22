<?

class ToolsController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $this->view->panelName = 'Admin Tools Dashboard';
    }

    public function createuserAction() {
        $this->view->action = 'createuser';
        $this->view->panelName = 'Create New Account';
    }

    public function suppressionsAction() {
        $this->view->action = 'suppressions';
        $this->view->panelName = 'Manage Suppressions';
        $this->view->lsfu = $this->getRequest()->getParam('lsfu', null);
    }

    public function searchuserAction() {
        $this->view->action = 'searchuser';
        $this->view->panelName = 'Search Users';
    }

    public function sirenAction() {
        $this->view->action = 'siren';
        $this->view->panelName = 'Manage Siren Messages';
        $this->view->activeSirens = SirenFactory::getAllActiveSirenMessages();
        $this->view->pastSirens = SirenFactory::getAllPastSirenMessages();
        $this->view->appNames = AccountFactory::getAllAppNames();
    }

    public function createaccountAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $username = $this->getRequest()->getParam("username");
        $email = $this->getRequest()->getParam("email");
        $fname = $this->getRequest()->getParam("fname");
        $lname = $this->getRequest()->getParam("lname");

        AccountFactory::createAccount($username, $email, $fname, $lname);
    }

    public function updateusersuppressionsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $userId = $this->getRequest()->getParam("userid");
        $suppressions = $this->getRequest()->getParam("suppressions");
        AccountFactory::updateUserSuppressions($userId, $suppressions);
    }

    public function findaccountAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $userId = $this->getRequest()->getParam("userid");
        $username = $this->getRequest()->getParam("username");
        $fname = $this->getRequest()->getParam("fname");
        $lname = $this->getRequest()->getParam("lname");
        $email = $this->getRequest()->getParam("email");

        if ($this->user->userType == 0) {
            $res = AccountFactory::findUserAccount(
                is_null($userId) ? null : $userId,
                is_null($username) ? null : $username,
                is_null($email) ? null : $email,
                is_null($fname) ? null : $fname,
                is_null($lname) ? null : $lname
            );
            echo Zend_Json::encode($res);
            exit;
        }
    }

    public function changesirenstateAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->user->userType == 0) {
            $sirenId = $this->getRequest()->getParam("sirenId");
            $is_active = $this->getRequest()->getParam("is_active");

            SirenFactory::changeSirenState($sirenId, $is_active, $this->user->userId);
        }
    }

    public function addsirenAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->user->userType == 0) {
            $message = $this->getRequest()->getParam("message");
            $app = $this->getRequest()->getParam("app");

            SirenFactory::addSiren($message, $app, $this->user->userId);
        }
    }

}

