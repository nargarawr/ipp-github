<?

/**
 * Class ModelFactory
 *
 * Base class for all models, which provides access to the database and the abilities to make database calls
 *
 * @author Craig Knott
 *
 */
class ModelFactory {

    /**
     * Connect to the database and return the connection
     *
     * @author Craig Knott
     *
     * @return Zend_Db_Adapter_Pdo_Mysql PDO Database connection
     */
    public static function getDb() {
        $host = 'niceway.to';
        $username = 'root';
        $password = ModelFactory::getPassword();
        $dbName = 'nicewayto';

        $db = new Zend_Db_Adapter_Pdo_Mysql(array(
            'host'     => $host,
            'username' => $username,
            'password' => $password,
            'dbname'   => $dbName
        ));
        return $db;
    }

    /**
     * Returns the database password
     *
     * @author Craig Knott
     *
     * @return String The niceway.to database password
     */
    public static function getPassword() {
        return 'bC5sx5hLnV';
    }

    /**
     * Get all result rows from a query
     *
     * @author Craig Knott
     *
     * @param string       $sql    The SQL to run
     * @param array(param) $params Array of parameters to the query (':param' => val)
     *
     * @return array(row) Array of row object returned from the query
     */
    public static function fetchAll($sql, $params = null) {
        $stmt = new Zend_Db_Statement_Pdo(self::getDb(), $sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll();
        $results = array();
        foreach ($res as $r) {
            $results[] = (object)$r;
        }
        return $results;
    }

    /**
     * Get a single result row from a query
     *
     * @author Craig Knott
     *
     * @param string       $sql    The SQL to run
     * @param array(param) $params Array of parameters to the query (':param' => val)
     *
     * @return object Row object returned from the query
     */
    public static function fetchOne($sql, $params = null) {
        $stmt = new Zend_Db_Statement_Pdo(self::getDb(), $sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        if ($result === false) {
            return $result;
        } else {
            return (object)$result;
        }
    }

    /**
     * Executes a SQL query (update, insert, delete, etc)
     *
     * @author Craig Knott
     *
     * @param string       $sql      The SQL to run
     * @param array(param) $params   Array of parameters to the query (':param' => val)
     * @param bool         $returnId Whether to return the ID of the last inserted row
     *
     * @return int Id of the last row inserted (if returnId is true);
     */
    public static function execute($sql, $params = null, $returnId = false) {
        $db = self::getDb();
        $stmt = new Zend_Db_Statement_Pdo($db, $sql);
        $stmt->execute($params);

        if ($returnId) {
            return (int)$db->lastInsertId();
        }

        return null;
    }
}
