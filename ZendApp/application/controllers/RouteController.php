<?

class RouteController extends BaseController {

    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    public function indexAction() {
    }

    public function createAction() {
        // User must be logged in to create a route
        if (!(Zend_Auth::getInstance()->hasIdentity())) {
            // If the user is not logged in, redirect to the login page
            $this->_helper->redirector('login', 'member', null, array(
                'redirect'     => "route-create",
                'fromRedirect' => 1
            ));
        }
    }

    public function listAction() {

    }

    public function detailAction() {
        //$routeId = $this->getRequest()->getParam('id', null);
    }

    public function saveAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam('name', null);
        $desc = $this->getRequest()->getParam('description', null);
        $isPrivate = $this->getRequest()->getParam('privacy', 0);

        $routeId = RouteFactory::createRoute($name, $desc, $isPrivate, $this->user->userId);

        echo Zend_Json::encode($routeId);
        exit;
    }

}
