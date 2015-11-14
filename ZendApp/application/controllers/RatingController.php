<?

/**
 * Class RatingController
 *
 * Class in charge of sending, retrieving and modifying ratings (numbers between 1 and 5, multiples of 0.5)
 *
 * @author Craig Knott
 */
class RatingController extends BaseController {

    /**
     * Initialises the controller. No pages of this controller are ever rendered, so we turn this off here
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Used to add a new rating to a route
     *
     * @author Craig Knott
     */
    public function addAction() {
        $routeId = $this->getRequest()->getParam('id', null);
        $rating = $this->getRequest()->getParam('rating', null);

        $id = RatingFactory::addRating($routeId, $rating, $this->user->userId);
        RouteFactory::updateRouteLog($routeId, $this->user->userId, 'rate', $id);

        $routeOwner = UserFactory::getRouteOwnerDetails($routeId);
        if (!is_null($routeOwner->email) && $routeOwner->emailOnRouteRating) {
            EmailFactory::sendEmail(
                $routeOwner->email,
                $this->user->username . ' has left a rating on your route!',
                $this->view->action(
                    'newsocialinteraction',
                    'email',
                    null,
                    array(
                        'type'       => 'rate',
                        'routeId'    => $routeId,
                        'rating'     => $rating,
                        'routeOwner' => $routeOwner->username
                    )
                )
            );
        }

        echo Zend_Json::encode(array(
            'ratingId' => $id,
            'username' => $this->user->username
        ));
        exit;
    }

    /**
     * Used to "clear" a rating from a route (as if the user never rated it)
     *
     * @author Craig Knott
     */
    public function removeAction() {
        $ratingId = $this->getRequest()->getParam('id', null);

        RatingFactory::removeRating($ratingId);

        exit;
    }

    /**
     * Used to get the average rating for a route. Used through Ajax to update when a new rating is made
     *
     * @author Craig Knott
     */
    public function getrouteaverageAction() {
        $routeId = $this->getRequest()->getParam('id', null);

        $rating = RatingFactory::getAverageRatingForRoute($routeId);

        echo Zend_Json::encode($rating);
        exit;
    }

}