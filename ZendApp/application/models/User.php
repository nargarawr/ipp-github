<?

/**
 * Class User
 *
 * The user object, which contains all information on the currently logged in user
 *
 * @author Craig Knott
 *
 */
class User {

    public $username;
    public $userId;
    public $fname;
    public $lname;
    public $email;
    public $location;
    public $bio;
    public $loginCount;
    public $lastLogin;
    public $isAdmin;
    public $isBanned;
    public $isShadowBanned;
    public $isConfirmed;
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
    public function __construct(
        $username, $userId, $fname, $lname, $email, $location, $bio, $loginCount, $lastLogin, $isAdmin,
        $isBanned, $isShadowBanned, $isConfirmed, $datetimeCreated, $datetimeUpdated
    ) {
        $this->username = $username;
        $this->userId = $userId;
        $this->fname = $fname;
        $this->lname = $lname;
        $this->email = $email;
        $this->location = $location;
        $this->bio = $bio;
        $this->loginCount = $loginCount;
        $this->lastLogin = $lastLogin;
        $this->isAdmin = $isAdmin;
        $this->isBanned = $isBanned;
        $this->isShadowBanned = $isShadowBanned;
        $this->isConfirmed = $isConfirmed;
        $this->datetimeCreated = $datetimeCreated;
        $this->datetimeUpdated = $datetimeUpdated;
    }
}