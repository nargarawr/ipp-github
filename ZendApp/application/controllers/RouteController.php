<?

class RouteController extends BaseController {

    public function init() {
        $this->view->isExternal = false;
        parent::init();
    }

    public function indexAction() {
    }

}