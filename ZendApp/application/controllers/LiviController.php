<?

class LiviController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $events = LiviFactory::getAllEvents();

        $eventsByMonth = array();
        foreach ($events as $event) {
            $dateParts = explode('-', $event->day);
            if (array_key_exists($dateParts[1], $eventsByMonth)) {
                array_push($eventsByMonth[$dateParts[1]], $event);
            } else {
                $eventsByMonth[$dateParts[1]] = array();
            }
        }

        $this->view->months = array(
            (object)array(
                'name'      => 'February',
                'startDay'  => 6,
                'numOfDays' => 28,
                'idPrefix'  => '2015-02',
                'events'    => $eventsByMonth['02']
            ),
            (object)array(
                'name'      => 'March',
                'startDay'  => 6,
                'numOfDays' => 31,
                'idPrefix'  => '2015-03',
                'events'    => $eventsByMonth['03']
            ),
            (object)array(
                'name'      => 'April',
                'startDay'  => 2,
                'numOfDays' => 30,
                'idPrefix'  => '2015-04',
                'events'    => $eventsByMonth['04'],
                'selected'  => true
            )
        );
    }

    public function deleteAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->getRequest()->getParam('id', null);
        if (!is_null($id)) {
            LiviFactory::removeEvent($id, $this->user->userId);
        }
        $this->_helper->redirector->gotoSimple('index', 'livi', null, array());
    }

    public function addeventAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $day = $this->getRequest()->getParam('day', null);
        $event = $this->getRequest()->getParam('event', null);
        if (!is_null($event) && !is_null($day)) {
            LiviFactory::addEvent($day, $event, $this->user->userId);
        }

        $this->_helper->redirector->gotoSimple('index', 'livi', null, array());
    }

}
