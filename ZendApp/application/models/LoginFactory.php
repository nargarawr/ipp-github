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
     * @return int The id of the newly created user
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
        $id = parent::execute($sql, $params, true);

        // Add row in preferences table
        $sql = "INSERT INTO tb_user_preference (
                    fk_pk_user_id
                ) VALUES (
                    :userId
                )";
        $params = array(
            ':userId' => $id
        );
        parent::execute($sql, $params);

        return $id;
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

    /**
     * Checks whether the provided email address is registered to any account in the system
     *
     * @author Craig Knott
     *
     * @param string $email     The email address to check
     * @param bool   $getUserId Whether or not to return the user Id of the user that owns this email
     *
     * @return bool Whether or not the email exists in the system
     */
    public static function checkEmailExists($email, $getUserId = false) {
        $sql = "SELECT
                    pk_user_id AS id
                FROM tb_user
                WHERE email = :email";
        $params = array(
            ':email' => $email
        );

        if ($getUserId) {
            return parent::fetchOne($sql, $params);
        }

        $result = parent::fetchAll($sql, $params);
        return (count($result) > 0);
    }

    /**
     * Checks whether or not a given hash string is the correct hash string for the provided email address
     *
     * @author Craig Knott
     *
     * @param string $email
     * @param string $hashString
     *
     * @return int The user id of the user this combination belongs to, or false if no one
     */
    public static function checkEmailHash($email, $hashString) {
        $sql = "SELECT
                    pk_user_id AS id
                FROM tb_user
                WHERE email = :email
                AND md5(concat(pk_user_id, email)) = :hash";
        $params = array(
            ':email' => $email,
            ':hash' => $hashString
        );

        $result = parent::fetchOne($sql, $params);

        if ($result !== false) {
            return $result->id;
        }
        return false;
    }
}


