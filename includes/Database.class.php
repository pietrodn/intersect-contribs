<?php
/**
* This class encapsulates MySQL database object with a nice and minimal API.
* It also tries to re-use existing connections for the same host and database name.
*
* Instances of the Database class must be requested only using the static factory function
* Database::database($db_host, $db_name)
*/
class Database {
    static protected $instances = [];
    private $db = null;

    /**
    * Initializes a new instance.
    * Should not be called directly.
    * @param String $db_host Database host
    * @param String $db_name Name of the database
    */
    private function __construct($db_host, $db_name) {
        $this->db = new mysqli($db_host, DB_USER, DB_PASSWORD, $db_name) or die($this->db->error);
        $this->db->set_charset('utf8');
    }

    /**
    * Closes the database connection when no longer needed.
    */
    function __destruct() {
        $this->db->close();
    }

    /**
    * Static factory function to get new instances of the Database class.
    * It returns pre-existing instances for the same host and database name, when needed.
    * @param  String $db_host Database host
    * @param  String $db_name Name of the database
    * @return Database          A database object
    */
    public static function database($db_host, $db_name) {

        // The database is identified by host and DB name
        $id = $db_name . '@' . $db_host;

        if(!isset(self::$instances[$id])) {
            self::$instances[$id] = new Database($db_host, $db_name);
        }

        return self::$instances[$id];
    }

    /**
    * Executes a query and returns the results as an array of associative arrays.
    * @param  String $query The query in SQL
    * @return Array        The results
    */
    public function query($query) {
        // Extreme debugging
        // echo $query;
        $res = $this->db->query($query) or die($this->db->error);

        if($res === TRUE || $res === FALSE) {
            return $res;
        } else {
            $rows = Array();
            while($row = $res->fetch_assoc()) {
                $rows[]=$row;
            }
            $res->free();
            return $rows;
        }
    }

    public function escape($str) {
        return $this->db->real_escape_string($str);
    }
}
?>
