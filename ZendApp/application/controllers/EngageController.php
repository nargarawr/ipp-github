<?

class EngageController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $this->view->apps = EngageFactory::getAllEngagementApps($this->user->userId);
    }

}
