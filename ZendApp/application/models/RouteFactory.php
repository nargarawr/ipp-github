<?

/**
 * Class RouteFactory
 *
 * Manages all routes, and their interaction with the database
 *
 * @author Craig Knott
 *
 */
class RouteFactory extends ModelFactory {

    /**
     * Inserts a new route into the database
     *
     * @author Craig Knott
     *
     * @param string $name        Name of the route
     * @param string $description Description of the route
     * @param int    $isPrivate   Whether this route is private or not
     * @param int    $userId      The id of the owner of this route
     *
     * @return int The Id of the newly created route
     */
    public static function createRoute($name, $description, $isPrivate, $userId) {
        $sql = "INSERT INTO tb_route (
                    created_by,
                    name,
                    description,
                    is_private,
                    cost,
                    distance,
                    datetime_created,
                    datetime_updated
                ) VALUES (
                    :userId,
                    :name,
                    :description,
                    :isPrivate,
                    0,
                    0,
                    NOW(),
                    NOW()
                )";
        $params = array(
            ':userId'      => $userId,
            ':name'        => $name,
            ':description' => $description,
            ':isPrivate'   => $isPrivate
        );
        return parent::execute($sql, $params, true);
    }

    /**
     * Creates a new point for a given route
     *
     * @author Craig Knott
     *
     * @param object $point   The point object to be added
     * @param int    $routeId The id of the route to add this point to
     *
     * @return void
     */
    public static function createRoutePoint($point, $routeId) {
        $sql = "INSERT INTO tb_point (
                    fk_route_id,
                    name,
                    description,
                    latitude,
                    longitude
                ) VALUES (
                    :routeId,
                    :name,
                    :description,
                    :latitude,
                    :longitude
                )";
        $params = array(
            ':routeId'     => $routeId,
            ':name'        => $point->name,
            ':description' => $point->description,
            ':latitude'    => $point->lat,
            ':longitude'   => $point->lng
        );
        parent::execute($sql, $params);
    }

    /**
     * Get all routes for a given user (optionally also returns all points for each route)
     *
     * @author Craig Knott
     *
     * @param int  $userId               The user to get all routes for
     * @param bool $withPoints           Whether to return the routes with their points as well (stored in a $points
     *                                   array)
     *
     * @return array All routes for the given user
     */
    public static function getRoutesForUser($userId, $withPoints = false) {
        $sql = "SELECT
                    pk_route_id AS routeId,
                    name,
                    description,
                    is_private,
                    cost,
                    distance,
                    datetime_created AS created,
                    (SELECT count(1) FROM tb_point WHERE fk_route_id = pk_route_id) AS num_points,
                    IFNULL(
                        (SELECT FLOOR(avg(value) * 2) / 2  FROM tb_rating WHERE fk_route_id = tb_route.pk_route_id AND is_deleted = 0), 0
                    ) AS rating,
                    IFNULL (
                        (SELECT count(pk_comment_id) AS comments FROM tb_comment c WHERE fk_route_id = pk_route_id AND is_deleted = 0), 0
                    ) AS comments,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = tb_route.pk_route_id AND action = 'download'), 0
                    ) AS downloads,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = tb_route.pk_route_id AND action = 'fork'), 0
                    ) AS forks,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = tb_route.pk_route_id AND action = 'share'), 0
                    ) AS shares
                FROM tb_route
                WHERE created_by = :userId
                AND is_deleted = 0
                ORDER BY datetime_created DESC";
        $params = array(
            ':userId' => $userId
        );

        $routes = parent::fetchAll($sql, $params);
        if (!$withPoints) {
            return $routes;
        }

        foreach ($routes as &$route) {
            $route->points = RouteFactory::getRoutePoints($route->routeId);
        }
        return $routes;
    }

    /**
     * Get a specific route for a user
     *
     * @author Craig Knott
     *
     * @param int $routeId The id of the route to get
     *
     * @return object The route object
     */
    public static function getRoute($routeId) {
        $sql = "SELECT
                    name,
                    description,
                    is_private
                FROM tb_route
                WHERE pk_route_id = :routeId
                AND is_deleted = 0";
        $params = array(
            ':routeId' => $routeId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     * Get a specific route for display on the route detail page
     *
     * @author Craig Knott
     *
     * @param int $routeId The id of the route to get
     *
     * @return object The route object
     */
    public static function getRouteForDetailPage($routeId) {
        $sql = "SELECT
                    r.name,
                    r.description,
                    r.is_private,
                    r.pk_route_id AS routeId,
                    IFNULL(
                        (SELECT FLOOR(avg(value) * 2) / 2  FROM tb_rating WHERE fk_route_id = :routeId AND is_deleted = 0), 0
                    ) AS rating,
                    IFNULL (
                        (SELECT count(pk_comment_id) AS comments FROM tb_comment c WHERE fk_route_id = :routeId AND is_deleted = 0), 0
                    ) AS comments,
                    IFNULL (
                        (SELECT count(pk_rating_id) AS comments FROM tb_rating r WHERE fk_route_id = :routeId AND is_deleted = 0), 0
                    ) AS ratings,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = :routeId AND action = 'download'), 0
                    ) AS downloads,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = :routeId AND action = 'fork'), 0
                    ) AS forks,
                    IFNULL (
                        (SELECT count(pk_route_log_id) FROM tb_route_log WHERE fk_route_id = :routeId AND action = 'share'), 0
                    ) AS shares,
                    u.username AS owner,
                    u.pk_user_id AS owner_id
                FROM tb_route r
                JOIN tb_user u
                ON r.created_by = u.pk_user_id
                WHERE pk_route_id = :routeId
                AND r.is_deleted = 0
                AND r.is_private = 0";
        $params = array(
            ':routeId' => $routeId
        );
        $result = parent::fetchOne($sql, $params);
        return $result;
    }


    /**
     * Get all points for a specified route
     *
     * @author Craig Knott
     *
     * @param int  $routeId The Id of the route to get the points for
     * @param bool $forJson Whether this is in the Json format (short hand names for latitude and longitude)
     *
     * @return array Array of all points for the given route
     */
    public static function getRoutePoints($routeId, $forJson = false) {
        $sql = "SELECT
                    name,
                    description,
                    latitude" . ($forJson ? (" as lat") : "") . ",
                    longitude" . ($forJson ? (" as lng") : "") . "
                FROM tb_point
                WHERE fk_route_id = :routeId";
        $params = array(
            ':routeId' => $routeId
        );
        return parent::fetchAll($sql, $params);
    }


    /**
     * Update a route's details
     *
     * @author Craig Knott
     *
     * @param int    $routeId     The route's ID
     * @param string $name        The route's new name
     * @param string $description The route's new description
     * @param int    $isPrivate   The route's new privacy setting
     *
     * @return void
     */
    public static function updateRoute($routeId, $name, $description, $isPrivate) {
        $sql = "UPDATE tb_route
                SET name = :name,
                    description = :description,
                    is_private = :isPrivate,
                    cost = 0,
                    distance = 0,
                    datetime_updated = NOW()
                WHERE pk_route_id = :routeId";
        $params = array(
            ':routeId'     => $routeId,
            ':name'        => $name,
            ':description' => $description,
            ':isPrivate'   => $isPrivate
        );
        parent::execute($sql, $params);
    }

    /**
     * Get the highest Id of any point for a given route
     *
     * @author Craig Knott
     *
     * @param int $routeId The route in question
     *
     * @return int The Id of the highest point for the specified route
     */
    public static function getHighestPointId($routeId) {
        $sql = "SELECT
                    max(pk_point_id) AS id
                FROM tb_point
                WHERE fk_route_id = :routeId";
        $params = array(
            ':routeId' => $routeId
        );
        return parent::fetchOne($sql, $params)->id;
    }

    /**
     * Removes all points from a specified route (so new ones can be added)
     *
     * @author Craig Knott
     *
     * @param int $highestIdForRoute The highest Id of any point in this route
     * @param int $routeId           The route in question
     *
     * @return void
     */
    public static function removeOldPoints($highestIdForRoute, $routeId) {
        $sql = "DELETE FROM tb_point
                WHERE pk_point_id <= :highestId
                AND fk_route_id = :routeId";
        $params = array(
            ':highestId' => $highestIdForRoute,
            ':routeId'   => $routeId
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets the first point in a given route
     *
     * @author Craig Knott
     *
     * @param int $routeId The route to look at
     *
     * @return object(lat, lng) An object with the latitude and longitude of the first point in the route
     */
    public static function getFirstRoutePoint($routeId) {
        $points = RouteFactory::getRoutePoints($routeId);
        return (object)array(
            'lat' => $points[0]->latitude,
            'lng' => $points[0]->longitude
        );
    }

    /**
     * Deletes a route (user id is necessary to prevent malicious actions)
     *
     * @author Craig Knott
     *
     * @param int $routeId The route to delete
     * @param int $userId  The user who this route belongs to (use 0 to override check)
     *
     * @return void
     */
    public static function deleteRoute($routeId, $userId) {
        $sql = "UPDATE tb_route
                SET is_deleted = 1,
                    datetime_updated = NOW()
                WHERE pk_route_id = :routeId
                AND (created_by = :userId OR :userId = 0)";
        $params = array(
            ':routeId' => $routeId,
            ':userId'  => $userId
        );
        parent::execute($sql, $params);
    }

    /**
     * Creates a copy of a route, from a route Id. Copies the route to the account of the user Id provided
     *
     * @author Craig Knott
     *
     * @param int $oldRouteId Id of the route to copy
     * @param int $userId     Id of the user to copy this route to
     *
     * @return int The newly create route's Id
     */
    public static function forkRoute($oldRouteId, $userId) {
        // Copy route first
        $sql = "INSERT INTO tb_route (
                    created_by,
                    name,
                    description,
                    is_private,
                    cost,
                    distance,
                    is_deleted,
                    datetime_created,
                    datetime_updated
                )
                SELECT
                    :userId,
                    name,
                    description,
                    0,
                    cost,
                    distance,
                    0,
                    NOW(),
                    NOW()
                FROM tb_route
                WHERE pk_route_id = :routeId";
        $params = array(
            ':routeId' => $oldRouteId,
            ':userId'  => $userId
        );

        $routeId = parent::execute($sql, $params, true);

        // Copy points second
        $sql = "INSERT INTO tb_point (
                    fk_route_id,
                    name,
                    description,
                    latitude,
                    longitude
                )
                SELECT
                    :routeId,
                    name,
                    description,
                    latitude,
                    longitude
                FROM tb_point
                WHERE fk_route_id = :oldRouteId;";
        $params = array(
            ':routeId'    => $routeId,
            ':oldRouteId' => $oldRouteId
        );
        parent::execute($sql, $params);

        return $routeId;
    }

    /**
     * Determines whether a rating for the given user/route combination already exists.
     *
     * @author Craig Knott
     *
     * @param int $routeId Id of the route the rating was given to
     * @param int $userId  Id of the user performing the action
     *
     * @return bool Whether a rating for this user/route combination exists
     */
    public static function checkIfRatingExists($routeId, $userId) {
        $sql = "SELECT
                    pk_route_log_id
                FROM tb_route_log
                WHERE action = 'rate'
                AND fk_user_id = :userId
                AND fk_route_id = :routeId";
        $params = array(
            ':routeId' => $routeId,
            ':userId'  => $userId
        );

        $exists = parent::fetchOne($sql, $params);
        return ($exists !== false);
    }

    /**
     * Logs a row in tb_route_log whenever an action is taken on a route. This is so we can display them all in the
     * social stream
     *
     * @author Craig Knott
     *
     * @param int    $routeId       Id of the route the action was performed on
     * @param int    $userId        Id of the user performing the action - if 0, it is a user not logged in
     * @param string $action        The action performed ("rate", "comment", "fork", "download", or "share")
     * @param int    $action_id     If rating or commenting, the Id of the rating or comment
     * @param string $action_string If sharing a route, what website it was shared to
     */
    public static function updateRouteLog($routeId, $userId, $action, $action_id = null, $action_string = null) {
        // Check this user hasn't already left a rating for this route. If they have, we update that entry instead of
        // adding another.
        if ($action === 'rate') {
            $exists = RouteFactory::checkIfRatingExists($routeId, $userId);
            if ($exists) {
                $sql = "UPDATE tb_route_log
                        SET datetime = NOW()
                        WHERE fk_route_id = :routeId
                        AND fk_user_id = :userId
                        AND action = 'rate'";
                $params = array(
                    ':routeId' => $routeId,
                    ':userId'  => $userId
                );
                parent::execute($sql, $params);
                return;
            }
        }

        $sql = "INSERT INTO tb_route_log (
                    fk_route_id,
                    fk_user_id,
                    action,
                    action_value_id,
                    action_value_string,
                    datetime
                ) VALUES (
                    :routeId,
                    :userId,
                    :action,
                    :action_id,
                    :action_string,
                    NOW()
                )";
        $params = array(
            ':routeId'       => $routeId,
            ':userId'        => $userId,
            ':action'        => $action,
            ':action_id'     => $action_id,
            ':action_string' => $action_string
        );
        parent::execute($sql, $params);
    }

    /**
     * Returns the entire social stream for a route. Including shares, routes and comments
     *
     * @author Craig Knott
     *
     * @param int $routeId Id of the route to get the stream from
     * @param int $viewer  User Id of the person looking at the page (to deal with shadow bans)
     *
     * @return array All social interactions with this route
     */
    public static function getSocialStream($routeId, $viewer) {
        $sql = "SELECT
                    rl.fk_route_id,
                    rl.action AS type,
                    rl.action_value_id AS valueId,
                    rl.action_value_string AS valueString,
                    u.username,
                    c.comment,
                    r.value AS rating,
                    CASE
                        WHEN rl.action='comment' THEN 'fa fa-comment'
                        WHEN rl.action='rate' THEN 'fa fa-star'
                        WHEN rl.action='download' THEN 'fa fa-download'
                        WHEN rl.action='share' THEN 'fa fa-share'
                        WHEN rl.action='fork' THEN 'fa fa-code-fork'
                    END AS icon
                FROM tb_route_log rl
                JOIN tb_user u
                ON u.pk_user_id = rl.fk_user_id
                LEFT JOIN tb_comment c
                ON c.pk_comment_id = rl.action_value_id
                LEFT JOIN tb_rating r
                ON r.pk_rating_id = rl.action_value_id
                WHERE rl.fk_route_id = :routeId
                AND (u.is_shadow_banned = 0 OR u.pk_user_id = :viewer)
                AND u.is_banned = 0
                AND (c.is_deleted = 0 OR c.is_deleted IS NULL OR (c.is_deleted = 1 AND rl.action = 'rate'))
                AND (r.is_deleted = 0 OR r.is_deleted IS NULL OR (r.is_deleted = 1 AND rl.action = 'comment'))
                ORDER BY datetime DESC";
        $params = array(
            ':routeId' => $routeId,
            ':viewer'  => $viewer
        );
        return parent::fetchAll($sql, $params);
    }

    /**
     * Returns the rating that a particular user gave for a particular route
     *
     * @author Craig Knott
     *
     * @param int $userId  The user to look for
     * @param int $routeId The route to look for
     *
     * @return array The rating given to this route from this user
     */
    public static function getUserRatingForRoute($userId, $routeId) {
        $sql = "SELECT
                    pk_rating_id AS id,
                    value
                FROM tb_rating
                WHERE fk_route_id = :routeId
                AND created_by = :userId";
        $params = array(
            ':userId'  => $userId,
            ':routeId' => $routeId
        );

        $result = parent::fetchOne($sql, $params);
        if ($result === false) {
            return (object)array(
                'ratingId' => 0,
                'value'    => 0
            );
        }

        return (object)array(
            'ratingId' => $result->id,
            'value'    => $result->value
        );
    }

    /**
     * Returns the name of a route, from it's ID
     *
     * @author Craig Knott
     *
     * @param int $routeId The id of a route
     *
     * @return string The name of the route
     */
    public static function getRouteName($routeId) {
        $sql = "SELECT
                    name
                FROM tb_route
                WHERE pk_route_id = :routeId";
        $params = array(
            ':routeId' => $routeId
        );
        return parent::fetchOne($sql, $params)->name;
    }

    public static function getNearbyRoutes($lat, $long) {
      // Currently gets the start and end points for all routes
      // Need to compare them to the lat and long passed in
         $sql = "select
                   start.latitude,
                   start.longitude,
                   end.latitude,
                   end.longitude,
                   pk_route_id,
                   1 + 1 as some_calculation
                 from tb_route r
                 join (
                   select latitude, longitude, fk_route_id
                   from tb_point group by fk_route_id
                 ) as start
                 on start.fk_route_id = r.pk_route_id
                 join (
                   select latitude, longitude, fk_route_id
                   from (
                     select latitude, longitude, fk_route_id
                     from tb_point
                     order by pk_point_id desc
                   ) as inner_select group by inner_select.fk_route_id
                 ) as end
                 on end.fk_route_id = r.pk_route_id
                 where r.is_private = 0";
         $params = array (

        );



        // Get first point for a route
        // select latitude, longitude from tb_point group by fk_route_id;

        // Get last pooint for a route
        // select latitude, longitude from (select latitude, longitude, fk_route_id from tb_point order by pk_point_id desc) as inner_select group by inner_select.fk_route_id;



        return parent::fetchAll($sql, $params);
    }
}
// SELECT id, ( 3959 * acos( cos( radians(37) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(-122) ) + sin( radians(37) ) * sin( radians( lat ) ) ) ) AS distance FROM markers HAVING distance < 25 ORDER BY distance LIMIT
