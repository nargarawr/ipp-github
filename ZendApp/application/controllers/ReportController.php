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

    /**
     * Returns a JSON array of all non-resolved reports in the system
     *
     * @author Craig Knott
     */
    public function getAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $sortBy = $this->getRequest()->getParam('sortBy', 'datetime');
        $direction = $this->getRequest()->getParam('direction', 'ASC');

        $results = ReportFactory::getAll($sortBy, $direction);

        echo Zend_Json::encode($results);
        exit;
    }

    /**
     * Marks the given report as resolve, with the resolution reason
     *
     * @author Craig Knott
     */
    public function resolveAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $reportId = $this->getRequest()->getParam('id', null);
        $resolution = $this->getRequest()->getParam('resolution', null);
        $type = $this->getRequest()->getParam('type', null);
        $reportedItemId = $this->getRequest()->getParam('reportedItemId', null);

        ReportFactory::resolveReport($reportId, $resolution, $this->user->userId, $type, $reportedItemId);

        // If we deleted something, we to actually delete it now
        if ($resolution == 'deleted') {
            if ($type == 'comment') {
                CommentFactory::deleteComment($reportedItemId);
            } else if ($type == 'route') {
                RouteFactory::deleteRoute($reportedItemId, 0);
            }
        }

        exit;
    }
}
