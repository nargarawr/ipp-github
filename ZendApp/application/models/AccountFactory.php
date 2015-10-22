<?php

class AccountFactory extends ModelFactory {

    /**
     * Returns the auth adapter, to check user credentials
     *
     * @author Craig Knott
     *
     * @return Zend_Auth_Adapter_DbTable
     */
    public static function getAuthAdapter() {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

        $authAdapter->setTableName('tb_user')
            ->setIdentityColumn('username')
            ->setCredentialColumn('password')
            ->setCredentialTreatment('MD5(?)');

        return $authAdapter;
    }

    /**
     * Update the count of user logins
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to update
     *
     * @return void
     */
    public static function updateLoginCount($userId) {
        $sql = "UPDATE tb_user
                SET login_count = login_count + 1
                WHERE pk_user_id = :userId;";
        $params = array(
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

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
                SET password = MD5(:password)
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
                AND pk_user_id = :userId;";
        $params = array(
            ':userId'   => $userId,
            ':password' => $password
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all suppressed apps for a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get information for
     *
     * @return array(app) Suppressed apps for the user
     */
    public static function getSuppressedApps($userId) {
        $sql = "SELECT
                    pk_app_id,
                    url
                FROM tb_user_app_suppression tuas
                JOIN tb_app
                ON fk_pk_app_id = pk_app_id
                WHERE fk_pk_user_id = :userId
                AND tuas.is_active = 1";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all settings for a specific app
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to get information for
     * @param int $appId  Id of app to get settings for
     *
     * @return object Object of all settings and their values
     *       | null
     */
    public static function getSettings($userId, $appId) {
        $sql = "SELECT
                    name,
                    number_value,
                    string_value
                FROM tb_setting
                JOIN tb_user_setting
                ON pk_setting_id = fk_pk_setting_id
                WHERE fk_pk_app_id = :appId
                AND fk_pk_user_id = :userId";
        $params = array(
            ':userId' => $userId,
            ':appId'  => $appId
        );
        $results = parent::fetchAll($sql, $params);

        $settings = array();
        if (count($results) == 0) {
            return null;
        }

        foreach ($results as $result) {
            $settings[$result->name] = is_null($result->number_value)
                ? $result->string_value
                : floatval($result->number_value);
        }
        return (object)$settings;

    }

    /**
     * Updates the account details for the user id specified
     *
     * @author Craig Knott
     *
     * @param int    $userId Id of user to update
     * @param string $fName  New account first name
     * @param string $lName  New account last name
     * @param string $email  New account email
     *
     * @return void
     */
    public static function updateAccountDetails($userId, $fName, $lName, $email) {
        $sql = "UPDATE tb_user
                SET fname = :firstname,
                    lname = :lastname,
                    email = :email
                WHERE pk_user_id = :userId;";
        $params = array(
            ':userId'    => $userId,
            ':firstname' => $fName,
            ':lastname'  => $lName,
            ':email'     => $email
        );
        parent::execute($sql, $params);
    }

    /**
     * Updates suppressions for the user id specified
     *
     * @author Craig Knott
     *
     * @param int             $userId       Id of user to update
     * @param array(int, int) $suppressions Array of apps, and their suppressed status (i.e, (1,1) would suppress app 1)
     *
     * @return void
     */
    public static function updateUserSuppressions($userId, $suppressions) {
        foreach ($suppressions as $s) {
            $sql = "INSERT INTO tb_user_app_suppression (
                        fk_pk_user_id,
                        fk_pk_app_id,
                        is_active
                    ) VALUES (
                        :userId, "
                . $s['appid'] . ", "
                . $s['suppressed'] . "
                    )
                    ON DUPLICATE KEY
                    UPDATE is_active = " . $s['suppressed'];
            $params = array(
                ':userId' => $userId
            );
            parent::execute($sql, $params);
        }
    }

    /**
     * Get details of a user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of user to update
     *
     * @return object Object of user details for given id
     */
    public static function getUserDetails($userId) {
        $sql = "SELECT
                    pk_user_id AS id,
                    username,
                    is_active,
                    datetime_created,
                    email,
                    fname,
                    lname,
                    login_count
                FROM tb_user
                WHERE pk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Update app settings for a user
     *
     * @author Craig Knott
     *
     * @param int                       $userId   Id of user to update
     * @param array(string, int|string) $settings Array of settings, with their id and their value (i.e, (1, 'test'))
     *
     * @return void
     */
    public static function updateSettings($userId, $settings) {
        foreach ($settings as $settingName => $setting) {
            $sql = "INSERT INTO tb_user_setting (
                        fk_pk_user_id,
                        fk_pk_setting_id,
                        number_value,
                        string_value
                    ) VALUES (
                        :userId,
                        (SELECT pk_setting_id FROM tb_setting WHERE name = '" . $settingName . "' AND fk_pk_app_id = " . $setting->app . "),"
                . (is_numeric($setting->value) ? $setting->value . ", NULL" : "NULL, '" . $setting->value . "'") . "
                    )
                    ON DUPLICATE KEY
                    UPDATE " . (is_numeric($setting->value)
                    ? "number_value = " . $setting->value . ", string_value = NULL;"
                    : "number_value = NULL, string_value = '" . $setting->value . "';");
            $params = array(
                ':userId' => $userId
            );

            parent::execute($sql, $params);
        }
    }

    /**
     * Create a new account with specified information
     *
     * @author Craig Knott
     *
     * @param string $username Username of new account
     * @param string $email    Email address of new account
     * @param string $fname    First name of new account
     * @param string $lname    Last name of new account
     *
     * @return void
     */
    public static function createAccount($username, $email = null, $fname = null, $lname = null) {
        // Add to tb_user
        $sql = "INSERT INTO tb_user (
                    username,
                    is_active,
                    user_type,
                    password,
                    datetime_created,
                    email,
                    fname,
                    lname,
                    login_count
                ) VALUES (
                    :username,
                    1,
                    1,
                    MD5('password'),
                    NOW(),
                    :email,
                    :fname,
                    :lname,
                    0
                )";
        $params = array(
            ':username' => $username,
            ':email'    => $email,
            ':fname'    => $fname,
            ':lname'    => $lname
        );
        parent::execute($sql, $params);
    }

    /**
     * Check whether a given username is unique
     *
     * @author Craig Knott
     *
     * @param string $username Username to check
     *
     * @return bool Whether or not this username is unique
     */
    public static function checkUniqueUsername($username) {
        $sql = "SELECT
                    pk_user_id
                FROM tb_user
                WHERE username = :username";
        $params = array(
            ':username' => $username
        );
        return (count(parent::fetchAll($sql, $params)) == 0);
    }

    /**
     * Return details on all user accounts, that fit the search criteria
     *
     * @author Craig Knott
     *
     * @param int    $userId   Id of account to search for
     * @param string $username Username of account to search for
     * @param string $email    Email of account to search for
     * @param string $fname    First name of account to search for
     * @param string $lname    Last name of account to search for
     *
     * @return array(user) All users found in the search
     */
    public static function findUserAccount($userId = null, $username = null, $email = null, $fname = null,
                                           $lname = null) {
        $sql = "SELECT
                    datetime_created,
                    email,
                    fname,
                    is_active,
                    lname,
                    login_count,
                    pk_user_id AS id,
                    user_type,
                    username
                FROM tb_user
                WHERE 1 = 1
                AND is_active = 1 ";
        $params = array();
        if (!is_null($userId)) {
            $sql .= "AND pk_user_id = :userId ";
            $params[':userId'] = $userId;
        }

        if (!is_null($username)) {
            $sql .= "AND username = :username ";
            $params[':username'] = $username;
        }

        if (!is_null($email)) {
            $sql .= "AND email LIKE :email ";
            $params[':email'] = '%' . $email . '%';
        }

        if (!is_null($fname)) {
            $sql .= "AND fname LIKE :fname ";
            $params[':fname'] = '%' . $fname . '%';
        }

        if (!is_null($lname)) {
            $sql .= "AND lname LIKE :lname ";
            $params[':lname'] = '%' . $lname . '%';
        }
        return self::fetchAll($sql, $params);
    }

    /**
     * Returns detail on all user accounts where one of their fields matches the search string (name, email or username)
     *
     * @author Craig Knott
     *
     * @param string $searchString Either the name, username or email of an account
     *
     * @return array(user) All users found in the search
     */
    public static function findUserAccountWithoutSearchLabels($searchString) {
        $searchString = '%' . $searchString . '%';
        $sql = "SELECT
                    fname,
                    lname,
                    username
                FROM tb_user
                WHERE 1 = 1
                AND is_active = 1
                OR username LIKE :searchString
                OR fname LIKE :searchString
                OR lname LIKE :searchString
                OR CONCAT(fname, ' ', lname) LIKE :searchString
                OR email LIKE :searchString";
        $params = array(
            ':searchString' => $searchString
        );

        return parent::fetchAll($sql, $params);
    }

    /**
     * Get the names of all apps on the site
     *
     * @author Craig Knott
     *
     * @return array(string) Array of names of all apps on the site
     */
    public static function getAllAppNames() {
        $sql = "SELECT
                    url
                FROM tb_app;";
        $params = null;
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get the name of the app represented by the controller given
     *
     * @author Craig Knott
     *
     * @param string $controller Name of controller to get app name for
     *
     * @return string Name of app represented by the controller
     */
    public static function getAppName($controller) {
        if ($controller === 'index') {
            return 'Craig Knott';
        }

        $sql = "SELECT
                    name
                FROM tb_app
                WHERE url = :controller";
        $params = array(
            ':controller' => $controller
        );
        $result = parent::fetchOne($sql, $params);

        if ($result === false) {
            return 'Engagement Center';
        }

        return $result->name;
    }

    /**
     * Determine whether or not user can access the specified controller/action
     *
     * @author Craig Knott
     *
     * @param string $controller Name of app controller
     * @param string $action     Name of app action
     * @param object $user       User object
     *
     * @return bool Whether or not the user can access the specified controller/action
     */
    public static function canAccessApp($controller, $action, $user) {
        $userId = $user->userId;

        // Only allow admins on tools
        if ($controller === 'tools' && $user->userType != 0) {
            return false;
        }

        if ($controller === 'account') {
            $controller = $action;
        }

        // Check if it's an app
        $sql = "SELECT
                    pk_app_id,
                    is_active
                FROM tb_app a
                WHERE url = :controller";
        $params = array(
            ':controller' => $controller
        );
        $result = parent::fetchOne($sql, $params);

        // Not an app
        if ($result === false) {
            return true;
        }

        // App is not active
        if ($result->is_active == 0) {
            return false;
        }

        // Check for suppressions
        $sql = "SELECT
                    *
                FROM tb_user_app_suppression
                WHERE fk_pk_app_id = :appId
                AND fk_pk_user_id = :userId
                AND is_active = 1";
        $params = array(
            ':appId'  => $result->pk_app_id,
            ':userId' => $userId
        );

        $result = parent::fetchOne($sql, $params);

        // No suppression exists
        if ($result === false) {
            return true;
        }

        return false;
    }
}