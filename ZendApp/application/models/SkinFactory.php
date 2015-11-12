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
     * Returns all skins owned by a particular user, grouped by slot
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to look for
     *
     * @return array Array of user skins
     */
    public static function getAllUserSkins($userId) {
        $sql = "SELECT
                    s.pk_skin_id AS id,
                    s.name AS name,
                    s.img AS img,
                    REPLACE(s.img, '.', '_thumb.') AS thumb,
                    iss.name AS slot_name,
                    o.equipped AS equipped,
                    s.reason AS reason
                FROM tb_user u
                JOIN tb_skin_owner o
                ON u.pk_user_id = o.fk_user_id
                JOIN tb_skin s
                ON o.fk_skin_id = s.pk_skin_id
                JOIN tb_skin_slot iss
                ON s.fk_slot_id = iss.pk_skin_slot_id
                WHERE pk_user_id = :userId
                ORDER BY o.equipped DESC, iss.pk_skin_slot_id ASC, pk_skin_id";
        $params = array(
            ':userId' => $userId
        );
        $results = parent::fetchAll($sql, $params);

        $skins = array();
        foreach ($results as $skin) {
            if (!(array_key_exists($skin->slot_name, $skins))) {
                $skins[$skin->slot_name] = array();
            }
            array_push($skins[$skin->slot_name], $skin);
        }

        return $skins;
    }

    /**
     * Returns all skins
     *
     * @author Craig Knott
     *
     * @param int $userId If a user Id is given, the results will return a row for skins the user owns
     *
     * @return array Array of all skins
     */
    public static function getAllSkins($userId = null) {
        $sql = "SELECT
                    s.pk_skin_id AS id,
                    s.name AS name,
                    s.img AS img,
                    REPLACE(s.img, '.', '_thumb.') AS thumb,
                    ss.name AS slot_name,
                    s.reason AS reason,
                    IFNULL(o.fk_user_id, 0) AS owned
                FROM tb_skin s
                JOIN tb_skin_slot ss
                ON ss.pk_skin_slot_id = s.fk_slot_id
                LEFT JOIN (
                    SELECT
                        *
                    FROM tb_skin_owner
                    WHERE fk_user_id = 1
                ) AS o
                ON o.fk_skin_id = s.pk_skin_id;";
        $params = array();
        $results = parent::fetchAll($sql, $params);

        $skins = array();
        foreach ($results as $skin) {
            if (!(array_key_exists($skin->slot_name, $skins))) {
                $skins[$skin->slot_name] = array();
            }
            array_push($skins[$skin->slot_name], $skin);
        }

        return $skins;
    }

    /**
     * Gives the default skins to all new users -
     * New Explorer Title, "None" Title, "None" Head, "None" Neck and "None" Badge
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
                ) VALUES
                (1, :userId, 1),
                (6, :userId, 0),
                (7, :userId, 1),
                (8, :userId, 1),
                (9, :userId, 1)";

        $params = array(
            ':userId' => $userId
        );

        parent::execute($sql, $params);
    }

    /**
     * Given a user Id, produces an array of statistics about them, including: User Average Rating, User Ratings
     * Given, User Ratings Received, User Comments Given, User Comments Received, User forks, Users routes forked,
     * User shares, User shares received, User downloads, User downloads received, max comments on a route, max
     * ratings on a route, max shares on a route, max downloads on a route, max forks on a route, account age, number
     * of routes, and number of points
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user
     *
     * @return object Object of user statistics
     */
    public static function getUserStats($userId) {
        /*
         * TODO
         *
         * Comments on a particular route
         * Ratings on a particular route
         * Shares on a particular route
         * Downloads on a particular route
         * Forks on a particular route
         *
         * Account age
         *
         * Number of routes
         * Number of points
         *
         */

        $sql = "SELECT
                    ratingAverage,
                    ratingsReceived,
                    ratingsGiven,
                    commentsReceived,
                    commentsGiven,
                    downloadsGiven,
                    downloadsReceived,
                    sharesGiven,
                    sharesReceived,
                    forksGiven,
                    forksReceived
                FROM
                (SELECT
                    'c' AS chain,
                    FLOOR(avg(value) * 2) / 2 AS ratingAverage
                FROM tb_rating rating
                JOIN tb_route route
                ON rating.fk_route_id = route.pk_route_id
                JOIN tb_user user
                ON route.created_by = user.pk_user_id
                WHERE user.pk_user_id = :userId
                AND rating.is_deleted = 0) AS ratings
                JOIN
                    (
                    SELECT
                        'c' AS chain,
                        GROUP_CONCAT(IF(action='download',cnt,NULL)) AS downloadsGiven,
                        GROUP_CONCAT(IF(action='share' ,cnt,NULL)) AS sharesGiven,
                        GROUP_CONCAT(IF(action='fork',cnt,NULL)) AS forksGiven,
                        GROUP_CONCAT(IF(action='rate',cnt,NULL)) AS ratingsGiven,
                        GROUP_CONCAT(IF(action='comment',cnt,NULL)) AS commentsGiven
                    FROM
                    (
                        SELECT
                            count(action) AS cnt,
                            action
                        FROM tb_route_log rl
                            JOIN tb_user u
                                ON u.pk_user_id = rl.fk_user_id
                        WHERE u.pk_user_id = :userId
                        GROUP BY action
                    ) AS innerCount
                ) AS givenSocial
                ON givenSocial.chain = ratings.chain
                JOIN
                (
                    SELECT
                        'c' AS chain,
                        GROUP_CONCAT(IF(action='download',cnt,NULL)) AS downloadsReceived,
                        GROUP_CONCAT(IF(action='share' ,cnt,NULL)) AS sharesReceived,
                        GROUP_CONCAT(IF(action='fork',cnt,NULL)) AS forksReceived,
                        GROUP_CONCAT(IF(action='rate',cnt,NULL)) AS ratingsReceived,
                        GROUP_CONCAT(IF(action='comment',cnt,NULL)) AS commentsReceived
                    FROM (
                             SELECT
                                 COUNT(action) AS cnt,
                                 action
                             FROM tb_route_log rl
                                 JOIN tb_route r
                                     ON rl.fk_route_id = r.pk_route_id
                                 JOIN tb_user u
                                     ON u.pk_user_id = r.created_by
                             WHERE r.created_by = :userId
                             GROUP BY action
                         ) AS innerCount
                ) AS receivedSocial
                ON receivedSocial.chain = givenSocial.chain";
        $params = array(
            ":userId" => $userId
        );
        $stats = parent::fetchOne($sql, $params);

        $statsObj = (object)array(
            'ratingAverage'     => $stats->ratingAverage,
            'ratingsReceived'   => $stats->ratingsReceived,
            'ratingsGiven'      => $stats->ratingsGiven,
            'commentsReceived'  => $stats->commentsReceived,
            'commentsGiven'     => $stats->commentsGiven,
            'downloadsGiven'    => $stats->downloadsGiven,
            'downloadsReceived' => $stats->downloadsReceived,
            'sharesGiven'       => $stats->sharesGiven,
            'sharesReceived'    => $stats->sharesReceived,
            'forksGiven'        => $stats->forksGiven,
            'forksReceived'     => $stats->forksReceived,
            'accountAge'        => 10,
            'routeCount'        => 1
        );

        return $statsObj;
    }

    /**
     * Given an object of user statistics, awards skins to the user
     *
     * @author Craig Knott
     *
     * @param array $stats An array of different stats for the user
     */
    public static function allocateSkins($stats) {

    }

    /**
     * Sets the given skins as equipped, and unequips all other skins
     *
     * @author Craig Knott
     *
     * @param int                  $userId The user to do the action on
     * @param array(string => int) $skins  Array of skin types and the Id of which skin to equiped
     */
    public static function updateUserSkins($userId, $skins) {
        SkinFactory::removeAllSkins($userId);
        $sql = "UPDATE tb_skin_owner
                SET equipped = 1
                WHERE fk_user_id = :userId
                AND";
        foreach ($skins as $skin) {
            $sql .= " (fk_skin_id = " . $skin . ") OR";
        }
        $sql = rtrim($sql, 'OR');

        $params = array(
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Unequips all skins from a given user
     *
     * @author Craig Knott
     *
     * @param int $userId The user to do the action on
     */
    public static function removeAllSkins($userId) {
        $sql = "UPDATE tb_skin_owner
                SET equipped = 0
                WHERE fk_user_id = :userId";
        $params = array(
            ':userId' => $userId
        );
        parent::execute($sql, $params);
    }

}
