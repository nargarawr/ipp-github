<?

/**
 * Class UserFactory
 *
 * Manages the user, and their interaction with the database
 *
 * @author Craig Knott
 *
 */
class UserFactory extends ModelFactory {

    /**
     * Update a user's password
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of user to update
     * @param string $password New password to set
     *
     * @return void
     */
    public static function updatePassword($userId, $password) {
        $sql = "UPDATE tb_user
                SET password = MD5(:password),
                    datetime_updated = NOW()
                WHERE pk_user_id = :userId;";
        $params = array(
            ':userId'   => $userId,
            ':password' => $password
        );
        parent::execute($sql, $params);
    }

    /**
     * Check if a user has given a correct password
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of user to check against
     * @param string $password Password to check if correct
     *
     * @return int, Id for user where this username/password combination is valid
     */
    public static function checkPassword($userId, $password) {
        $sql = "SELECT
                    pk_user_id
                FROM tb_user
                WHERE password = MD5(:password)
                AND pk_user_id = :userId";
        $params = array(
            ':userId'   => $userId,
            ':password' => $password
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Check that a given email address doesn't already exist in the system (or if it does, it belongs to the
     * current user)
     *
     * @author Craig Knott
     *
     * @param int    $userId The Id of the user we are checking against
     * @param string $email  The email address we are looking for
     *
     * @return bool Whether this email address can be used or not
     */
    public static function checkEmailAllowed($userId, $email) {
        $sql = "SELECT
                    pk_user_id AS userId
                FROM tb_user
                WHERE email = :email";
        $params = array(
            ':email' => $email
        );
        $result = parent::fetchOne($sql, $params);

        // No results or the owner is currently this user
        return ($result == false || $result->userId == $userId);
    }

    /**
     * Updates the account details for the user id specified
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of user to update
     * @param string $fname    New account first name
     * @param string $lname    New account last name
     * @param string $email    New account email
     * @param string $location New account location
     * @param string $bio      New account bio
     *
     * @return void
     */
    public static function updateUserDetails($userId, $fname, $lname, $email, $location, $bio) {
        $sql = "UPDATE tb_user
                SET fname = :fname,
                    lname = :lname,
                    email = :email,
                    location = :location,
                    bio = :bio,
                    datetime_updated = NOW()
                WHERE pk_user_id = :userId";
        $params = array(
            ':fname'    => $fname,
            ':lname'    => $lname,
            ':email'    => $email,
            ':location' => $location,
            ':bio'      => $bio,
            ':userId'   => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets all information for the user represented by the given user ID
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to get the data for
     *
     * @return User The user object of this user
     */
    public static function getUser($userId) {
        $sql = "SELECT
                    pk_user_id as id,
                    username,
                    fname,
                    lname,
                    email,
                    location,
                    bio,
                    login_count,
                    last_login,
                    is_admin,
                    is_banned,
                    is_shadow_banned,
                    datetime_created,
                    datetime_updated,
                    is_confirmed
                FROM tb_user
                WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        $user = parent::fetchOne($sql, $params);

        $userObject = new User(
            $user->username,
            $user->id,
            $user->fname,
            $user->lname,
            $user->email,
            $user->location,
            $user->bio,
            $user->login_count,
            $user->last_login,
            $user->is_admin,
            $user->is_banned,
            $user->is_shadow_banned,
            $user->datetime_created,
            $user->datetime_updated,
            $user->is_confirmed
        );
        return $userObject;
    }

}
