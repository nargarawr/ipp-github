<?

class LoginFactory extends ModelFactory {

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


