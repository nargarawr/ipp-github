<?php

class BaseController extends Zend_Controller_Action {

    public function init() {
        // Allow passing of Ajax content through the application
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getevents', array('json', 'html'))->initContext();
    }

    public function preDispatch() {
        $_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $controller = str_replace($this->_delimiters, '-', $this->getRequest()->getControllerName());
        $action = $this->getRequest()->getActionName();

        // If the user needs to be logged in to access this page, redirect them to the login page
        if ($this->view->isExternal == false) {
            if (Zend_Auth::getInstance()->hasIdentity()) {
                // Create the user object
                $this->view->user = $this->user = new User(Zend_Auth::getInstance()->getIdentity());
            } else {
                // If the user is not logged in, redirect to the login page
                $this->_helper->redirector('login', 'member', null, array(
                    'redirect' => $controller . "-" . $action
                ));
            }
        }
    }
}?>