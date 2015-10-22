<?

class SirenFactory extends ModelFactory {

    /**
     * Get siren messages for the given app
     *
     * @author Craig Knott
     *
     * @param string $appUrl URL of the app
     *
     * @return array(siren) Array of active siren messages for app
     */
    public static function getSirenMessage($appUrl) {
        $sql = "SELECT
                    message
                FROM tb_siren
                WHERE is_active = 1
                AND affected_app = :appUrl";
        $params = array(
            ':appUrl' => $appUrl
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all siren messages
     *
     * @author Craig Knott
     *
     * @return array(siren) Array of all siren messages
     */
    public static function getAllSirenMessages() {
        $sql = "SELECT
                    *
                FROM tb_siren";
        $params = array();
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all currently active siren messages
     *
     * @author Craig Knott
     *
     * @return array(siren) Array of active siren messages
     */
    public static function getAllActiveSirenMessages() {
        $sql = "SELECT
                    *
                FROM tb_siren
                WHERE is_active = 1";
        $params = array();
        return parent::fetchAll($sql, $params);
    }

    /**
     * Get all non-active siren messages
     *
     * @author Craig Knott
     *
     * @return array(siren) Array of past siren messages
     */
    public static function getAllPastSirenMessages() {
        $sql = "SELECT
                    *
                FROM tb_siren
                WHERE is_active = 0";
        $params = array();
        return parent::fetchAll($sql, $params);
    }

    /**
     * Change the active state of a given siren message
     *
     * @author Craig Knott
     *
     * @param int $sirenId   Id of siren to modify
     * @param int $is_active State of siren (1 active, 0 non-active)
     * @param int $updatedBy Who updated this row
     *
     * @return void
     */
    public static function changeSirenState($sirenId, $is_active, $updatedBy) {
        $sql = "UPDATE
                    tb_siren
                SET is_active = :is_active,
                    updated_by = :updatedBy,
                    datetime_updated = NOW()
                WHERE pk_siren_id = :sirenId";
        $params = array(
            ':is_active' => $is_active,
            ':sirenId'   => $sirenId,
            ':updatedBy' => $updatedBy
        );
        parent::execute($sql, $params);
    }

    /**
     * Add a new siren to the site
     *
     * @author Craig Knott
     *
     * @param string $message   The message to display
     * @param string $app       The URL of the app to add the message to
     * @param int    $createdBy Who created this row
     *
     * @return void
     */
    public static function addSiren($message, $app, $createdBy) {
        $sql = "INSERT INTO tb_siren (
                    message,
                    is_active,
                    affected_app,
                    created_by,
                    datetime_created,
                    updated_by,
                    datetime_updated
                ) VALUES (
                    :message,
                    :app,
                    :createdBy,
                    NOW(),
                    :createdBy,
                    NOW()
                )";
        $params = array(
            ':message'   => $message,
            ':app'       => $app,
            ':createdBy' => $createdBy
        );
        parent::execute($sql, $params);
    }

}

?>