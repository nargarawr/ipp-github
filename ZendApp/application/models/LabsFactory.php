<?

class LabsFactory extends ModelFactory {

    /**
     * Get the primary key for a given table
     *
     * @author Craig Knott
     *
     * @param string $tableName The name of the table in the database
     *
     * @return string Name of the primary given of a table
     */
    public static function getTablePrimaryKey($tableName) {
        $sql = "SHOW KEYS
                FROM " . $tableName . "
                WHERE Key_name = 'PRIMARY'";
        $result = parent::fetchOne($sql);
        return $result->Column_name;
    }

    /**
     * Get the value of the highest primary key for a given table
     *
     * @author Craig Knott
     *
     * @param string $tableName The table to look in
     * @param string $pk        The primary key to look for
     *
     * @return int The maximum value of the given primary key
     */
    public static function getHighestPrimaryKeyValue($tableName, $pk) {
        $sql = "SELECT MAX(" . $pk . ") AS max
                FROM " . $tableName;
        $result = parent::fetchOne($sql);
        return $result->max;
    }

    /**
     * Returns all results in a table with a primary key id of higher than a specified amount
     *
     * @author Craig Knott
     *
     * @param string $tableName The table to look in
     * @param string $pk        The name of the primary key
     * @param int    $highestId The id to check against
     *
     * @return array(row) Array of rows that have a higher id than the threshold
     */
    public static function getAllResultsAboveId($tableName, $pk, $highestId) {
        $sql = "SELECT *
                FROM " . $tableName . "
                WHERE " . $pk . " > " . $highestId;
        return parent::fetchAll($sql);
    }

    /**
     * Get the name of the table featured in the SQL
     *
     * @author Craig Knott
     *
     * @param string $sql The SQL to check in
     *
     * @return string Table name
     *       | null
     */
    public static function getTableNameFromQuery($sql) {
        $tokens = explode(' ', $sql);
        foreach ($tokens as $token) {
            if (preg_match("/tb_/", $token) == 1) {
                return $token;
                break;
            }
        }
        return null;
    }

    /**
     * Gets the rows modified by an update query
     *
     * @author Craig Knott
     *
     * @param string $sql The SQL that would be run
     *
     * @return array(row) Array of rows modified by the update
     */
    public static function getResultSetAfterUpdate($sql) {
        $parts = explode('where', $sql);
        $whereClause = $parts[1];
        $tableName = self::getTableNameFromQuery($parts[0]);
        $returnSql = "SELECT * from " . $tableName . " where " . $whereClause;
        return parent::fetchAll($returnSql);
    }

    /**
     * Check if the provided SQL matches the method given
     *
     * @author Craig Knott
     *
     * @param string $sql    SQL to check
     * @param string $method The type of SQL this should be
     *
     * @return array(valid, message) Array with results of the check
     */
    public static function checkSqlIsValidForQueryType($sql, $method) {
        $sql = strtolower($sql);
        $response = array(
            'isValid' => true,
            'message' => 'Valid',
            'results' => null
        );

        // Don't allow any kind of deletion
        if (preg_match("/drop|truncate|delete/", $sql)) {
            $response['isValid'] = false;
            $response['message'] = 'Attempts to delete are forbidden';
        } else if ($method == 'fetch' && !(preg_match('/select/', $sql))) {
            // Fetch must have a select
            $response['isValid'] = false;
            $response['message'] = 'Fetch query does not contain a select';
        } else if ($method == 'update' && !(preg_match('/update/', $sql))) {
            // Update must have an update
            $response['isValid'] = false;
            $response['message'] = 'Update query does not contain an update';
        } else if ($method == 'insert' && !(preg_match('/insert/', $sql))) {
            // Insert must have an insert
            $response['isValid'] = false;
            $response['message'] = 'Insert query does not contain an insert';
        }

        return $response;
    }

    /**
     * Run a SQL query
     *
     * @author Craig Knott
     *
     * @param string $sql    The SQL to run
     * @param string $method The type of SQL this is
     *
     * @return array(rows) The result set of the query (or the updated rows)
     */
    public static function sql_runSql($sql, $method) {
        $response = self::checkSqlIsValidForQueryType($sql, $method);

        if ($response['isValid']) {
            if ($method == 'fetch') {
                $response['results'] = parent::fetchAll($sql);
            } else if ($method == 'update') {
                parent::execute($sql);
                $response['results'] = self::getResultSetAfterUpdate($sql);
            } else if ($method == 'insert') {
                $tableName = self::getTableNameFromQuery($sql);
                $pk = self::getTablePrimaryKey($tableName);
                $highestId = self::getHighestPrimaryKeyValue($tableName, $pk);
                parent::execute($sql);
                $response['results'] = self::getAllResultsAboveId($tableName, $pk, $highestId);
            } else {
                $response['isValid'] = false;
                $response['message'] = "Invalid query type specified";
            }
        }

        return $response;
    }
}
