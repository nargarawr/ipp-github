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
     * @param string $age      New user date of birth
     */
    public static function updateUserDetails($userId, $fname, $lname, $email, $location, $bio, $age) {
        $sql = "UPDATE tb_user
                SET fname = :fname,
                    lname = :lname,
                    email = :email,
                    location = :location,
                    bio = :bio,
                    age = :age,
                    datetime_updated = NOW()
                WHERE pk_user_id = :userId";
        $params = array(
            ':fname'    => $fname,
            ':lname'    => $lname,
            ':email'    => $email,
            ':location' => $location,
            ':bio'      => $bio,
            ':age'      => $age,
            ':userId'   => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Update user preferences
     *
     * @author Craig Knott
     *
     * @param int     $userId              The id of the user to update the preferences for
     * @param boolean $emailOnRouteComment The email_on_route_comment setting value
     * @param boolean $emailOnRouteFork    The email_on_route_fork setting value
     * @param boolean $emailOnRouteRating  The email_on_route_rating setting value
     * @param boolean $emailOnAnnouncement The email_on_announcement setting value
     *
     * @return void
     */
    public static function updateUserPreferences($userId, $emailOnRouteComment, $emailOnRouteFork, $emailOnRouteRating,
                                                 $emailOnAnnouncement) {
        $sql = "UPDATE tb_user_preference
                SET email_on_route_comment = :emailOnRouteComment,
                    email_on_route_fork = :emailOnRouteFork,
                    email_on_route_rating = :emailOnRouteRating,
                    email_on_announcement = :emailOnAnnouncement
                WHERE fk_user_id = :userId";
        $params = array(
            ':emailOnRouteComment' => $emailOnRouteComment,
            ':emailOnRouteFork'    => $emailOnRouteFork,
            ':emailOnRouteRating'  => $emailOnRouteRating,
            ':emailOnAnnouncement' => $emailOnAnnouncement,
            ':userId'              => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Constructs a user from a user Id
     *
     * @author Craig Knott
     *
     * @param int     $userId   Id of the user to get the data for
     * @param boolean $asObject Whether to return the query results, or the full user object
     *
     * @return User | Object The user object of this user, or the returned SQL query
     */
    public static function getUser($userId, $asObject = true) {
        $sql = "SELECT
                    pk_user_id AS id,
                    username,
                    fname,
                    lname,
                    email,
                    location,
                    age,
                    bio,
                    login_count,
                    last_login,
                    is_admin,
                    is_banned,
                    is_shadow_banned,
                    datetime_created,
                    datetime_updated,
                    is_confirmed,
                    email_on_route_comment,
                    email_on_route_fork,
                    email_on_route_rating,
                    email_on_announcement,
                    should_deauth
                FROM tb_user u
                JOIN tb_user_preference up
                ON u.pk_user_id = up.fk_user_id
                WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        $user = parent::fetchOne($sql, $params);

        if ($asObject) {
            $userObject = new User($user->id);
            return $userObject;
        }

        return $user;
    }

    /**
     * Returns information about the owner of a specified route
     *
     * @author Craig Knott
     *
     * @param int $routeId Id of the route whose owners we want details own
     *
     * @return object Details about the owner of this route
     */
    public static function getRouteOwnerDetails($routeId) {
        $sql = "SELECT
                    pk_user_id AS id,
                    username,
                    email,
                    email_on_announcement AS emailOnRouteComment,
                    email_on_route_rating AS emailOnRouteRating,
                    email_on_route_fork AS emailOnRouteFork
                FROM tb_user u
                JOIN tb_route r
                ON u.pk_user_id = r.created_by
                JOIN tb_user_preference up
                ON u.pk_user_id = up.fk_user_id
                WHERE pk_route_id = :routeId";
        $params = array(
            ':routeId' => $routeId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Either shadow bans or unshadow bans the user with the given user id
     *
     * @param int    $userId The id of the user to shadow ban/unshadow ban
     * @param string $status Whether to set this user as shadow banned (1) or not (0)
     *
     * @author Craig Knott
     */
    public static function setUserShadowBanStatus($userId, $status) {
        $sql = "UPDATE tb_user
             SET is_shadow_banned = :status
             WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId,
            ':status' => $status
        );
        parent::execute($sql, $params);
    }

    /**
     * Sets the ban status of the given user, either banned or not banned
     *
     * @param int    $userId The id of the user to ban or unban
     * @param string $status Whether to set this user as banned (1) or not (0)
     *
     * @author Craig Knott
     */
    public static function setUserBanStatus($userId, $status) {
        $sql = "UPDATE tb_user
             SET is_banned = :status
             WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId,
            ':status' => $status
        );
        parent::execute($sql, $params);
    }

    /**
     * Deletes the user with the given id
     *
     * @param int    $userId The id of the user to delete
     *
     * @author Craig Knott
     */
    public static function deleteUser($userId) {
        $sql = "UPDATE tb_user
               SET is_deleted = 1
               WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Used to change the admin status of a user
     *
     * @author Craig Knott
     *
     * @param int     $userId The Id of the user to update
     * @param boolean $status Whether to set this user as admin (true) or not (false)
     */
    public static function setAdmin($userId, $status) {
        $sql = "UPDATE tb_user
                SET is_admin = :status
                WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId,
            ':status' => $status
        );
        parent::execute($sql, $params);

        // Add/Remove admin badge skin
        if ($status) {
            $sql = "INSERT INTO tb_skin_owner (
                        fk_skin_id,
                        fk_user_id,
                        equipped
                    ) VALUES (
                        43,
                        :userId,
                        0
                    )";
        } else {
            $sql = "DELETE FROM tb_skin_owner
                WHERE fk_user_id = :userId
                AND fk_skin_id = 43";
        }
        $params = array(
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

}
