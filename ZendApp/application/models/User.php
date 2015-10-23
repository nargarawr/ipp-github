<?
class User {

    public $username;
    public $userId;
    public $fname;
    public $lname;
    public $location;
    public $loginCount;
    public $isAdmin;
    public $isBanned;
    public $isShadowBanned;
    public $datetimeCreated;
    public $datetimeUpdated;

    /**
     * Constructs user class from user identity object from Zend Login
     *
     * @author Craig Knott
     *
     * @param object $userIdentity Object with user details
     *
     */
    public function __construct($userIdentity) {
        $this->username = $userIdentity->username;
        $this->userId = $userIdentity->pk_user_id;
        $this->fname = $userIdentity->fname;
        $this->lname = $userIdentity->lname;
        $this->location = $userIdentity->location;
        $this->loginCount = $userIdentity->login_count;
        $this->isAdmin = $userIdentity->is_admin;
        $this->isBanned = $userIdentity->is_banned;
        $this->isShadowBanned = $userIdentity->is_shadow_banned;
        $this->datetimeCreated = $userIdentity->datetime_created;
        $this->datetimeUpdated = $userIdentity->datetime_updated;
    }
}