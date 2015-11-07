<?

/**
 * Class CommentController
 *
 * Class in charge of sending, retrieving and modifying comments
 *
 * @author Craig Knott
 */
class CommentController extends BaseController {

    /**
     * Initialises the controller. No pages of this controller are ever rendered, so we turn this off here
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
     * Used to add a new comment to a route
     *
     * @author Craig Knott
     */
    public function addAction() {
        $routeId = $this->getRequest()->getParam('id', null);
        $text = $this->getRequest()->getParam('text', null);

        $id = CommentFactory::addComment($routeId, $text, $this->user->userId);

        echo Zend_Json::encode($id);
        exit;
    }

    /**
     * Used to update a comment on a route
     *
     * @author Craig Knott
     */
    public function updateAction() {
        $commentId = $this->getRequest()->getParam('id', null);
        $newText = $this->getRequest()->getParam('newText', null);

        CommentFactory::updateComment($commentId, $newText);

        exit;
    }

    /**
     * Used to remove a comment from a route
     *
     * @author Craig Knott
     */
    public function deleteAction() {
        $commentId = $this->getRequest()->getParam('id', null);

        CommentFactory::deleteComment($commentId);

        exit;
    }

}