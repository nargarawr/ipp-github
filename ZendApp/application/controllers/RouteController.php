<?

class RouteController extends BaseController {

    public function init() {
        $this->view->isExternal = true;
        parent::init();
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
        $routeId = $this->getRequest()->getParam('id', null);
    }

}