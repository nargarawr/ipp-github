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



}
