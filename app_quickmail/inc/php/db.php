<?php
class db
{
    public $last_inserted_id = null;
    public $connected_host = null;
    public $connected_db = null;

    public $connection = null;

    public function __construct()
    {
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
    }

}
