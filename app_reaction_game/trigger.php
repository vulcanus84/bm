<?php
define("level","../");
require_once(level."inc/standard_includes.php");

// Dauer in Sekunden (Float)
$duration = isset($_GET['duration']) ? floatval($_GET['duration']) : 0.0;
$user_id = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
$pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

// Jetzt mit Mikrosekunden holen
$now = microtime(true);

// Ereigniszeitpunkt = jetzt - duration
$eventTimestamp = $now - $duration;

// Zerlegen in Sekunden + Mikrosekunden
$sec  = floor($eventTimestamp);
$usec = sprintf("%06d", ($eventTimestamp - $sec) * 1_000_000);

// DATETIME(6) erzeugen
$eventDate = date("Y-m-d H:i:s", $sec) . "." . $usec;

// INSERT in die Tabelle
$db->insert([
    "repl_pos_id"  => $pos,
    "repl_user_id" => $user_id,
    "repl_ts"      => $eventDate,
    "repl_duration" => $duration
], "reaction_exercises_positions_live");

echo "OK";
