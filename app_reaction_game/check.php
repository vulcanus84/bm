<?php
$clientLast = isset($_GET['last']) ? floatval($_GET['last']) : 0;
$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
$excId = isset($_GET['excId']) ? intval($_GET['excId']) : 0;

$response = [
    "events" => [],
    "template" => [],
    "serverTimestamp" => $clientLast,
    "userId" => $userId
];

define("level","../");
require_once(level."inc/standard_includes.php"); // DB-Verbindung, User etc.

// --- Template laden ---
$positions = [];
$db->sql_query("SELECT * FROM reaction_exercises_positions WHERE rep_re_id = :exc_id ORDER BY rep_id ASC", ['exc_id'=>$excId]);
if($db->count() == 0) {
    // Kein Template gefunden
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
while($d = $db->get_next_res()) {
    $positions[] = intval($d->rep_id);
}

$response["template"] = $positions;

// --- Live-Daten laden ---
// Wir holen alle Positionen des Templates, nicht nur pos_id = 1
$db->sql_query("
    SELECT repl_pos_id AS pos_id,
           DATE_FORMAT(repl_ts, '%H:%i:%s') as timestamp,
           repl_duration, repl_session_id
    FROM (
        SELECT *
        FROM reaction_exercises_positions_live
        WHERE repl_pos_id IN (" . implode(',', $positions) . ")
          AND repl_user_id = :userId
        ORDER BY repl_ts DESC
        LIMIT 50
    ) AS sub
    ORDER BY repl_ts ASC
", ['userId' => $userId]);

while ($row = $db->get_next_res()) {
    $response["events"][] = [
        "pos_id"    => intval($row->pos_id),
        "timestamp" => $row->timestamp,
        "duration"  => floatval($row->repl_duration),
        "session_id" => $row->repl_session_id
    ];
}

// Server-Timestamp setzen
$triggerTime = file_exists("trigger.flag") ? filemtime("trigger.flag") : time();
$response["serverTimestamp"] = $triggerTime;

// JSON ausgeben
header('Content-Type: application/json');
echo json_encode($response);
