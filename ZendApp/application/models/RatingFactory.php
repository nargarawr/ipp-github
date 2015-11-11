<?

/**
 * Class RatingFactory
 *
 * Manages all ratings, and their interaction with the database. Ratings are a number given to a route to determine
 * how "good" it is. The number is between 1 and 5, and rounded to the nearest 0.5. This gives the routes a
 * star/half-star rating system
 *
 * @author Craig Knott
 *
 */
class RatingFactory extends ModelFactory {

    /**
     * Adds a rating to the given route. Due to the on duplicate key update, this can also be used to update ratings
     * - one method for everything!
     *
     * @author Craig Knott
     *
     * @param int   $routeId The Id of the route being rating
     * @param float $rating  The rating being given (rounded to a multiple of 0.5, between 1 and 5)
     * @param int   $ratedBy The user Id of the user giving this rating
     *
     * @return int The rating Id
     */
    public static function addRating($routeId, $rating, $ratedBy) {
        $sql = "INSERT INTO tb_rating (
                    fk_route_id,
                    created_by,
                    value,
                    is_deleted
                ) VALUES (
                    :routeId,
                    :ratedBy,
                    :rating,
                    0
                )
                ON DUPLICATE KEY
                UPDATE value = :rating,
                       is_deleted = 0;";
        $params = array(
            ':routeId' => $routeId,
            ':ratedBy' => $ratedBy,
            ':rating'  => $rating
        );

        $id = parent::execute($sql, $params, true);
        if ($id == 0 || is_null($id)) {
            $id = RatingFactory::getRatingId($routeId, $ratedBy);
        }
        return $id;
    }

    /**
     * Get the id of rating based on the unique routeId/userId combination. Necessary because the addRating function
     * will return a ratingId of 0 if the value is updated instead of added anew.
     *
     * @author Craig Knott
     *
     * @param int $routeId The route the rating was made on
     * @param int $ratedBy The user that made the rating
     *
     * @return int The id of this rating
     */
    public static function getRatingId($routeId, $ratedBy) {
        $sql = "SELECT
                    pk_rating_id as id
                FROM tb_rating
                WHERE fk_route_id = :routeId
                AND created_by = :ratedBy";
        $params = array(
            ':routeId' => $routeId,
            ':ratedBy' => $ratedBy
        );
        $result = parent::fetchOne($sql, $params);
        return $result->id;
    }

    /**
     * "Clears" a rating from a route, as if the user never rated it
     *
     * @author Craig Knott
     *
     * @param int $ratingId The Id of rating to clear
     */
    public static function removeRating($ratingId) {
        $sql = "UPDATE tb_rating
                SET is_deleted = 1
                WHERE pk_rating_id = :ratingId";
        $params = array(
            ':ratingId' => $ratingId
        );

        parent::execute($sql, $params);
    }

    /**
     * Gets average of all ratings for a route, giving the final rating value
     *
     * @author Craig Knott
     *
     * @param int $routeId The Id of the route we are getting the rating for
     *
     * @return float The average rating for the route (rounded to a multiple of 0.5, between 1 and 5)
     */
    public static function getAverageRatingForRoute($routeId) {
        $sql = "SELECT
                    FLOOR(avg(value) * 2) / 2 AS average
                FROM tb_rating
                WHERE fk_route_id = 1
                AND is_deleted = 0";
        $params = array(
            ':routeId' => $routeId
        );

        $rating = parent::fetchOne($sql, $params)->average;
        return $rating;
    }

    /**
     * Get the average rating for a user, which is average of every rating for every route they have
     *
     * @author Craig Knott
     *
     * @param int $userId The Id of the user we are getting the rating for
     *
     * @return float The average rating for the user (rounded to a multiple of 0.5, between 1 and 5)
     */
    public static function getAverageRatingForUser($userId) {
        $sql = "SELECT
                    FLOOR(avg(value) * 2) / 2 AS average
                FROM tb_rating rating
                JOIN tb_route route
                ON rating.fk_route_id = route.pk_route_id
                JOIN tb_user user
                ON route.created_by = user.pk_user_id
                WHERE user.pk_user_id = :userId
                AND rating.is_deleted = 0";
        $params = array(
            ':userId' => $userId
        );

        $rating = parent::fetchOne($sql, $params)->average;
        return $rating;
    }

    /**
     * Gets all ratings that the user has given out, for statistics
     *
     * @author Craig Knott
     *
     * @param int     $userId        Id of the user to get the data for
     * @param boolean $getNumberOnly Return the number of ratings instead of the ratings themselves
     *
     * @return array(int, int) | int, Route Ids and ratings from this user or the number of ratings
     */
    public static function getAllRatingsFromUser($userId, $getNumberOnly = false) {
        $sql = "SELECT
                    fk_route_id,
                    value
                FROM tb_rating
                WHERE created_by = :userId
                AND is_deleted = 0;";
        $params = array(
            ':userId' => $userId
        );

        $ratings = parent::fetchAll($sql, $params);
        if ($getNumberOnly) {
            return count($ratings);
        }
        return $ratings;
    }

    /**
     * Gets all ratings that the user has been given, for statistics
     *
     * @author Craig Knott
     *
     * @param int     $userId        Id of the user to get the data for
     * @param boolean $getNumberOnly Return the number of ratings instead of the ratings themselves
     *
     * @return array(int, int) | int, Route Ids and ratings from this user or the number of ratings
     */
    public static function getAllRatingsForUser($userId, $getNumberOnly = false) {
        $sql = "SELECT
                    fk_route_id,
                    value
                FROM tb_rating rating
                JOIN tb_route route
                ON rating.fk_route_id = route.pk_route_id
                WHERE rating.is_deleted = 0
                AND route.is_deleted = 0
                AND route.created_by = :userId";
        $params = array(
            ':userId' => $userId
        );

        $ratings = parent::fetchAll($sql, $params);
        if ($getNumberOnly) {
            return count($ratings);
        }
        return $ratings;
    }

}


