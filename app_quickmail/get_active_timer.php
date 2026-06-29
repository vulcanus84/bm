<?php

require_once 'inc/php/db.php';
$db = new db();


header('Content-Type: application/json');

$kw_id = $_GET['kw_id'] ?? 0;

$stmt = $db->connection->prepare("
    SELECT
        log_zgb_id,
        log_logcat_id,
        log_start_time,
        log_chart_id
    FROM quickmail_log
    WHERE log_kw_id = :kw_id
      AND log_end_time IS NULL
    LIMIT 1
");

$stmt->execute([
    ':kw_id' => $kw_id
]);

$timer = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($timer ?: null);