<?

class ModelFactory {

    /**
     * Connect to the database and return the connection
     *
     * @author Craig Knott
     *
     * @return Zend_Db_Adapter_Pdo_Mysql PDO Database connection
     */
    public static function getDb() {
        $host = 'craigknott.com';
        $username = 'root';
        $password = 'xD1NCzMlZv';
        $dbName = 'cxk';
        if (isDevelopment()) {
            $host = 'localhost';
            $password = '';
        }

        $db = new Zend_Db_Adapter_Pdo_Mysql(array(
            'host'     => $host,
            'username' => $username,
            'password' => $password,
            'dbname'   => $dbName
        ));
        return $db;
    }

    /**
     * Get the cache key for a given database call (based on the calling function, and it's parameters)
     *
     * @param array $thisFunction debug_backtrace()[0]
     * @param array $lastFunction debug_backtrace()[1]
     *
     * @return string Unique identified for this database call
     */
    public static function getCacheKey($thisFunction, $lastFunction) {
        $params = '';
        foreach ($thisFunction['args'][1] as $param) {
            $params .= $param . ',';
        }
        $params = rtrim($params, ',');
        return $lastFunction['class'] . '::' . $lastFunction['function'] . '(' . $params . ')';
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
     * Execute a SQL query (update, insert, delete, etc)
     *
     * @author Craig Knott
     *
     * @param string       $sql    The SQL to run
     * @param array(param) $params Array of parameters to the query (':param' => val)
     *
     * @return void
     */
    public static function execute($sql, $params = null) {
        $stmt = new Zend_Db_Statement_Pdo(self::getDb(), $sql);
        $stmt->execute($params);
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
            return (object) $result;
        }
    }
}