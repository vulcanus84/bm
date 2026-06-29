<?php
include 'inc/php/db.php';

$db = new db();

$data = json_decode(file_get_contents("php://input"), true);

$action = $data['action'] ?? null;
$zgb = $data['zgb_id'] ?? null;

switch ($data['type']) {
    case 'load':
        $data['type'] = 9;
        break;
    case 'sort':
        $data['type'] = 8;
        break;
    case 'drive':
        $data['type'] = getNextPosition();
        break;
    case 'delivery':
        $data['type'] = 10;
        break;
    default:
        exit;
}

if ($action === "start") {

        $stmt = $db->connection->prepare("
        INSERT INTO quickmail_log (log_kw_id, log_zgb_id, log_logcat_id, log_chart_id, log_start_time, log_start_km)
        VALUES (:kw, :zgb, :cat, :chartId, NOW(), :km)
    ");

    $stmt->execute([
        ':kw' => $data['kw_id'],
        ':zgb' => $data['zgb_id'],
        ':cat' => $data['type'],
        ':chartId' => $data['chart_id'],
        ':km' => $data['km']
    ]);
}

if ($action === "stop") {

    $stmt = $db->connection->prepare("
        UPDATE quickmail_log
        SET log_end_time = NOW(), log_end_km = :km
        WHERE log_zgb_id = :zgb
          AND log_end_time IS NULL
        ORDER BY log_start_time DESC
        LIMIT 1
    ");

    $stmt->execute([
        ':zgb' => $data['zgb_id'],
        ':km' => $data['km']
    ]);

    if (in_array($data['type'], [2,5,7])) {
        $stmt = $db->connection->prepare("
            SELECT log_start_time, log_end_time
            FROM quickmail_log
            WHERE log_zgb_id = :zgb
            AND log_logcat_id IN(2,5,7)
            AND log_end_time IS NOT NULL
        ");

        $stmt->execute([
            ':zgb' => $data['zgb_id']
        ]);
    } else {
        $stmt = $db->connection->prepare("
            SELECT log_start_time, log_end_time
            FROM quickmail_log
            WHERE log_zgb_id = :zgb
            AND log_logcat_id = :type
            AND log_end_time IS NOT NULL
        ");

        $stmt->execute([
            ':zgb' => $data['zgb_id'],
            ':type' => $data['type']
        ]);
    }


    $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

    $totalSeconds = 0;

    foreach ($logs as $log) {

        $start = strtotime($log->log_start_time);
        $end   = strtotime($log->log_end_time);

        if ($start && $end) {
            $totalSeconds += ($end - $start);
        }
    }

    $fieldMap = [
        9  => 'zgb_load_time_real',
        8  => 'zgb_sort_time_real',
        2  => 'zgb_drive_time_real',
        5  => 'zgb_drive_time_real',
        7  => 'zgb_drive_time_real',
        10 => 'zgb_delivery_time_real'
    ];

    $field = $fieldMap[$data['type']] ?? null;

    if (!$field) {
        exit;
    }

    $stmt = $db->connection->prepare("
        UPDATE quickmail_zgbs
        SET $field = :value
        WHERE zgb_id = :zgb
    ");

    $stmt->execute([
        ':value' => $totalSeconds,
        ':zgb'   => $data['zgb_id']
    ]);
}

function getNextPosition() {
    global $db;
    global $data;

    $stmt = $db->connection->prepare("
        SELECT *
        FROM quickmail_log
        WHERE log_kw_id = :kw
        ORDER BY log_created_at DESC
    ");

    $stmt->execute([
        ':kw' => $data['kw_id']
    ]);

    $logs = $stmt->fetchAll(PDO::FETCH_OBJ);

    foreach($logs as $log) {
        if($log->log_logcat_id == 2) { 
            if($log->log_zgb_id != $data['zgb_id']) {
                return 5;
            } else { return 7; }
        }

        if($log->log_logcat_id == 5) { 
            if($log->log_zgb_id != $data['zgb_id']) {
                return 5;
            } else { return 7; }
        }

        if($log->log_logcat_id == 7) { return 2; } 
    }

    return 2;

}