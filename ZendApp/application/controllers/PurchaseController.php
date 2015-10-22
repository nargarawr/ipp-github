<?php

class PurchaseController extends BaseController {

    public function init() {
        parent::init();
        $this->view->links = array(
            (object)array('controller' => 'purchase', 'action' => 'index', 'name' => 'Home'),
            (object)array('controller' => 'purchase', 'action' => 'archive', 'name' => 'Archive'),
            (object)array('controller' => 'purchase', 'action' => 'addnew', 'name' => 'Add New'),
            (object)array('controller' => 'purchase', 'action' => 'loan', 'name' => 'Loans', 'isAdminOnly' => true)
        );
    }

    public function indexAction() {
        $this->view->settings = AccountFactory::getSettings($this->user->userId, 1);
        $this->view->spendAggregates = PurchaseFactory::getSpendingAggregatesByUserId($this->user->userId);
    }

    public function archiveAction() {
        $purchaseCategories = PurchaseFactory::getPurchaseCategories($this->user->userId);
        $costAndDateRange = PurchaseFactory::getCostAndDateRanges($this->user->userId);

        if (!(is_null($costAndDateRange->maxCost))) {
            $this->view->maxCost = $costAndDateRange->maxCost;
            $this->view->minCost = $costAndDateRange->minCost;
            $this->view->maxDate = Utilities::convertDate($costAndDateRange->maxDate, 'en_us', 'en_gb');
            $this->view->minDate = Utilities::convertDate($costAndDateRange->minDate, 'en_us', 'en_gb');
        }

        $defaultMinDate = $this->getRequest()->getParam("startdate");
        if (!is_null($defaultMinDate)) {
            $this->view->defaultMinDate = Utilities::convertDate($defaultMinDate, 'en_us', 'en_gb');
        }

        $defaultMaxDate = $this->getRequest()->getParam("enddate");
        if (!is_null($defaultMaxDate)) {
            $this->view->defaultMaxDate = Utilities::convertDate($defaultMaxDate, 'en_us', 'en_gb');
        }

        $this->view->purchaseCategories = $purchaseCategories;
    }

    public function addnewAction() {
        $purchaseCategories = PurchaseFactory::getPurchaseCategories($this->user->userId);
        $this->view->purchaseCategories = $purchaseCategories;
    }

    public function loanAction() {
        $this->view->loans = PurchaseFactory::getAllLoansForUser($this->user->userId);
        $this->view->loanSteps = PurchaseFactory::getLoanSteps();
    }

    public function addpurchaseAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $items = $this->getRequest()->getPost('itemName', null);
        $cost = $this->getRequest()->getPost('itemCost', 0);
        $category = $this->getRequest()->getPost('inputCat', null);
        $userId = $this->user->userId;

        PurchaseFactory::addPurchase($userId, $items, $cost, $category);
        $this->_helper->redirector->gotoSimple('index', 'purchase', null, array());
    }

    public function updatepurchaseAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        if ($request->isPost()) {
            PurchaseFactory::updatePurchase(
                $this->user->userId,
                $this->_request->getParam('purchaseId'),
                $this->_request->getParam('items'),
                $this->_request->getParam('cost'),
                $this->_request->getParam('date'),
                $this->_request->getParam('category')
            );
        }
    }

    public function removepurchaseAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $purchaseId = $this->getRequest()->getParam("purchaseId");
        $userId = $this->user->userId;
        PurchaseFactory::removePurchase($userId, $purchaseId);
    }

    public function getspendingsperperiodAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $period = $this->getRequest()->getParam('period', 'week');

        if (!is_null($this->user)) {
            $spendingPerWeek = PurchaseFactory::getSpendPerPeriodByUserId($this->user->userId, $period);
            $movingAverage = 0;
            if (!is_null($spendingPerWeek)) {
                foreach ($spendingPerWeek as $key => $spend) {
                    $movingAverage = $movingAverage + $spend->cost;
                    $spendingPerWeek[$key]->movingAverage = $movingAverage / ($key + 1);
                }
                echo Zend_Json::encode($spendingPerWeek);
            }
        }
        exit;
    }

    public function getallpurchasesAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!(is_null($this->user))) {
            $categories = $this->getRequest()->getParam("cats", null);
            if (!is_null($categories)) {
                $categories = explode("&", $categories);
            }

            $startDate = $this->getRequest()->getParam("startdate", null);
            $endDate = $this->getRequest()->getParam("enddate", null);
            $minCost = $this->getRequest()->getParam("mincost", null);
            $maxCost = $this->getRequest()->getParam("maxcost", null);
            $sortBy = $this->getRequest()->getParam("sortby", null);
            $sortByDir = $this->getRequest()->getParam("sortbydir", null);

            $allPurchases = PurchaseFactory::getAllPurchases(
                $this->user->userId,
                $categories,
                $startDate,
                $endDate,
                $minCost,
                $maxCost,
                $sortBy,
                $sortByDir
            );
            echo Zend_Json::encode($allPurchases);
        }
        exit;
    }

    public function getspendbycategoryAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!(is_null($this->user))) {
            $spendings = PurchaseFactory::getSpendByCategory(
                $this->user->userId
            );

            echo Zend_Json::encode($spendings);
        }
        exit;
    }

    public function addcategoryAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $name = $this->getRequest()->getParam('category');

        if (!(is_null($this->user))) {
            PurchaseFactory::addNewCategory($name, $this->user->userId);
        }
    }

    public function newloanAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $tofrom = $this->getRequest()->getParam('tofromInput');
        $amount = $this->getRequest()->getParam('amountInput');
        $recipient = $this->getRequest()->getParam('toInput');

        // Check if recipient exists and has the purchase app
        $otherUser = PurchaseFactory::findPotentialLoaner($recipient);

        if ($otherUser == false) {
            // User does not exist
            die(var_dump('this user does not exist'));
        } else {
            if ($otherUser->can_loan) {
                $loaner = null;
                $loanee = null;

                if ($tofrom == 'owed') {
                    $loaner = $this->user->userId;
                    $loanee = $otherUser->id;
                } else if ($tofrom == 'owe') {
                    $loaner = $otherUser->id;
                    $loanee = $this->user->userId;
                }

                PurchaseFactory::addNewLoan($loaner, $loanee, $amount, $this->user->userId);
            } else {
                // User doesn't have access to the purchase app
                die(var_dump('user does not have app'));
            }
        }
        $this->_helper->redirector->gotoSimple('loan', 'purchase', null, array());
    }

    public function respondtoloanproposalAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $reply = $this->getRequest()->getParam('reply');
        $loanId = $this->getRequest()->getParam('loanId');
        $updatedBy = $this->getRequest()->getParam('updatedBy');

        PurchaseFactory::updateLoanStep(
            $loanId,
            ($reply == 'Accept') ? 2 : 5,
            $updatedBy);

        $this->_helper->redirector->gotoSimple('loan', 'purchase', null, array());
    }

    public function payloanAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $loanId = $this->getRequest()->getParam('loanId');
        $amount = $this->getRequest()->getParam('amountInput');
        $updatedBy = $this->getRequest()->getParam('updatedBy');

        PurchaseFactory::makeLoanPayment($loanId, $amount, $updatedBy, $this->user->userId);
        PurchaseFactory::updateLoanStep($loanId, 3, $updatedBy);

        $this->_helper->redirector->gotoSimple('loan', 'purchase', null, array());
    }

    public function respondtoloanpaymentAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        // If loan payment accepted
        // Find loan_payment in table
        // Set to be is_accepted = 1
        // Check if the loan is paid off in full
        // If paid off, change to state = 4
        // Otherwise, change to state = 2

        // If loan payment rejected
        // Some way of telling the payer it was rejected
    }
}
