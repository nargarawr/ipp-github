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
            ':userId' => $userId,
            ':name' => $name,
            ':description' => $description,
            ':isPrivate' => $isPrivate
        );
        $routeId = parent::execute($sql, $params, true);

        // Insert route points now
        // for each point, RouteFactory::createRoutePoint($point, $routeId);

        return $routeId;
    }

    public static function createRoutePoint($point, $routeId) {

    }

    public static function getRoutesForUser($userId) {
        $sql = "SELECT
                    pk_route_id as routeId,
                    name,
                    description,
                    is_private,
                    cost,
                    distance,
                    datetime_created
                FROM tb_route
                WHERE created_by = :userId
                ORDER BY datetime_created desc";
        $params = array(
            ':userId' => $userId
        );
        return parent::fetchAll($sql, $params);
    }

}