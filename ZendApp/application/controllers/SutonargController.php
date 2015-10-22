<?

class SutonargController extends BaseController {

    public function init() {
        parent::init();
        $this->view->links = array(
            (object)array('controller' => 'sutonarg', 'action' => 'episodes', 'name' => 'Episode List'),
            (object)array('controller' => 'sutonarg', 'action' => 'challenges', 'name' => 'Challenge List'),
            (object)array('controller' => 'sutonarg', 'action' => 'players', 'name' => 'Player List'),
            (object)array('controller' => 'sutonarg', 'action' => 'stats', 'name' => 'Stats'),
        );
        $this->view->isExternal = true;
    }

    public function indexAction() {
        $this->_helper->redirector('episodes', 'sutonarg', null, array());
    }

    public function statsAction() {
        $this->view->mostPlayed = SutonargFactory::getMostPlayedChallenges();
        $this->view->winners = SutonargFactory::getWinners();
    }

    public function playersAction() {
        $this->view->players = SutonargFactory::getPlayers();
    }

    public function challengesAction() {
    }

    public function episodesAction() {
        $this->view->seasons = SutonargFactory::getSutonargGameshow();
    }
}
