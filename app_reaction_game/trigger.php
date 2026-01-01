<?php
define("level","../");
require_once(level."inc/standard_includes.php");

// Dauer in Sekunden (Float)
$duration = isset($_GET['duration']) ? floatval($_GET['duration']) : 0.0;
$user_id = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
$pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
$session_id = isset($_GET['sessionId']) ? $_GET['sessionId'] : 0;

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
    "repl_duration" => $duration,
    "repl_session_id" => $session_id
], "reaction_exercises_positions_live");

$data = ["rec_last_update" => date("Y-m-d H:i:s")];
if(isset($_GET['distance'])) { $data["rec_distance"] = $_GET['distance']; } else { $data["rec_distance"] = null; }
if(isset($_GET['gameStatus'])) { $data["rec_status"] = $_GET['gameStatus']; }

if (isset($_GET['nextPos']) && $_GET['nextPos'] != 0) {
    $data["rec_expected_pos_id"] = $_GET['nextPos'];
    $db->sql_query("SELECT * FROM reaction_exercises_positions WHERE rep_id=:posId", ['posId'=>$_GET['nextPos']]);
    if($db->count() == 0) { $data["rec_expected_pos_id"] = null; }
} else {
    $data["rec_expected_pos_id"] = null;
}

$db->update(
    $data,
    "reaction_exercises_cubes",
    "rec_mac",
    $_GET['mac']
);

echo "OK";
