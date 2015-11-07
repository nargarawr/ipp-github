<?

/**
 * Class CommentFactory
 *
 * Manages all comments, and their interaction with the database
 *
 * @author Craig Knott
 *
 */
class CommentFactory extends ModelFactory {

    /**
     * Adds a new comment to a route
     *
     * @author Craig Knott
     *
     * @param int    $routeId  The id of the route to add the comment to
     * @param string $text     The text of the comment
     * @param int    $postedBy The Id of the user who posted the comment
     *
     * @return int The Id of the comment
     */
    public static function addComment($routeId, $text, $postedBy) {
        $sql = "INSERT INTO tb_comment (
                    fk_route_id,
                    created_by,
                    comment
                ) VALUES (
                    :routeId,
                    :postedBy,
                    :text
                )";
        $params = array(
            ':routeId'  => $routeId,
            ':postedBy' => $postedBy,
            ':text'     => $text,
        );

        $commentId = parent::execute($sql, $params, true);
        return $commentId;
    }

    /**
     * Updates the content of a comment
     *
     * @author Craig Knott
     *
     * @param int    $commentId The id of the comment to edit
     * @param string $newText   The comment's new text
     */
    public static function updateComment($commentId, $newText) {
        $sql = "UPDATE tb_comment
                SET comment = :newText
                WHERE pk_comment_id = :commentId";
        $params = array(
            ':commentId' => $commentId,
            ':newText'   => $newText
        );
        parent::execute($sql, $params);
    }

    /**
     * Removes a specific comment
     *
     * @author Craig Knott
     *
     * @param int $commentId The id of the comment to remove
     */
    public static function deleteComment($commentId) {
        $sql = "UPDATE tb_comment
                SET is_deleted = 1
                WHERE pk_comment_id = :commentId";
        $params = array(
            ':commentId' => $commentId
        );
        parent::execute($sql, $params);
    }

    /**
     * Gets all comments made on a given route
     *
     * @author Craig Knott
     *
     * @param int $routeId Id of the route to get the comments for
     *
     * @return array(comments) Array of comments for this route
     */
    public static function getCommentsForRoute($routeId) {
        $sql = "SELECT
                    c.pk_comment_id AS id,
                    c.comment,
                    u.pk_user_id AS userId,
                    u.username,
                    u.fname,
                    u.lname
                FROM tb_comment c
                JOIN tb_user u
                ON c.created_by = u.pk_user_id
                WHERE fk_route_id = :routeId
                AND is_deleted = 0";
        $params = array(
            ':routeId' => $routeId
        );
        $results = parent::fetchAll($sql, $params);
        return $results;
    }

    /**
     * Gets all comments made by a given user
     *
     * @author Craig Knott
     *
     * @param int     $userId Id of the user to get the comments for
     * @param boolean $count  Whether or not to return just the number of comments
     *
     * @return array(comments) | int, Array of comments for this user or single int representing the count
     */
    public static function getCommentsFromUser($userId, $count) {
        $sql = "SELECT
                    c.pk_comment_id AS commentId,
                    c.comment,
                    r.name AS routeName,
                    u.fname AS route_creator_fname,
                    u.lname AS route_creator_lname,
                    u.username AS route_creator_uname
                FROM tb_comment c
                JOIN tb_route r
                ON c.fk_route_id = r.pk_route_id
                JOIN tb_user u
                ON r.created_by = u.pk_user_id
                WHERE c.created_by = :userId
                AND c.is_deleted = 0";
        $params = array(
            ':userId' => $userId
        );

        $results = parent::fetchAll($sql, $params);
        if ($count) {
            return count($results);
        } else {
            return $results;
        }
    }

    /**
     * Gets all comments made by other users, on routes for the specified user
     *
     * @author Craig Knott
     *
     * @param int     $userId Id of the user to get the comments for
     * @param boolean $count  Whether or not to return just the number of comments
     *
     * @return array(comments) | int, Array of comments for this user or single int representing the count
     */
    public static function getCommentsForUser($userId, $count) {
        $sql = "SELECT
                    c.pk_comment_id AS commentId,
                    c.comment,
                    r.name AS routeName,
                    u.fname AS route_creator_fname,
                    u.lname AS route_creator_lname,
                    u.username AS route_creator_uname
                FROM tb_comment c
                JOIN tb_route r
                ON c.fk_route_id = r.pk_route_id
                JOIN tb_user u
                ON r.created_by = u.pk_user_id
                WHERE r.created_by = :userId
                AND c.is_deleted = 0";
        $params = array(
            ':userId' => $userId
        );

        $results = parent::fetchAll($sql, $params);
        if ($count) {
            return count($results);
        } else {
            return $results;
        }
    }
}
