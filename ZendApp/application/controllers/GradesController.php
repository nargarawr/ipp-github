<?php

class GradesController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
        $settings = AccountFactory::getSettings($this->user->userId, 6);

        if (is_null($settings)) {
            $this->_helper->redirector->gotoSimple(
                'gradesftu', 'account', null, array()
            );
        }
        $this->view->settings = $settings;

        $modulesByYear = GradesFactory::getModuleDetails(
            $this->user->userId,
            $settings->course_length
        );
        $this->view->years = $modulesByYear;

    }

    public function addnewmoduleAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $code = $this->getRequest()->getParam("code");
        $name = $this->getRequest()->getParam("name");
        $year = $this->getRequest()->getParam("year");
        $credits = $this->getRequest()->getParam("credits");
        $semester = $this->getRequest()->getParam("semester");

        if (!is_null($this->user)) {
            GradesFactory::addNewModule(
                $this->user->userId,
                $code,
                $name,
                $year,
                $credits,
                $semester
            );
        }
    }

    public function setmoduleactiveAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $moduleId = $this->getRequest()->getParam("id");
        $isActive = $this->getRequest()->getParam("isActive");

        if (!is_null($this->user)) {
            GradesFactory::setModuleActive(
                $moduleId,
                $isActive,
                $this->user->userId
            );
        }
    }

    public function updatemoduleAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $moduleId = $this->getRequest()->getParam("moduleId");
        $code = $this->getRequest()->getParam("code");
        $name = $this->getRequest()->getParam("name");
        $year = $this->getRequest()->getParam("year");
        $credits = $this->getRequest()->getParam("credits");
        $semester = $this->getRequest()->getParam("semester");

        if (!is_null($this->user)) {
            GradesFactory::updateModuleDetails(
                $moduleId,
                $code,
                $credits,
                $name,
                $year,
                $semester,
                $this->user->userId
            );
        }
    }

    public function updateyearweightsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getParam('params');
        if (!is_null($this->user)) {
            GradesFactory::updateYearWeight(
                $params,
                $this->user->userId
            );
        }
    }

    public function getmodulegradesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!is_null($this->user)) {
            $results = GradesFactory::getUserModuleGrades(
                $this->user->userId,
                true
            );
            echo Zend_Json::encode($results);
        }
        exit;
    }

    public function setassessmentactiveAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $assessmentId = $this->getRequest()->getParam("id");
        $isActive = $this->getRequest()->getParam("isActive");

        if (!is_null($this->user)) {
            GradesFactory::setAssessmentActive(
                $assessmentId,
                $isActive,
                $this->user->userId
            );
        }
    }

    public function editassessmentAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $assessmentId = $this->getRequest()->getParam('id');
        $name = $this->getRequest()->getParam('name');
        $weight = $this->getRequest()->getParam('weight');
        $grade = $this->getRequest()->getParam('grade');

        if (!is_null($this->user)) {
            GradesFactory::editAssessment(
                $assessmentId,
                $name,
                $weight,
                $grade,
                $this->user->userId
            );
        }

        exit;
    }

    public function addassessmentAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam("name", null);
        $weight = $this->getRequest()->getParam("weight", 0);
        $moduleId = $this->getRequest()->getParam("moduleId");
        $mark = $this->getRequest()->getParam("mark", 0);

        if (!is_null($this->user)) {
            GradesFactory::addNewAssessment(
                $moduleId,
                $name,
                $weight,
                $mark,
                $this->user->userId
            );
        }
    }

}