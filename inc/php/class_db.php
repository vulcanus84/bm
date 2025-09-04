<?php
//*****************************************************************************
// 26.03.2013 Claude Hübscher (angepasst mit Logging-Verbesserung)
//-----------------------------------------------------------------------------
// Diese Klasse kann für den Datenbankzugriff verwendet werden.
// Sie verwendet PDO und wirft Exceptions -> deshalb try/catch notwendig.
// Alle Queries werden mit Parametern interpoliert ins Log geschrieben.
//*****************************************************************************

class db
{
    public $last_inserted_id = null;
    public $connected_host = null;
    public $connected_db = null;

    private $connection = null;
    private $res = null;
    private $counter = null;
    private $logger;
    private static $instance = null;

    public function __construct()
    {
        error_log("Neue DB-Verbindung erstellt! Stack: " . json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)));
        $user = "huebsche_bm";
        $pw   = "badminton123$";
        $host = "localhost";
        $db   = "tournament";

        $this->connection = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8",
            $user,
            $pw,
            [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_PERSISTENT => false,          // Keine persistenten Verbindungen
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Fehler als Exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Standard-Fetch-Modus
            ]
        );


        $this->connected_host = $host;
        $this->connected_db   = $db;

        $this->logger = new log($this);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new db();
        }
        return self::$instance;
    }

    // ------------------------------------------------------------
    // Hilfsfunktion: Parameter in Query einsetzen (für Logging)
    // ------------------------------------------------------------
    private function interpolateQuery($query, array $params = [])
    {
        if (!$params) return $query;

        $keys = [];
        $values = [];

        foreach ($params as $key => $value) {
            $keys[] = is_string($key)
                ? '/:' . preg_quote(ltrim($key, ':'), '/') . '/'
                : '/[?]/';

            if (is_null($value)) {
                $values[] = 'NULL';
            } elseif (is_string($value) && strtoupper($value) === 'NULL') {
                // Falls jemand explizit "NULL" als String übergibt
                $values[] = 'NULL';
            } elseif (is_string($value)) {
                $values[] = "'" . addslashes($value) . "'";
            } elseif (is_bool($value)) {
                $values[] = $value ? '1' : '0';
            } else {
                $values[] = $value;
            }
        }

        return preg_replace($keys, $values, $query, 1);
    }


    // ------------------------------------------------------------
    // INSERT
    // ------------------------------------------------------------
    public function insert($arr_fields, $table)
    {
        try {
            $columns = [];
            $placeholders = [];

            foreach ($arr_fields as $k => $v) {
                $columns[] = $k;
                $placeholders[] = ':' . $k;
            }

            $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
            $STH = $this->connection->prepare($sql);
            $STH->execute($arr_fields);

            if ($table != 'log') {
                $this->last_inserted_id = $this->connection->lastInsertId();
                $this->logger->write_to_log('Database', $this->interpolateQuery($sql, $arr_fields));
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Insert-Error: " . $e->getMessage() . "<br><code>" .
                $this->interpolateQuery($sql, $arr_fields) . "</code>"
            );
        }
    }

    // ------------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------------
    public function update($arr_fields, $table, $id_column, $id)
    {
        try {
            $set = [];
            foreach ($arr_fields as $k => $v) {
                $set[] = "$k = :$k";
            }

            $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE $id_column = :my_id";
            $arr_fields['my_id'] = $id;

            $STH = $this->connection->prepare($sql);
            $STH->execute($arr_fields);

            if ($table != 'log') {
                $this->logger->write_to_log('Database', $this->interpolateQuery($sql, $arr_fields));
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Update-Error: " . $e->getMessage() . "<br><code>" .
                $this->interpolateQuery($sql, $arr_fields) . "</code>"
            );
        }
    }

    // ------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------
    public function delete($table, $id_column, $id)
    {
        try {
            $sql = "DELETE FROM $table WHERE $id_column = :my_id";
            $params = ['my_id' => $id];

            $STH = $this->connection->prepare($sql);
            $STH->execute($params);

            if ($table != 'log') {
                $this->logger->write_to_log('Database', $this->interpolateQuery($sql, $params));
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Delete-Error: " . $e->getMessage() . "<br><code>" .
                $this->interpolateQuery($sql, ['my_id' => $id]) . "</code>"
            );
        }
    }

    // ------------------------------------------------------------
    // GENERISCHE QUERY
    // ------------------------------------------------------------
    public function sql_query($sql, $parameters = null)
    {
        try {
            $this->res = $this->connection->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
            $this->res->execute(is_array($parameters) ? $parameters : null);

            if(strtoupper(substr($sql,0,6))!=="SELECT" && 
              strtoupper(substr($sql,0,4))!=="SHOW" &&
              strtoupper(substr($sql,0,18))!=="UPDATE TRANSLATION" &&
              strpos($sql," log ")===false
              ) { $this->logger->write_to_log('Database', $this->interpolateQuery($sql, $parameters ?: [])); }
        } catch (PDOException $e) {
            throw new Exception(
                "Query-Error: " . $e->getMessage() . "<br><code>" .
                $this->interpolateQuery($sql, $parameters ?: []) . "</code>"
            );
        }

        $this->counter = null;
        return $this->res;
    }

    // ------------------------------------------------------------
    // QUERY + FETCH EINZELNEN DATENSATZ
    // ------------------------------------------------------------
    public function sql_query_with_fetch($sql, $parameters = null)
    {
        try {
            $this->res = $this->sql_query($sql, $parameters);
            if ($this->res) {
                return $this->res->fetchObject();
            }
        } catch (PDOException $e) {
            throw new Exception(
                "Query-Error (with fetch): " . $e->getMessage() . "<br><code>" .
                $this->interpolateQuery($sql, $parameters ?: []) . "</code>"
            );
        }
    }

    // ------------------------------------------------------------
    // HELFER
    // ------------------------------------------------------------
    public function get_next_res()
    {
        if ($this->res) {
            return $this->res->fetchObject();
        }
    }

    public function count()
    {
        $this->counter = $this->res->rowCount();
        return $this->counter;
    }

    public function seek($item)
    {
        if ($this->res) {
            $this->res->fetch(PDO::FETCH_OBJ, PDO::FETCH_ORI_NEXT, $item);
        }
    }
}
?>
