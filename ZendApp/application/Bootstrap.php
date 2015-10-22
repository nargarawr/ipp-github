<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initIndex() {
        ini_set('xdebug.var_display_max_depth', 5);
        ini_set('xdebug.var_display_max_children', 256);
        ini_set('xdebug.var_display_max_data', 1024);

        $layout = Zend_Layout::startMvc();
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');

        // By default all pages have top and nav bar
        $this->view->hasTopBar = true;
        $this->view->hasNavBar = true;
        $this->view->isExternal = false;

        $scriptsDir = APPLICATION_PATH . '/views/scripts/';
        $view->addScriptPath(array($scriptsDir));
        $layout->getView()->addScriptPath(array($scriptsDir));

        // Include global php file
        require_once('../public/global.php');

        // Include base controller
        require_once('../application/controllers/BaseController.php');

        $config = new Zend_Config_Ini(
            '../application/configs/db_config.ini',
            (isDevelopment()) ? 'offline' : 'online'
        );
        $registry = Zend_Registry::getInstance();
        $registry->set('db_config', $config);
        $db_config = Zend_Registry::get('db_config');
        $db = Zend_Db::factory($db_config->db);
        Zend_Db_Table::setDefaultAdapter($db);
        unset($dbAdapter, $registry, $configuration);
    }
}
