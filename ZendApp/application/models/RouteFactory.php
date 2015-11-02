<?

class RouteFactory extends ModelFactory {

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

    public static function getRoutesForUser($userId) {
        $sql = "SELECT
                    pk_route_id AS routeId,
                    name,
                    description,
                    is_private,
                    cost,
                    distance,
                    datetime_created AS created,
                    (SELECT count(1) FROM tb_point WHERE fk_route_id = pk_route_id) AS num_points
                FROM tb_route
                WHERE created_by = :userId
                AND is_deleted = 0
                ORDER BY datetime_created DESC";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

    public static function getRoute($routeId, $userId) {
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

    public static function getFirstRoutePoint($routeId) {
        $points = RouteFactory::getRoutePoints($routeId);
        return (object)array(
            'lat' => $points[0]->latitude,
            'lng' => $points[0]->longitude
        );
    }

    // use user id to prevent delete other users stuff
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

}
