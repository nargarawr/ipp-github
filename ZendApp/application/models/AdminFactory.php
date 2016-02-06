<?

/**
 * Class AdminFactory
 *
 * Manages all administrative settings in the database
 *
 * @author Craig Knott
 *
 */
class AdminFactory extends ModelFactory {

    /**
     * Returns all the current site administration settings
     *
     * @author Craig Knott
     *
     * @return object The contents of tb_site_admin
     */
    public static function getSiteAdmin() {
        $sql = "SELECT *
                FROM tb_site_admin";
        $params = array();
        return parent::fetchOne($sql, $params);
    }

    /**
     * Sets the site to be locked or unlocked, based on {$locked}
     *
     * @author Craig Knott
     *
     * @param bool $locked Whether the site should be locked or unlock
     */
    public static function setSiteLocked($locked) {
        $sql = "UPDATE tb_site_admin
                SET is_locked = :locked";
        $params = array(
            ':locked' => $locked
        );
        parent::execute($sql, $params);
    }

    /**
     * Displays a new announcement
     *
     * @author Craig Knott
     *
     * @param string $message   The announcement to be posted
     * @param bool   $emailed   Whether the announcement will be emailed out
     * @param int    $createdBy Who posted the announcement
     */
    public static function postAnnouncement($message, $emailed, $createdBy) {
        $sql = "UPDATE tb_announcement
                SET is_active = 0";
        $params = array();
        parent::execute($sql, $params);

        $sql = "INSERT INTO tb_announcement (
                    message,
                    created_by,
                    datetime_created,
                    is_active,
                    was_emailed
                ) VALUES (
                    :message,
                        :created_by,
                        NOW(),
                        1,
                        :emailed
                )";
        $params = array(
            ':message'    => $message,
            ':created_by' => $createdBy,
            ':emailed'    => $emailed
        );
        parent::execute($sql, $params);
    }


    /**
     * Inserts a row into tb_admin_log whenever an admin action is performed
     *
     * @author Craig Knott
     *
     * @param int $userId The admin who performed this action
     * @param string $action The action that was performed
     */
    public static function updateAdminLog($userId, $action) {
        $sql = "INSERT INTO tb_admin_log (
                    fk_user_id,
                    datetime,
                    action
                ) VALUES (
                    :userId,
                    NOW(),
                    :action
                )";
        $params = array(
            ':userId' => $userId,
            ':action' => $action
        );

        parent::execute($sql, $params);
    }

    /**
     * Returns the message of the most recent announcement
     *
     * @author Craig Knott
     */
    public static function getCurrentAnnouncement() {
        $sql = "SELECT
                    message
                FROM tb_announcement
                WHERE is_active = 1
                ORDER BY datetime_created ASC
                LIMIT 1;";
        $params = array();
        $result = parent::fetchOne($sql, $params);
        if ($result !== false) {
            return $result->message;
        }
        return $result;
    }

    /**
     * Deactivates any active announcements
     *
     * @param int $userId The id of the user performing this action
     *
     * @author Craig Knott
     */
    public static function clearAnnouncements($userId) {
        $sql = "UPDATE tb_announcement
                SET is_active = 0";
        $params = array();
        parent::execute($sql, $params);

        AdminFactory::updateAdminLog($userId, 'Announcements Cleared');
    }

    /**
     * Finds a user based on the given search term
     *
     * @param string $searchTerm The term to search with, can either represent the user id, the username, or user email address
     *
     * @author Craig Knott
     */
     public static function findUser($searchTerm) {
        if (is_numeric($searchTerm)) {
            $sql = "SELECT pk_user_id as id
                    FROM tb_user u
                    WHERE pk_user_id = :searchTerm
                    OR username = :searchTerm";
        } else {
            $sql = "SELECT pk_user_id as id
                    FROM tb_user u
                    WHERE username = :searchTerm
                    OR email = :searchTerm";
        }

        $params = array(
            ':searchTerm' => $searchTerm
        );

        $result = parent::fetchOne($sql, $params);
        if ($result !== false) {
            return $result->id;
        }

        return $result;
     }

    /**
     * Sets the 'should_deauth' flag for all user accounts to 1. When the users next navigate to a page, they will be
     * logged out and will need to log in again
     *
     * @author Craig Knott
     */
    public static function deauthoriseSessions() {
        $sql = "UPDATE tb_user
                SET should_deauth = 1
                WHERE is_admin = 0";
        $params = array();
        parent::execute($sql, $params);
    }


    /**
     * Gets site statistics/figures
     *
     * @author Craig Knott
     *
     * @param string $from When to start the statistics from (Y-m-d h:i:s date)
     *
     * @return Object of statistics
     */
    public static function getSiteStatistics($from) {
        $sql = "select
                    (select count(pk_user_id) from tb_user where is_deleted = 0 and datetime_created >= '" . $from .  "'
                    ) as new_users,
                    (select count(pk_route_id) from tb_route where is_deleted = 0 and datetime_created >= '" . $from .  "'
                    ) as new_routes,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'comment' and datetime >= '" . $from .  "'
                    ) as new_comments,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'download' and datetime >= '" . $from .  "'
                    ) as new_downloads,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'fork' and datetime >= '" . $from .  "'
                    ) as new_forks,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'rate' and datetime >= '" . $from .  "'
                    ) as new_ratings,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'share' and datetime >= '" . $from .  "'
                    ) as new_shares,
                    (select count(pk_user_id) from tb_user where is_deleted = 0
                    ) as total_users,
                    (select count(pk_route_id) from tb_route where is_deleted = 0
                    ) as total_routes,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'comment'
                    ) as total_comments,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'download'
                    ) as total_downloads,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'fork'
                    ) as total_forks,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'rate'
                    ) as total_rating,
                    (select count(pk_route_social_log_id) from tb_route_social_log where action = 'share'
                    ) as total_shares;";
        $params = array();
        return parent::fetchOne($sql, $params);
    }
}
