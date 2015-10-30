<?php

class BaseController extends Zend_Controller_Action {

    public function init() {
        // Allow passing of Ajax content through the application
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getevents', array('json', 'html'))->initContext();
    }

    public function preDispatch() {
        $isAjax = $this->getRequest()->isXmlHttpRequest();


        $controller = str_replace($this->_delimiters, '-', $this->getRequest()->getControllerName());
        $action = $this->getRequest()->getActionName();

        // If the user needs to be logged in to access this page, redirect them to the login page
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // Create the user object
            $this->view->user = $this->user = new User(Zend_Auth::getInstance()->getIdentity());
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

    protected function getNavBar($c, $a) {
        $currentUrl = "/" . $c . "/" . $a;
        $navBar = array(
            'search'  => (object)array(
                'name'          => 'Search Routes',
                'link'          => '/route/index',
                'type'          => 'link',
                'shouldDisplay' => true,
                'isActive'      => false
            ),
            'create'  => (object)array(
                'name'          => 'Create a Route',
                'link'          => '/route/create',
                'type'          => 'link',
                'shouldDisplay' => true,
                'isActive'      => false
            ),
            'profile' => (object)array(
                'name'          => 'My Profile',
                'type'          => 'dropdown',
                'link'          => '/user/routes',
                'shouldDisplay' => true,
                'isActive'      => false,
                'children'      => array(
                    (object)array(
                        'name'          => 'My Routes',
                        'link'          => '/user/routes',
                        'icon'         => '<i class="fa fa-map-marker"></i>',
                        'shouldDisplay' => true
                    ),
                    (object)array(
                        'name'          => 'My Details',
                        'link'          => '/user/details',
                        'icon'         => '<i class="fa fa-user"></i>',
                        'shouldDisplay' => true
                    ),
                    (object)array(
                        'name'          => 'Administration',
                        'link'          => '/user/admin',
                        'icon'         => '<i class="fa fa-cog"></i>',
                        'shouldDisplay' => (!is_null($this->user) && $this->user->isAdmin)
                    ),
                )
            )
        );

        $activeSelected = false;
        foreach ($navBar as &$nav) {
            if ($nav->link === $currentUrl) {
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
}
