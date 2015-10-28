<?

class RouteFactory extends ModelFactory {

    public static function createRoute($name, $description, $isPrivate, $points, $userId) {
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
        $routeId = parent::execute($sql, $params, true);

        foreach ($points as $point) {
            RouteFactory::createRoutePoint((object)$point, $routeId);
        }

        return $routeId;
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
                    datetime_created,
                    (select count(1) from tb_point where fk_route_id = pk_route_id) AS num_points
                FROM tb_route
                WHERE created_by = :userId
                ORDER BY datetime_created DESC";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

}