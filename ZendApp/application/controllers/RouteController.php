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
        $routeId = $this->getRequest()->getParam('id', 0);
        $this->view->route = RouteFactory::getRouteForDetailPage($routeId);
        $this->view->socialStream = RouteFactory::getSocialStream(
            $routeId,
            (is_null($this->user)) ? null : $this->user->userId
        );

        $points = RouteFactory::getRoutePoints($routeId);
        $this->view->points = $points;

        $this->view->gmapUrl = $this->getGmapStaticUrlForRoute($points);

        $this->view->firstPoint = $points[0]->latitude . "," . $points[0]->longitude;
        $this->view->lastPoint = $points[count($points) - 1]->latitude . "," . $points[count($points) - 1]->longitude;

        $this->view->userRouteRating = RouteFactory::getUserRatingForRoute(
            (is_null($this->user)) ? 0 : $this->user->userId,
            $routeId
        );
    }

    /**
     * Given a set of points, returns a Google Maps Static map URL which can be queried to retrieve a map
     * representing those points
     *
     * @author Craig Knott
     *
     * @param array(points) $points The points to be included on the map
     *
     * @return string The URL to query
     */
    protected function getGmapStaticUrlForRoute($points) {
        $baseUrl = "https://maps.googleapis.com/maps/api/staticmap?";
        $size = "size=640x640";
        $type = "maptype=roadmap";
        $markers = "";
        $path = "path=color:0x0000ff80%7Cweight:3%7C";
        $key = "key=AIzaSyBdGDXYIc0fK_SGoImxqOozcXkNwyPqofI";

        $maxLen = 8;
        foreach ($points as $i => $point) {
            $markers .= "markers=color:blue%7Clabel:" . ($i + 1) . "%7C" .
                substr($point->latitude, 0, $maxLen) . "," . substr($point->longitude, 0, $maxLen) . "&";

            $path .= substr($point->latitude, 0, $maxLen) . "," . substr($point->longitude, 0, $maxLen) . "%7C";
        }
        $markers = rtrim($markers, "&");
        $path = rtrim($path, "%7C");

        $params = implode('&', array(
            $size,
            $type,
            $markers,
            $path,
            $key
        ));
        return $baseUrl . $params;
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

    /**
     * Deletes a given route from the system, takes a user Id to ensure no malicious intent
     *
     * @author Craig Knott
     */
    public function deleteAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $routeId = $this->getRequest()->getParam('id', 0);
        RouteFactory::deleteRoute($routeId, $this->user->userId);

        $this->_helper->redirector('details', 'user', null, array());
    }

    /**
     * Gets a route and sends it to the browser as a Json file for downloading
     *
     * @author Craig Knott
     */
    public function downloadAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $routeId = $this->getRequest()->getParam('id', 0);
        $route = RouteFactory::getRoute($routeId, $this->user->userId);
        $route->points = RouteFactory::getRoutePoints($routeId, true);


        RouteFactory::updateRouteLog(
            $routeId,
            is_null($this->user) ? 0 : $this->user->userId,
            "download"
        );


        $fileName = $route->name . ".json";

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo Zend_Json::encode($route);
    }

    /**
     * Creates a copy of a route and adds it to the current user's account
     *
     * @author Craig Knott
     */
    public function forkAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $idToFork = $this->getRequest()->getParam('id', 0);
        $id = RouteFactory::forkRoute($idToFork, $this->user->userId);

        RouteFactory::updateRouteLog($idToFork, $this->user->userId, 'fork');

        $this->_helper->redirector('create', 'route', null, array(
            'id' => $id
        ));
    }

    /**
     * Used to open a route in a different mapping software
     *
     * @author Craig Knott
     */
    public function openingooglemapsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $routeId = $this->getRequest()->getParam('id', 0);

        $points = RouteFactory::getRoutePoints($routeId);

        $url = "https://www.google.com/maps/dir/";
        foreach ($points as $point) {
            $url .= $point->latitude . ',' . $point->longitude . '/';
        }

        $this->_helper->redirector->gotoUrlAndExit($url);
    }
}
