<?

class AccountController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $this->view->action = 'index';
        $this->view->appName = 'Account Information';
        $this->view->appsWithSettings = EngageFactory::getAllAppsWithSettings($this->user->userId);
    }

    public function purchaseAction() {
        $this->view->action = 'purchase';
        $this->view->appName = 'Finance Manager';
        $this->view->settings = AccountFactory::getSettings($this->user->userId, 1);
        $this->view->appsWithSettings = EngageFactory::getAllAppsWithSettings($this->user->userId);

        $this->view->customCategories = PurchaseFactory::getCustomPurchaseCategories($this->user->userId);
        $this->view->defaultCategories = PurchaseFactory::getDefaultPurchaseCategories();
    }

    public function revisionAction() {
        $this->view->action = 'revision';
        $this->view->appName = 'Revision Manager';
        $showMsg = $this->getRequest()->getParam('showmsg', null);
        if (is_null($showMsg)) {
            $periods = RevisionFactory::getRevisionPeriods($this->user->userId);
            if (count($periods) == 0) {
                $showMsg = 'ftu';
            }
        }
        $this->view->showMsg = $showMsg;
        $this->view->appsWithSettings = EngageFactory::getAllAppsWithSettings($this->user->userId);
    }

    public function gradesAction() {
        $this->view->action = 'grades';
        $this->view->appName = 'Grades Tracker';

        $this->view->settings = AccountFactory::getSettings($this->user->userId, 6);
        if (is_null($this->view->settings)) {
            $this->_helper->redirector->gotoSimple(
                'gradesftu', 'account', null, array()
            );
        }

        $this->view->appsWithSettings = EngageFactory::getAllAppsWithSettings($this->user->userId);
        $this->view->yearsAndWeights = GradesFactory::getYearWeights($this->user->userId);
    }

    public function gradesftuAction() {
        $this->view->action = 'grades';
        $this->view->appName = 'Grades Tracker';
        $this->view->appsWithSettings = EngageFactory::getAllAppsWithSettings($this->user->userId);
    }

    public function updategradessettingsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            AccountFactory::updateSettings(
                $this->user->userId,
                array(
                    'course_length' => (object)array(
                        'value' => $this->_request->getParam('length'),
                        'app'   => 6
                    ),
                    'default_tab'   => (object)array(
                        'value' => $this->_request->getParam('defaultTab'),
                        'app'   => 6
                    )
                )
            );
        }
    }

    public function updateaccountsettingsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            AccountFactory::updateAccountDetails(
                $this->user->userId,
                $this->_request->getParam('firstName'),
                $this->_request->getParam('lastName'),
                $this->_request->getParam('email')
            );

            Zend_Auth::getInstance()->getIdentity()->fname = $this->_request->getParam('firstName');
            Zend_Auth::getInstance()->getIdentity()->lname = $this->_request->getParam('lastName');
            Zend_Auth::getInstance()->getIdentity()->email = $this->_request->getParam('email');
        }
    }

    public function updatepurchasesettingsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            AccountFactory::updateSettings(
                $this->user->userId,
                array(
                    'salary'   => (object)array(
                        'value' => $this->_request->getParam('salary'),
                        'app'   => 1
                    ),
                    'post_tax' => (object)array(
                        'value' => $this->_request->getParam('postTax'),
                        'app'   => 1
                    ),
                    'bills'    => (object)array(
                        'value' => $this->_request->getParam('bills'),
                        'app'   => 1
                    ),
                    'year_length' => (object) array(
                        'value' => $this->_request->getParam('yearLength'),
                        'app' => 1
                    )
                )
            );
        }
    }

    public function updaterevisionstartdateAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            AccountFactory::updateSettings(
                $this->user->userId,
                array(
                    'start_date' => (object)array(
                        'value' => $this->_request->getParam('startDate'),
                        'app'   => 4
                    )
                )
            );

        }
    }

    public function updatepasswordAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {

            $username = $this->user->username;
            $password = $this->_request->getParam('currentPass');
            $newPass1 = $this->_request->getParam('newPass1');
            $newPass2 = $this->_request->getParam('newPass2');

            if ($password === "" || $newPass1 === "" || $newPass2 === "") {
                echo Zend_Json::encode(array('error' => 'blank_fields'));
                exit;
            }

            if ($newPass1 !== $newPass2) {
                echo Zend_Json::encode(array('error' => 'password_mismatch'));
                exit;
            }

            $oldPassValid = AccountFactory::checkPassword($this->user->userId, $password);
            if (count($oldPassValid) == 0) {
                echo Zend_Json::encode(array('error' => 'wrong_password'));
                exit;
            }

            // If all is good, update password
            AccountFactory::updatePassword($this->user->userId, $newPass1);
        }
    }

    public function checkuniqueusernameAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $username = $this->_request->getParam('username');
        $res = AccountFactory::checkUniqueUsername($username);
        echo Zend_Json::encode($res);
        exit;
    }

    public function getsettingsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $appId = $this->_request->getParam('appid');
        $results = AccountFactory::getSettings($this->user->userId, $appId);
        $settings = new Settings($results);
        $this->view->settings = $settings;
    }

    public function getuserappswithsuppressionsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $userId = $this->_request->getParam('userid');
        $res = EngageFactory::getAllEngagementAppsIgnoringSuppressions($userId);
        echo Zend_Json::encode($res);
        exit;
    }

    public function getuserdetailsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if ($this->user->userType == 0) {
            $userId = $this->_request->getParam('userid');
            $res = AccountFactory::getUserDetails($userId);
            echo Zend_Json::encode($res);
            exit;
        }
    }

    public function setcustomcategoryactiveAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $categoryId = $this->_request->getParam('categoryId');
        $active = $this->_request->getParam('active');
        PurchaseFactory::updateActiveOfCustomCategory($categoryId, $active, $this->user->userId);
    }

    public function updatecustomcategorynameAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $categoryId = $this->_request->getParam('categoryId');
        $name = $this->_request->getParam('name');
        PurchaseFactory::updateCustomCategoryName($categoryId, $name, $this->user->userId);
    }

    public function setupgradesappAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        // Set course length and default tab
        AccountFactory::updateSettings(
            $this->user->userId,
            array(
                'course_length' => (object)array(
                    'value' => $this->_request->getParam('courseLength'),
                    'app'   => 6
                ),
                'default_tab'   => (object)array(
                    'value' => $this->_request->getParam('defaultTab'),
                    'app'   => 6
                )
            )
        );

        // Assign year weights
        $yearWeights = $this->_request->getParam('yearWeights');
        GradesFactory::addYearWeights($this->user->userId, $yearWeights);
    }

    public function searchuserswithoneparamAction(){
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $searchString = $this->getRequest()->getParam('searchString');
        $results = AccountFactory::findUserAccountWithoutSearchLabels($searchString);

        echo Zend_Json::encode($results);
    }

}