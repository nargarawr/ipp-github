<?

/**
 * Class ReportController
 *
 * Controller that handles user report requests
 *
 * @author Craig Knott
 */
class ReportController extends BaseController {

    /**
     * Initialisation function that allowed for JSON to be displayed on the page, and sets up the flash messenger
     *
     * @author Craig Knott
     */
    public function init() {
        parent::init();
        $this->view->isExternal = true;
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Used to add a new route to a route
     *
     * @author Craig Knott
     */
    public function addAction() {
        $id = $this->getRequest()->getParam('id', null);
        $type = $this->getRequest()->getParam('type', null);
        $reason = $this->getRequest()->getParam('reason', null);



        if (!is_null($this->user)) {
            $id = ReportFactory::addReport($this->user->userId, $type, $id, $reason);
            echo Zend_Json::encode($id);
        }

        exit;
    }

}
