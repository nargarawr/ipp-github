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
                    IFNULL(o.fk_user_id, 0) AS owned,
                    (
                        SELECT
                            100 * (count(fk_skin_id) / (SELECT count(1) FROM tb_user))
                        FROM tb_skin_owner
                        WHERE fk_skin_id = s.pk_skin_id
                    ) AS ownerPercentage
                FROM tb_skin s
                JOIN tb_skin_slot ss
                ON ss.pk_skin_slot_id = s.fk_slot_id
                LEFT JOIN (
                    SELECT
                        *
                    FROM tb_skin_owner
                    WHERE fk_user_id = :userId
                ) AS o
                ON o.fk_skin_id = s.pk_skin_id;";
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
                (1, :userId, 0),
                (2, :userId, 1),
                (3, :userId, 1),
                (4, :userId, 1),
                (5, :userId, 1)";

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
     * of routes, and number of points.
     *
     * The query is really long, but it's quite simple. Each section gets a certain sets of statistics, and then they
     * all join together using the fake 'chain' field, so they can be displayed as a single row
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user
     *
     * @return object Object of user statistics
     */
    public static function getUserStats($userId) {
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
                    forksReceived,
                    accountAge,
                    routeCount.cnt AS routeCount,
                    pointCount.cnt AS pointCount,
                    mostDownloadsForOne,
                    mostCommentsForOne,
                    mostForksForOne,
                    mostRatingsForOne,
                    mostSharesForOne
                FROM (
                    SELECT
                        'c' AS chain,
                        FLOOR(avg(value) * 2) / 2 AS ratingAverage,
                        datediff(NOW(), user.datetime_created) AS accountAge
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
                        IFNULL(GROUP_CONCAT(IF(action='download',cnt,NULL)),0) AS downloadsGiven,
                        IFNULL(GROUP_CONCAT(IF(action='share' ,cnt,NULL)),0) AS sharesGiven,
                        IFNULL(GROUP_CONCAT(IF(action='fork',cnt,NULL)),0) AS forksGiven,
                        IFNULL(GROUP_CONCAT(IF(action='rate',cnt,NULL)),0) AS ratingsGiven,
                        IFNULL(GROUP_CONCAT(IF(action='comment',cnt,NULL)),0) AS commentsGiven
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
                        IFNULL(GROUP_CONCAT(IF(action='download',cnt,NULL)),0) AS downloadsReceived,
                        IFNULL(GROUP_CONCAT(IF(action='share' ,cnt,NULL)),0) AS sharesReceived,
                        IFNULL(GROUP_CONCAT(IF(action='fork',cnt,NULL)),0) AS forksReceived,
                        IFNULL(GROUP_CONCAT(IF(action='rate',cnt,NULL)),0) AS ratingsReceived,
                        IFNULL(GROUP_CONCAT(IF(action='comment',cnt,NULL)),0) AS commentsReceived
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
                ON receivedSocial.chain = givenSocial.chain
                JOIN (
                    SELECT
                        'c' AS chain,
                        count(pk_route_id) AS cnt
                    FROM tb_route
                    WHERE created_by = :userId
                    AND is_deleted = 0
                ) AS routeCount
                ON routeCount.chain = receivedSocial.chain
                JOIN (
                    SELECT
                        'c' AS chain,
                        count(pk_point_id) AS cnt
                    FROM tb_point p
                    JOIN tb_route r
                    ON r.pk_route_id = p.fk_route_id
                    WHERE created_by = :userId
                    AND is_deleted = 0
                ) AS pointCount
                ON pointCount.chain = routeCount.chain
                JOIN (
                    SELECT
                        'c' AS chain,
                        substring_index(IFNULL(GROUP_CONCAT(IF(raw.action='download',raw.cnt,NULL)),0),',', 1) AS mostDownloadsForOne,
                        substring_index(IFNULL(GROUP_CONCAT(IF(raw.action='comment',raw.cnt,NULL)),0),',', 1) AS mostCommentsForOne,
                        substring_index(IFNULL(GROUP_CONCAT(IF(raw.action='fork',raw.cnt,NULL)),0),',', 1) AS mostForksForOne,
                        substring_index(IFNULL(GROUP_CONCAT(IF(raw.action='rate',raw.cnt,NULL)),0),',', 1) AS mostRatingsForOne,
                        substring_index(IFNULL(GROUP_CONCAT(IF(raw.action='share',raw.cnt,NULL)),0),',', 1) AS mostSharesForOne
                    FROM (
                        SELECT
                            count(action) AS cnt,
                            action,
                            rl.fk_route_id
                        FROM tb_route_log rl
                        JOIN tb_route r
                        ON r.pk_route_id = rl.fk_route_id
                        JOIN tb_user u
                        ON u.pk_user_id = rl.fk_user_id
                        LEFT JOIN tb_comment c
                        ON c.pk_comment_id = rl.action_value_id
                        LEFT JOIN tb_rating rating
                        ON rating.pk_rating_id = rl.action_value_id
                        WHERE r.created_by = :userId
                        AND r.is_deleted = 0
                        AND u.is_shadow_banned = 0
                        AND u.is_banned = 0
                        AND (c.is_deleted = 0 OR c.is_deleted IS NULL OR (c.is_deleted = 1 AND rl.action = 'rate'))
                        AND (rating.is_deleted = 0 OR rating.is_deleted IS NULL OR (rating.is_deleted = 1 AND rl.action = 'comment'))
                        GROUP BY action, rl.fk_route_id
                        ORDER BY action, count(action) DESC
                    ) AS raw
                ) AS mostSocialForOneRoute
                ON mostSocialForOneRoute.chain = pointCount.chain";
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
            'accountAge'        => $stats->accountAge,
            'routeCount'        => $stats->routeCount,
            'pointCount'        => $stats->pointCount
        );

        return $statsObj;
    }

    /**
     * Gets the requirements necessary to award all skins this user does not currently possess
     *
     * @author Craig Knott
     *
     * @param int $userId The user to check for
     */
    public static function getSkinRequirements($userId) {
        $sql = "SELECT
                    fk_skin_id AS skin,
                    requirement_tag AS tag,
                    requirement_value AS value
                FROM tb_skin_requirement
                WHERE fk_skin_id NOT IN (
                    SELECT
                        fk_skin_id
                    FROM tb_skin_owner
                    WHERE fk_user_id = :userId
                )";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Given an object of user statistics, awards skins to the user
     *
     * @author Craig Knott
     *
     * @param array $stats  An array of different stats for the user
     * @param int   $userId Id of user to allocate to
     */
    public static function allocateSkins($stats, $userId) {
        $requirements = self::getSkinRequirements($userId);

        $shouldAward = array();
        foreach ($requirements as $requirement) {
            if ($stats->{$requirement->tag} >= $requirement->value) {
                $shouldAward[] = $requirement->skin;
            }
        }

        if (count($shouldAward) === 0) {
            return;
        }

        $sql = "INSERT INTO tb_skin_owner (
                    fk_skin_id,
                    fk_user_id,
                    equipped
                ) VALUES ";
        foreach ($shouldAward as $award) {
            $sql .= "(" . $award . ", " . $userId . ", 0),";
        }
        $sql = rtrim($sql, ",");
        $params = array();

        parent::execute($sql, $params);
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
