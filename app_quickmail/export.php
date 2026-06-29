<?php
include 'inc/php/db.php';

$db = new db();

/*
 * Alle KWs laden
 */
$allKwStmt = $db->connection->query("
    SELECT *
    FROM quickmail_kws
    ORDER BY kw_year DESC, kw_week DESC
");

$allKws = $allKwStmt->fetchAll(PDO::FETCH_OBJ);

/*
 * Gewählte KW oder neueste laden
 */
$selectedKwId = isset($_GET['kw']) ? (int)$_GET['kw'] : 0;

if ($selectedKwId > 0) {

    $kwStmt = $db->connection->prepare("
        SELECT *
        FROM quickmail_kws
        WHERE kw_id = :id
    ");

    $kwStmt->execute([
        ':id' => $selectedKwId
    ]);

} else {

    $kwStmt = $db->connection->query("
        SELECT *
        FROM quickmail_kws
        ORDER BY kw_year DESC, kw_week DESC
        LIMIT 1
    ");

}

$kw = $kwStmt->fetch(PDO::FETCH_OBJ);

if (!$kw) {
    die("Keine KW gefunden");
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Quickmail Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script src="inc/js/index.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="inc/css/style.css">
</head>

<body>



<div style="display:flex;align-items:center;gap:15px;">

    <h1>
        KW <?= htmlspecialchars($kw->kw_week) ?> /
        <?= htmlspecialchars($kw->kw_year) ?>
    </h1>

    <div style="margin-left:auto;display:flex;gap:10px;">
        <a href='index.php'><button style="padding:8px 16px;border-radius:6px;cursor:pointer;">Home</button></a>
    </div>

</div>

<div class="sub">
    <form method="get" style="margin:0;">
        <select name="kw" onchange="this.form.submit()">
            <?php foreach ($allKws as $item): ?>
                <option
                    value="<?= $item->kw_id ?>"
                    <?= $item->kw_id == $kw->kw_id ? 'selected' : '' ?>>
                    KW <?= $item->kw_week ?> / <?= $item->kw_year ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php

$stmt = $db->connection->prepare("
    SELECT *, 
        DATE_FORMAT(log_start_time, '%H:%i') AS log_start_time_c,
        DATE_FORMAT(log_end_time, '%H:%i') AS log_end_time_c,
        DATE_FORMAT(log_start_time, '%d.%m.%Y') AS log_date
    FROM quickmail_log
    LEFT JOIN quickmail_zgbs ON log_zgb_id = zgb_id
    LEFT JOIN quickmail_log_categories ON log_logcat_id = logcat_id
    WHERE log_kw_id = :id
");

$stmt->execute([
    ':id' => $kw->kw_id
]);

$allLogs = $stmt->fetchAll(PDO::FETCH_OBJ);

?>

<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>Name</th>
            <th>Woche</th>
            <th>Monat</th>
            <th>Jahr</th>
            <th>Datum</th>
            <th>Depot</th>
            <th>ZGB-Nr.</th>
            <th>Prozess</th>
            <th>Zeit Start</th>
            <th>Zeit Ende</th>
            <th>km Start</th>
            <th>km Ende</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($allLogs as $log): ?>
        <tr>
            <td>Hübscher</td>
            <td><?= htmlspecialchars($kw->kw_week ?? '') ?></td>
            <td></td>
            <td><?= htmlspecialchars($kw->kw_year ?? '') ?></td>
            <td><?= htmlspecialchars($log->log_date ?? '') ?></td>
            <td><?= htmlspecialchars(substr($log->zgb_code,0,strpos($log->zgb_code,"-")) ?? '') ?></td>
            <td><?= htmlspecialchars(substr($log->zgb_code,strpos($log->zgb_code,"-")+1) ?? '') ?></td>
            <td><?= htmlspecialchars($log->logcat_name ?? '') ?></td>
            <?php
                // Start/Stopp of the timers can result in missing minutes
                // Therefore every difference beyond 60 seconds will be ignored an the last endtime will be set
                if (isset($last_time))
                {
                    $start = new DateTime($log->log_start_time);
                    $last  = new DateTime($last_time);
                    $diffSeconds = $start->getTimestamp() - $last->getTimestamp();
                    if ($diffSeconds >= 60) {
                        print "<td>" . htmlspecialchars($log->log_start_time_c ?? '') . "</td>";
                    } else {
                        print "<td>" . htmlspecialchars($last_time_c ?? '') . "</td>";
                    }
                } else {
                        print "<td>" . htmlspecialchars($log->log_start_time_c ?? '') . "</td>";
                }
                $last_time   = $log->log_end_time;
                $last_time_c = $log->log_end_time_c;
            ?>
            <td><?= htmlspecialchars($log->log_end_time_c ?? '') ?></td>
            <td><?= htmlspecialchars($log->log_start_km ?? '') ?></td>
            <td><?= htmlspecialchars($log->log_end_km ?? '') ?></td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</body>
</html>