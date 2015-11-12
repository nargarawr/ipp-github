<?

/**
 * Class BaseController
 *
 * Base controller that all others inherit from
 *
 * @author Craig Knott
 */
class BaseController extends Zend_Controller_Action {

    /**
     * Initialisation function that allowed for JSON to be displayed on the page, and sets up the flash messenger
     *
     * @author Craig Knott
     */
    public function init() {
        // Allow passing of Ajax content through the application
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getevents', array('json', 'html'))->initContext();

        $this->messageManager = $this->_helper->getHelper('FlashMessenger');
    }

    /**
     * Called before any page is loaded. Makes sure that users are logged in if necessary, and determines whether to
     * display the navigation bar.
     *
     * @author Craig Knott
     */
    public function preDispatch() {
        $controller = str_replace($this->_delimiters, '-', $this->getRequest()->getControllerName());
        $action = $this->getRequest()->getActionName();

        $isLocked = AdminFactory::getSiteAdmin()->is_locked;

        // If the user needs to be logged in to access this page, redirect them to the login page
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // Create the user object
            $userIdentity = Zend_Auth::getInstance()->getIdentity();
            $this->view->user = $this->user = new User($userIdentity->pk_user_id);
        } else if ($this->view->isExternal == false) {
            // If the user session is no longer valid and they are navigating to a page
            $this->_helper->redirector('login', 'member', null, array(
                'redirect'     => $controller . "-" . $action,
                'fromRedirect' => 1
            ));
        }

        $this->view->shouldDisplayNav = ($controller != 'member');
        $this->view->navBar = $this->getNavBar($controller, $action);
    }

    /**
     * Constructs the navigation bar
     *
     * @author Craig Knott
     *
     * @param string $c The name of the current controller
     * @param string $a The name of the current action
     *
     * @return array Array used to draw the navigation
     */
    protected function getNavBar($c, $a) {
        $currentUrl = "/" . $c . "/" . $a;
        $navBar = array(
            'search'  => (object)array(
                'name'          => 'Search Routes',
                'link'          => '/route/index',
                'type'          => 'link',
                'icon'          => '<i class="fa fa-search"></i>',
                'shouldDisplay' => true,
                'isActive'      => false
            ),
            'create'  => (object)array(
                'name'          => 'Create a Route',
                'link'          => '/route/create',
                'type'          => 'link',
                'icon'          => '<i class="fa fa-plus"></i>',
                'shouldDisplay' => true,
                'isActive'      => false
            ),
            'profile' => (object)array(
                'name'          => 'Profile',
                'type'          => 'dropdown',
                'link'          => '/user/details',
                'icon'          => '<i class="fa fa-user"></i>',
                'shouldDisplay' => true,
                'isActive'      => false,
                'children'      => array(
                    (object)array(
                        'name'          => 'My Profile',
                        'link'          => '/user/details',
                        'icon'          => '<i class="fa fa-list-alt"></i>',
                        'shouldDisplay' => true
                    ),
                    (object)array(
                        'name'          => 'Settings',
                        'link'          => '/user/settings',
                        'icon'          => '<i class="fa fa-cog"></i>',
                        'shouldDisplay' => true
                    ),
                    (object)array(
                        'name'          => 'Skins',
                        'link'          => '/user/skins',
                        'icon'          => '<i class="fa fa-paint-brush"></i>',
                        'shouldDisplay' => true
                    ),
                )
            ),
            'admin'   => (object)array(
                'name'          => 'Administration',
                'type'          => 'link',
                'link'          => '/admin/index',
                'icon'          => '<i class="fa fa-cogs"></i>',
                'shouldDisplay' => (!is_null($this->user) && $this->user->isAdmin),
                'isActive'      => false
            )
        );

        $activeSelected = false;
        foreach ($navBar as &$nav) {
            if ($nav->link === $currentUrl ||
                $nav->link === "/route/index" && ($currentUrl == "/route/list" || $currentUrl == "/route/detail")
            ) {
                $nav->isActive = true;
                $activeSelected = true;
                break;
            }
        }

        if (!$activeSelected) {
            $navBar['profile']->isActive = true;
        }

        return $navBar;

    }

    /**
     * Debug function used to make the output of var_dump a little easier to read
     *
     * @author Craig Knott
     *
     * @param mixed $variable The variable to display
     *
     * @return void
     */
    function dump($variable) {
        echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
        var_dump($variable);
        echo "</pre>\n";
    }
}
