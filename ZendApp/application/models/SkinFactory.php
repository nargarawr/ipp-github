<?

/**
 * Class SkinFactory
 *
 * Manages everything to do with Skins (user customisation incentives)
 *
 * @author Craig Knott
 *
 */
class SkinFactory extends ModelFactory {

    /**
     * Returns all skins equipped by a particular user
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to look for
     *
     * @return array Array of user skins
     */
    public static function getUserEquippedSkins($userId) {
        $sql = "SELECT
                    s.pk_skin_id AS id,
                    s.name AS name,
                    s.img AS img,
                    iss.name AS slot_name
                FROM tb_user u
                JOIN tb_skin_owner o
                ON u.pk_user_id = o.fk_user_id
                JOIN tb_skin s
                ON o.fk_skin_id = s.pk_skin_id
                JOIN tb_skin_slot iss
                ON s.fk_slot_id = iss.pk_skin_slot_id
                WHERE pk_user_id = :userId
                AND equipped = 1";
        $params = array(
            ':userId' => $userId
        );
        $results = parent::fetchAll($sql, $params);

        $skins = array();

        foreach ($results as $skin) {
            $skins[$skin->slot_name] = $skin;
        }

        return $skins;
    }

    /**
     * Gives the default skins to all new users
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to assign the skins to
     */
    public static function assignStartingSkins($userId) {
        $sql = "INSERT INTO tb_skin_owner (
                    fk_skin_id,
                    fk_user_id,
                    equipped
                ) VALUES (
                    1,
                    :userId,
                    1
                )";

        $params = array(
            ':userId' => $userId
        );

        parent::execute($sql, $params);
    }

}
