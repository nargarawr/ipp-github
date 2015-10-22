<?php

class BaseController extends Zend_Controller_Action {

    protected $api;

    public function init() {
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('getevents', array('json', 'html'))->initContext();
    }

    public function preDispatch() {
        $_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $controller = str_replace($this->_delimiters, '-', $this->getRequest()->getControllerName());
        $action = $this->getRequest()->getActionName();

        // If it's not external, we should check user object
        if ($this->view->isExternal == false) {
            if (Zend_Auth::getInstance()->hasIdentity()) {
                // If user is logged in, check if they can access the app
                $this->view->user = $this->user = new User(Zend_Auth::getInstance()->getIdentity());
                $canAccessApp = AccountFactory::canAccessApp($controller, $action, $this->user);

                if (!$canAccessApp) {
                    $_redirector->gotoUrl('engage/index');
                }

                // If we can access the app, set the title and any sirens
                $this->view->title = AccountFactory::getAppName($controller);

                $sirens = SirenFactory::getSirenMessage($controller);
                $this->view->siren = (count($sirens) > 0) ? $sirens : array();

                logger(
                    'Accessed ' . $controller . '/' . $action
                    . ($this->getRequest()->isXmlHttpRequest() ? ' (ajax)' : ''),
                    $controller == 'error' ? 'HIGH' : 'LOW',
                    $this->user->userId
                );

                $this->api = new ApiFactory($this->user->userId);
            } else {
                // If the user is not logged in, redirect to the login page
                $this->_helper->redirector('index', 'login', null, array(
                    'redirect' => $controller . "-" . $action
                ));
            }
        } else {
            logger(
                'Accessed ' . $controller . '/' . $action
                . ($this->getRequest()->isXmlHttpRequest() ? ' (ajax)' : ''),
                $controller == 'error' ? 'HIGH' : 'LOW',
                0
            );
        }
    }
}