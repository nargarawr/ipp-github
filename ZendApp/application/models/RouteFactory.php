<?

class RouteFactory extends ModelFactory {

    public static function createRoute($name, $description, $isPrivate, $userId) {
        $sql = "INSERT INTO tb_route (
                    created_by,
                    name,
                    description,
                    is_private,
                    cost,
                    distance
                ) VALUES (
                    :userId,
                    :name,
                    :description,
                    :isPrivate,
                    0,
                    0
                )";
        $params = array(
            ':userId' => $userId,
            ':name' => $name,
            ':description' => $description,
            ':isPrivate' => $isPrivate
        );
        $routeId = parent::execute($sql, $params, true);

        // Insert route points now

        return $routeId;
    }

    public static function getRoutesForUser($userId) {

    }

}