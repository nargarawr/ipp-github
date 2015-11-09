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
                        (SELECT FLOOR(avg(value) * 2) / 2  FROM tb_rating WHERE fk_route_id = tb_route.pk_route_id), 0
                    ) AS rating,
                    IFNULL (
                        (SELECT count(pk_comment_id) AS comments FROM tb_comment c WHERE fk_route_id = pk_route_id AND is_deleted = 0), 0
                    ) AS comments
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
     * @param int $userId  The id of the user this belongs to (to avoid unwarranted access)
     *
     * @return object The route object
     */
    public
    static function getRoute($routeId, $userId) {
        $sql = "SELECT
                    name,
                    description,
                    is_private
                FROM tb_route
                WHERE pk_route_id = :routeId
                AND is_deleted = 0
                AND created_by = :userId";
        $params = array(
            ':routeId' => $routeId,
            ':userId'  => $userId
        );
        return parent::fetchOne($sql, $params);
    }

    /**
     *Get all points for a specified route
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
     * @param int $userId  The user who this route belongs to
     *
     * @return void
     */
    public static function deleteRoute($routeId, $userId) {
        $sql = "UPDATE tb_route
                SET is_deleted = 1,
                    datetime_updated = NOW()
                WHERE pk_route_id = :routeId
                AND created_by = :userId";
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

}
