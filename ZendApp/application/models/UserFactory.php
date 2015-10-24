<?

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
     * @return array(int) Array of ids for users where this username/password combination is valid
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

    public static function checkEmailAllowed($userId, $email) {
        $sql = "SELECT
                    pk_user_id as userId
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
     * @param int    $userId Id of user to update
     * @param string $fname  New account first name
     * @param string $lname  New account last name
     * @param string $email  New account email
     * @param string $location New account location
     *
     * @return void
     */
    public static function updateUserDetails($userId, $fname, $lname, $email, $location) {
        $sql = "UPDATE tb_user
                SET fname = :fname,
                    lname = :lname,
                    email = :email,
                    location = :location,
                    datetime_updated = NOW()
                WHERE pk_user_id = :userId";
        $params = array(
            ':fname' => $fname,
            ':lname' => $lname,
            ':email' => $email,
            ':location' => $location,
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

}
