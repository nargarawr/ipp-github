<?php

class BaseController extends Zend_Controller_Action {

    public function init() {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getevents', array('json', 'html'))->initContext();
    }

    public function preDispatch() {
        $_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $controller = str_replace($this->_delimiters, '-', $this->getRequest()->getControllerName());
        $action = $this->getRequest()->getActionName();

        // If this page isn't publicly accessible, we should check user object

        /*
        if ($this->view->isExternal == false) {
            if (Zend_Auth::getInstance()->hasIdentity()) {
                $this->view->user = $this->user = new User(Zend_Auth::getInstance()->getIdentity());
            } else {
                // If the user is not logged in, redirect to the login page
                $this->_helper->redirector('login', 'member', null, array(
                    'redirect' => $controller . "-" . $action
                ));
            }
        }
        */
    }
}?>