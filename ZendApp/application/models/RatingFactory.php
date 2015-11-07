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
     * Adds a rating to the given route
     *
     * @author Craig Knott
     *
     * @param int   $routeId The Id of the route being rating
     * @param float $rating  The rating being given (rounded to a multiple of 0.5, between 1 and 5)
     *
     * @return int The rating Id
     */
    public static function addRating($routeId, $rating) {

        return 1;
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
        return 1;
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

    }

    /**
     * "Clears" a rating from a route, as if the user never rated it
     *
     * @author Craig Knott
     *
     * @param int $ratingId The Id of rating to clear
     */
    public static function removeRating($ratingId) {

    }

    /**
     * Changes the rating a user left for a route
     *
     * @author Craig Knott
     *
     * @param int   $ratingId The rating to update
     * @param float $newValue The new rating value (rounded to a multiple of 0.5, between 1 and 5)
     */
    public static function updateRating($ratingId, $newValue) {

    }

    /**
     * Gets all ratings that the user has given out, for statistics
     *
     * @author Craig Knott
     *
     * @param int $userId Id of the user to get the data for
     *
     * @return array(int, int) Routed Ids and ratings from this user
     */
    public static function getAllRatingsFromUser($userId) {

    }

}


