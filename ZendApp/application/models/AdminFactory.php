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
}
