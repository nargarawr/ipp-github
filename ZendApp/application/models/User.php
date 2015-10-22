<?

class User {

    public $username;
    public $userId;
    public $userType;
    public $joinDate;
    public $email;
    public $firstName;
    public $lastName;
    public $name;

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
        $this->userType = $userIdentity->user_type;
        $this->joinDate = $userIdentity->datetime_created;
        $this->email = $userIdentity->email;
        $this->firstName = $userIdentity->fname;
        $this->lastName = $userIdentity->lname;
        $this->name = (is_null($this->firstName) || $this->firstName === '') ? $this->username : $this->firstName;
    }
}