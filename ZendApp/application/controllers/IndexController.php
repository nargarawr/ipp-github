<?

class IndexController extends BaseController {

    public function init() {
        parent::init();
        $this->view->isExternal = true;
    }

    public function indexAction() {
        // Don't render this page, just forward to route/index
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->_helper->redirector->gotoSimple(
            'index', 'route', null, array()
        );
    }
}