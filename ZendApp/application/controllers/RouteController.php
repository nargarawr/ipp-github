<?

/**
 * Class RouteController
 *
 * Details with the entire route flow, from creation to searching and displaying
 *
 * @author Craig Knott
 */
class RouteController extends BaseController {

    /**
     * Initialises the class
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    /**
     * The landing page for the website. Allows the user to search for a given route, as well as explaining what the
     * website is about
     *
     * @author Craig Knott
     */
    public function indexAction() {
    }

    /**
     * Lists the results of a given user search
     *
     * @author Craig Knott
     */
    public function listAction() {
    }

    /**
     * Lists the details of a specific route
     *
     * @author Craig Knott
     */
    public function detailAction() {
    }

    /**
     * Map based page that allows a user to create a route. If passed the /id/x url parameter, will draw the route
     * with id x, and allow user to edit it
     *
     * @author Craig Knott
     */
    public function createAction() {
        // User must be logged in to create a route
        if (!(Zend_Auth::getInstance()->hasIdentity())) {
            // If the user is not logged in, redirect to the login page
            $this->_helper->redirector('login', 'member', null, array(
                'redirect'     => "route-create",
                'fromRedirect' => 1
            ));
        } else if ($this->user->isConfirmed == false) {
            $this->_redirect("/user/details/nce/1");

        }

        $this->view->routeId = $this->getRequest()->getParam('id', null);

        if (!is_null($this->view->routeId)) {
            $this->view->route = RouteFactory::getRoute($this->view->routeId, $this->user->userId);
            $this->view->latlng = RouteFactory::getFirstRoutePoint($this->view->routeId);
            $this->view->routeExists = ($this->view->route !== false);
        }
    }


    /**
     * Adds a new route to the database and returns a Json object with the id of that route
     *
     * @author Craig Knott
     */
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

    /**
     * Updates a specified route, and returns a Json object with the id of that route
     *
     * @author Craig Knott
     */
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

    /**
     * Gets all points for a specific route, and returns them as a Json object
     *
     * @author Craig Knott
     */
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

    /**
     * Takes a file from the user, uploads this to a temporary directory and then returns a Json object with the
     * contents of the file
     *
     * @author Craig Knott
     */
    public function uploadAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $fileContent = file_get_contents($_FILES["file"]["tmp_name"]);
        echo str_replace("\n", "", $fileContent);
        exit;
    }

}
