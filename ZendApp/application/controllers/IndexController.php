<?

/**
 * Class IndexController
 *
 * Deals with users attempting to access the root pass of Niceway.to
 *
 * @author Craig Knott
 */
class IndexController extends BaseController {

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
     * When the user navigates to www.niceway.to/, they are redirected to the route/index page
     *
     * @author Craig Knott
     */
    public function indexAction() {
        // Don't render this page, just forward to route/index
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_helper->redirector->gotoSimple(
            'index', 'route', null, array()
        );
    }
}