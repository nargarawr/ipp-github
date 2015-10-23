<?

class IndexController extends BaseController {

    public function init() {
        $this->view->isExternal = true;
        parent::init();
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