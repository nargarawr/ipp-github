<?

/**
 * Class LoginFactory
 *
 * In charge of accessing the login server and authenticating users
 *
 * @author Craig Knott
 */
class LoginFactory extends ModelFactory {

    /**
     * Creates a new user on the system
     *
     * @author Craig Knott
     *
     * @param string $username  Username for the user
     * @param string $password  Password of the user
     * @param string $email     Email address of the user
     * @param string $firstName (optional) First name of the user
     * @param string $location  (optional) Location of the user
     *
     * @return void
     */
    public static function createNewUser($username, $password, $email, $firstName = null, $location = null) {
        $sql = "INSERT INTO tb_user (
                    username,
                    fname,
                    lname,
                    email,
                    location,
                    password,
                    login_count,
                    last_login,
                    is_admin,
                    is_banned,
                    is_shadow_banned,
                    datetime_created,
                    datetime_updated
                ) VALUES (
                    :username,
                    :firstName,
                    '',
                    :email,
                    :location,
                    MD5(:password),
                    0,
                    NOW(),
                    0,
                    0,
                    0,
                    NOW(),
                    NOW()
                );";
        $params = array(
            ':username'  => $username,
            ':firstName' => $firstName,
            ':email'     => $email,
            ':location'  => $location,
            ':password'  => $password
        );
        parent::execute($sql, $params);
    }

    /**
     * Run whenever a user logs in to increase their login count and update their last login date
     *
     * @author Craig Knott
     *
     * @param int $userId The Id of the user that logged in
     *
     * @return void
     */
    public static function registerUserLogin($userId) {
        $sql = "UPDATE tb_user
                SET login_count = login_count + 1,
                    last_login = NOW()
                WHERE pk_user_id = :userId";
        $params = array(
            ":userId" => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Checks whether a given user is unique, to prevent duplicates. Check whether their username or email already
     * exists in the system
     *
     * @author Craig Knott
     *
     * @param string $username Username to check
     * @param string $email    Email to check
     *
     * @return bool Whether this user's details are unique or not
     */
    public static function checkUserUnique($username, $email) {
        $sql = "SELECT
                    pk_user_id
                FROM tb_user
                WHERE username = :username
                OR email = :email";
        $params = array(
            ':username' => $username,
            ':email'    => $email
        );
        $result = parent::fetchAll($sql, $params);

        return (count($result) == 0);
    }
}


