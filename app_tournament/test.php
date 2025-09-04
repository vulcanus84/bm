<?php
namespace Tournament;

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

require_once('inc/php/class_tournament.php');

// $myTournament = new tournament(348);
// $myTournament->calc->calc_ranking();
// print $myTournament->html->debug_out_class();

// DB-Zugangsdaten
        $user = "huebsche_bm";
        $pw   = "badminton123$";
        $host = "localhost";
        $db   = "tournament";

try {
    $PDO = new \PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pw, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]);

    echo "<h2>MySQL Connection Info</h2>";

    // Threads connected
    $stmt = $PDO->query("SHOW GLOBAL STATUS LIKE 'Threads_connected'");
    $threads = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo "Threads_connected: " . $threads['Value'] . "<br>";

    // Threads running
    $stmt = $PDO->query("SHOW GLOBAL STATUS LIKE 'Threads_running'");
    $threadsRunning = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo "Threads_running: " . $threadsRunning['Value'] . "<br>";

    // Max connections
    $stmt = $PDO->query("SHOW VARIABLES LIKE 'max_connections'");
    $maxConn = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo "Max connections: " . $maxConn['Value'] . "<br>";

    // Aktive Prozesse
    echo "<h3>Active Processes:</h3>";
    $stmt = $PDO->query("SHOW FULL PROCESSLIST");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th><th>Info</th></tr>";
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Id']}</td>";
        echo "<td>{$row['User']}</td>";
        echo "<td>{$row['Host']}</td>";
        echo "<td>{$row['db']}</td>";
        echo "<td>{$row['Command']}</td>";
        echo "<td>{$row['Time']}</td>";
        echo "<td>{$row['State']}</td>";
        echo "<td>{$row['Info']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
