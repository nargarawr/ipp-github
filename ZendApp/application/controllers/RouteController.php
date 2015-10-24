<?

class RouteController extends BaseController {

    public function init() {
        $this->view->isExternal = true;
        parent::init();
    }

    public function indexAction() {
    }

}