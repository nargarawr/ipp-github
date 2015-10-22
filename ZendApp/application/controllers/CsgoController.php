<?

class CsgoController extends BaseController {

    public function init() {
        parent::init();
        $this->view->hasNavBar = false;
        $this->view->isExternal = true;
    }

    public function indexAction() {
    }

    public function smokesAction() {
        CsgoFactory::getSmokes();
    }

    public function getmapstatsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $maps = array();

        $teamSize = $this->getRequest()->getParam('team', null);
        $stats = CsgoFactory::getMapStats($teamSize);

        foreach ($stats as $stat) {
            if (!array_key_exists($stat->map_name, $maps)) {
                $maps[$stat->map_name] = array(
                    'name' => $stat->map_name,
                    'wins' =>  $stat->matches_won,
                    'ties' => $stat->matches_tied,
                    'losses' => $stat->matches_lost,
                    'win_and_tie_percent' => $stat->not_lose_percent,
                    'win_percent' => $stat->win_percent,
                    'stats' => array(
                        'T' => null,
                        'CT' => null
                    )
                );
                $maps[$stat->map_name]['stats'][$stat->side] = $stat;
            } else {
                $maps[$stat->map_name]['stats'][$stat->side] = $stat;
            }
        }

        echo Zend_Json::encode($maps);
        exit;
    }

    public function getmatchhistoryAction(){
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $teamSize = $this->getRequest()->getParam('team', null);
        $matches = CsgoFactory::getMatchHistory($teamSize);
        echo Zend_Json::encode($matches);
        exit;
    }
}

