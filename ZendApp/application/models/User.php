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
    public $age;
    public $bio;
    public $loginCount;
    public $lastLogin;
    public $isAdmin;
    public $isBanned;
    public $isShadowBanned;
    public $isConfirmed;
    public $datetimeCreated;
    public $datetimeUpdated;
    public $preferences;
    public $shouldDeauth;

    /**
     * Constructs user class to represent the user. Accessed with $this->user
     *
     * @author Craig Knott
     *
     * @param int $userId The id of the user to construct
     */
    public function __construct($userId) {
        $dbUser = UserFactory::getUser($userId, false);

        $this->userId = $dbUser->id;
        $this->username = $dbUser->username;
        $this->fname = $dbUser->fname;
        $this->lname = $dbUser->lname;
        $this->email = $dbUser->email;
        $this->age = $dbUser->age;
        $this->location = $dbUser->location;
        $this->bio = $dbUser->bio;
        $this->loginCount = $dbUser->login_count;
        $this->lastLogin = $dbUser->last_login;
        $this->isAdmin = $dbUser->is_admin;
        $this->isBanned = $dbUser->is_banned;
        $this->isShadowBanned = $dbUser->is_shadow_banned;
        $this->isConfirmed = $dbUser->is_confirmed;
        $this->datetimeCreated = $dbUser->datetime_created;
        $this->datetimeUpdated = $dbUser->datetime_updated;
        $this->preferences = (object)array(
            'emailOnRouteComment' => $dbUser->email_on_route_comment,
            'emailOnRouteFork'    => $dbUser->email_on_route_fork,
            'emailOnRouteRating'  => $dbUser->email_on_route_rating,
            'emailOnAnnouncement' => $dbUser->email_on_announcement
        );
        $this->shouldDeauth = $dbUser->should_deauth;
    }
}