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

        $this->view->routeId = $this->getRequest()->getParam('id', null);

        if (!is_null($this->view->routeId)) {
            $this->view->route = RouteFactory::getRoute($this->view->routeId, $this->user->userId);
            $this->view->latlng = RouteFactory::getFirstRoutePoint($this->view->routeId);
            $this->view->routeExists = ($this->view->route !== false);
        }
    }

    public function listAction() {
    }

    public function detailAction() {
        //$routeId = $this->getRequest()->getParam('id', null);
    }

    public function newAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam('name', null);
        $desc = $this->getRequest()->getParam('description', null);
        $isPrivate = $this->getRequest()->getParam('privacy', 0);
        $points = $this->getRequest()->getParam('points', null);

        $routeId = RouteFactory::createRoute($name, $desc, $isPrivate, $this->user->userId);
        foreach ($points as $point) {
            RouteFactory::createRoutePoint((object)$point, $routeId);
        }

        echo Zend_Json::encode($routeId);
        exit;
    }

    public function updateAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam('name', null);
        $desc = $this->getRequest()->getParam('description', null);
        $isPrivate = $this->getRequest()->getParam('privacy', 0);
        $points = $this->getRequest()->getParam('points', null);
        $routeId = $this->getRequest()->getParam('routeId', null);

        RouteFactory::updateRoute($routeId, $name, $desc, $isPrivate);

        $highestIdForRoute = (int)RouteFactory::getHighestPointId($routeId);
        foreach ($points as $point) {
            RouteFactory::createRoutePoint((object)$point, $routeId);
        }
        RouteFactory::removeOldPoints($highestIdForRoute, $routeId);

        echo Zend_Json::encode((int)$routeId);
        exit;
    }

    public function getpointsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->getRequest()->getParam('id', null);
        $result = null;
        if (!is_null($id)) {
            $result = RouteFactory::getRoutePoints($id);
        }

        echo Zend_Json::encode($result);
        exit;
    }

}
