<?

require('Pusher/lib/Pusher.php');

class LabsController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $apiResponse = $this->api->call('get/users');
        if ($apiResponse->Status != 0) {
            var_dump("There was an error with your request: " . $apiResponse->Message);
        } else {
            var_dump($apiResponse->Data);
        }
    }

    public function chatAction() {
    }

    public function sqlAction() {
        $method = $this->getRequest()->getParam('method');
        $sql = $this->getRequest()->getParam('sqlInput');
        $this->view->sql = $sql;

        if (!is_null($sql) && trim($sql) != '') {
            $results = LabsFactory::sql_runSql($sql, $method);
            $this->view->results = $results;
        }
    }

    public function sendmessageAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $message = $this->getRequest()->getParam('message');
        $timestamp = $this->getRequest()->getParam('timestamp');

        $app_key = 'f43c16c9bdcce3f6e731';
        $app_secret = '34457154039b5f338f9b';
        $app_id = '117781';
        $pusher = new Pusher($app_key, $app_secret, $app_id);
        $pusher->trigger(
            'chat_channel',
            'send_message',
            array(
                'sender'    => $this->user->name,
                'message'   => $message,
                'timestamp' => $timestamp
            )
        );

        exit;
    }

}