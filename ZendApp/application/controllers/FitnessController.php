<?php

class FitnessController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
    }

    public function indexAction() {
    }

    public function workoutAction() {
        $this->view->exercises = array();

        $workoutId = $this->getRequest()->getParam('workoutId', null);
        $createNew = $this->getRequest()->getParam('createNew', null);
        if (!is_null($createNew) && $createNew == 1) {
            $lastInsertedId = FitnessFactory::createNewWorkout($this->user->userId);
        }

        $this->view->workoutId = (is_null($workoutId) ? $lastInsertedId : $workoutId);
    }

    public function exerciseAction() {
        $this->view->workoutId = $this->getRequest()->getParam('workoutId');
    }

    public function setAction() {
        $workoutId = $this->getRequest()->getParam('workoutId');
        $exerciseId = $this->getRequest()->getParam('exercise');

        $this->view->exerciseName = FitnessFactory::getExerciseName($exerciseId);
        $this->view->lastWorkout = FitnessFactory::getLastWorkoutForExercise(
            $exerciseId,
            $this->user->userId,
            $workoutId
        );

        $this->view->workoutId = $workoutId;
        $this->view->exerciseId = $exerciseId;
        $this->view->weightIntervals = array(1, 2, 2.5, 5, 10, 20);
        $this->view->repIntervals = array(1, 5, 10);
    }

    public function getallexercisesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $results = FitnessFactory::getAllExercisesByBodypartAndMuscle();
        echo Zend_Json::encode($results);
        exit;
    }

    public function addsetAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $reps = $this->getRequest()->getParam('reps');
        $weight = $this->getRequest()->getParam('weight');
        $workoutId = $this->getRequest()->getParam('workoutId');
        $exerciseId = $this->getRequest()->getParam('exerciseId');

        FitnessFactory::addSet($reps, $weight, $workoutId, $exerciseId, $this->user->userId);
    }

    public function exerciselistAction() {
        $workoutId = $this->getRequest()->getParam('workoutId', null);
        if (!is_null($workoutId)) {
            $this->view->exercises = FitnessFactory::getWorkoutExercises($workoutId);
        }
        $this->view->workoutId = $workoutId;
    }

    public function previousAction() {
        $this->view->previousWorkouts = FitnessFactory::getPreviousWorkouts($this->user->userId);
    }

    public function addAction () {
        $this->view->muscles = FitnessFactory::getAllMuscles();
    }

}