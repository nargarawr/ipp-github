<?php

class RevisionController extends BaseController {

    public function init() {
        parent::init();
        $this->view->links = array(
            (object)array('controller' => 'revision', 'action' => 'index', 'name' => 'Home')
        );
    }

    public function indexAction() {
        $periods = RevisionFactory::getRevisionPeriods($this->user->userId);
        if (count($periods) == 0 && !is_null($this->user)) {
            // No periods set up yet, redirect to account settings page
            $this->_redirect('account/revision/showmsg/ftu');
        }

        $pid = $this->getRequest()->getParam('period', null);
        if (!is_null($pid)) {
            $res = RevisionFactory::getPeriodById($pid);

            AccountFactory::updateSettings($this->user->userId, array(
                'last_period' => (object)array(
                    'value' => $pid,
                    'app'   => 4
                )
            ));
        } else {
            $res = RevisionFactory::getCurrentRevisionPeriod($this->user->userId);
            $pid = $res->id;
        }

        $this->view->start_date = $res->start_date;
        $this->view->end_date = $res->end_date;
        $this->view->periods = $periods;
        $this->view->examsCount = RevisionFactory::getUserExamCount(
            $this->user->userId,
            $pid
        );

        $this->view->periodId = $pid;

        $this->view->stats = RevisionFactory::getStatsForPeriod($pid);
    }

    public function addexamAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!is_null($this->user)) {
            $module = $this->getRequest()->getParam('module', null);
            $worth = $this->getRequest()->getParam('worth', null);
            $location = $this->getRequest()->getParam('location', null);
            $date = $this->getRequest()->getParam('date', null);
            $time = $this->getRequest()->getParam('time', null);
            if (preg_match("/pm/", $time)) {
                $parts = explode(":", $time);
                $time = (((int)$parts[0]) + 12) . ":" . substr($time, 2);
            }

            $length = $this->getRequest()->getParam('length', null);
            $seat = $this->getRequest()->getParam('seat', null);
            $period = $this->getRequest()->getParam('period', null);

            RevisionFactory::addExam(
                $this->user->userId,
                $module,
                $worth,
                $location,
                $date,
                $time,
                $length,
                $seat,
                $period
            );
        }
    }

    public function getexamsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $periodId = $this->getRequest()->getParam('period', null);

        if (!is_null($this->user)) {
            $exams = RevisionFactory::getUserExams(
                $this->user->userId,
                $periodId
            );
            echo Zend_Json::encode($exams);
        }
        exit;
    }

    public function editexamAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!is_null($this->user)) {
            $module = $this->getRequest()->getParam('module', null);
            $worth = $this->getRequest()->getParam('worth', null);
            $location = $this->getRequest()->getParam('location', null);
            $date = $this->getRequest()->getParam('date', null);
            $time = $this->getRequest()->getParam('time', null);
            if (preg_match("/pm/", $time)) {
                $parts = explode(":", $time);
                $time = (((int)$parts[0]) + 12) . ":" . substr($time, 2);
            }

            $length = $this->getRequest()->getParam('length', null);
            $seat = $this->getRequest()->getParam('seat', null);
            $examId = $this->getRequest()->getParam('examid', null);

            RevisionFactory::editExam(
                $this->user->userId,
                $module,
                $worth,
                $location,
                $date,
                $time,
                $length,
                $seat,
                $examId
            );
        }
    }

    public function removeexamAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!is_null($this->user)) {
            $examId = $this->getRequest()->getParam('examid', null);
            RevisionFactory::removeExam($this->user->userId, $examId);
        }
        exit;
    }

    public function getrevisionentriesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $periodId = $this->getRequest()->getParam('periodId', 0);

        if (!is_null($this->user) && $periodId != 0) {
            $entries = RevisionFactory::getPeriodRevisionEntries(
                $periodId,
                $this->user->userId
            );
            echo Zend_Json::encode($entries);
        }
        exit;
    }

    public function getexamperiodsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!is_null($this->user)) {
            $periods = RevisionFactory::getRevisionPeriods(
                $this->user->userId
            );
            echo Zend_Json::encode($periods);
        }
        exit;
    }

    public function deleteexamperiodAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->getRequest()->getParam('id', null);

        if (!is_null($this->user)) {
            RevisionFactory::deleteExamPeriod(
                $id,
                $this->user->userId
            );
        }
    }

    public function editexamperiodAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $id = $this->getRequest()->getParam('id', null);
        $name = $this->getRequest()->getParam('name', null);
        $start_date = $this->getRequest()->getParam('start_date', null);
        $end_date = $this->getRequest()->getParam('end_date', null);

        if (!is_null($this->user)) {
            RevisionFactory::editExamPeriod(
                $id,
                $name,
                $start_date,
                $end_date,
                $this->user->userId
            );
        }
    }

    public function addexamperiodAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam('name', null);
        $startDate = $this->getRequest()->getParam('start_date', null);
        $endDate = $this->getRequest()->getParam('end_date', null);

        if (!is_null($this->user)) {
            RevisionFactory::addExamPeriod(
                $this->user->userId,
                $name,
                $startDate,
                $endDate
            );
        }
    }

    public function updateexamentriesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $date = $this->getRequest()->getParam('date');
        $values = $this->getRequest()->getParam('values');

        foreach ($values as $value) {
            RevisionFactory::updateRevisionEntry(
                $date,
                $value['examId'],
                $value['planned'],
                $value['actual']
            );
        }

    }
}