<?

/**
 * Class AdminController
 *
 * Class in charge of all administrative actions
 *
 * @author Craig Knott
 */
class AdminController extends BaseController {

    /**
     * Initialises the controller. No pages of this controller are ever rendered, so we turn this off here
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    /**
     * Called before any page is loaded. Makes sure that users are admin
     *
     * @author Craig Knott
     */
    public function preDispatch() {
        parent::preDispatch();
        if ($this->user->isAdmin != 1) {
            $this->_helper->redirector('details', 'user', null, array());
        }
    }

    /**
     * Landing page for admin tools, allows admins to perform all administrative actions
     *
     * @author Craig Knott
     */
    public function indexAction() {
        $this->view->siteAdmin = AdminFactory::getSiteAdmin();
    }

    public function locksiteAction() {
        $shouldLock = $this->getRequest()->getParam('lock', 0);
        AdminFactory::setSiteLocked($shouldLock);
        $this->_helper->redirector('index', 'admin', null, array());
    }

}
