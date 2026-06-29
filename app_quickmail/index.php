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
  print "<a href='import.php'><button style='padding:8px 16px;border-radius:6px;cursor:pointer;'>Import</button></a>";
  die();
}

/*
 * 2. ZGBs zur KW holen
 */
$zgbStmt = $db->connection->prepare("
    SELECT *
    FROM quickmail_zgbs
    WHERE zgb_kw_id = :kw_id
    ORDER BY zgb_code
");

$zgbStmt->execute([
    ':kw_id' => $kw->kw_id
]);

$zgbs = $zgbStmt->fetchAll(PDO::FETCH_OBJ);

function getZgbColor($zgb): string
{
    $count = 0;

    if (!empty($zgb->zgb_load_time_real)) $count++;
    if (!empty($zgb->zgb_sort_time_real)) $count++;
    if (!empty($zgb->zgb_drive_time_real)) $count++;
    if (!empty($zgb->zgb_delivery_time_real)) $count++;

    return match ($count) {
        0 => '#fff',
        1 => '#fed7aa',
        2 => '#fef3c7',
        3 => '#bbf7d0',
        4 => '#16a34a',
    };
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

<body data-kw-id="<?= $kw->kw_id ?>">

<div id="confirmationOverlay" style="
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    align-items:center;
    justify-content:center;
">

    <div style="
        background:white;
        padding:20px;
        border-radius:12px;
        min-width:250px;
        text-align:center;
        display:flex;
        flex-direction:column;
        align-items:center;
    ">
        <h1>Timer wirklich starten/stoppen?</h1>
        <button onclick="confirmed()" style="font-size:16pt;background-color:#AADDAA;border-radius:2vw;width:50%;color:black;padding:2vw;">OK</button>
        <button onclick="closeConfirmationOverlay()" style="font-size:16pt;background-color:#DDAAAA;border-radius:2vw;width:50%;color:black;padding:2vw;">Abbrechen</button>

    </div>
</div>



<div id="kmOverlay" style="
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.5);
    z-index:9999;
    align-items:center;
    justify-content:center;
">

    <div style="
        background:white;
        padding:20px;
        border-radius:12px;
        min-width:250px;
        text-align:center;
        display:flex;
        flex-direction:column;
        align-items:center;
    ">

        <div style="margin-bottom:10px;font-weight:bold;">
            KM eingeben
        </div>

        <input id="kmInput" type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="off"
            style="
            padding:8px;
            width:50%;
            font-size:16px;
            margin-bottom:10px;
            text-align:center;
        ">

        <button onclick="confirmKm()" style="font-size:16pt;background-color:#AADDAA;border-radius:2vw;width:70%;color:black;padding:2vw;margin-bottom:5px;">OK</button>
        <button onclick="closeKmOverlay()" style="font-size:16pt;background-color:#DDAAAA;border-radius:2vw;width:70%;color:black;padding:2vw;">Abbrechen</button>

    </div>
</div>


<div style="display:flex;align-items:center;gap:15px;">

    <h1>
        KW <?= htmlspecialchars($kw->kw_week) ?> /
        <?= htmlspecialchars($kw->kw_year) ?>
    </h1>

    <div style="margin-left:auto;display:flex;gap:10px;">
        <a href='import.php'><button style="padding:8px 16px;border-radius:6px;cursor:pointer;">Import</button></a>
        <a href='export.php'><button style="padding:8px 16px;border-radius:6px;cursor:pointer;">Export</button></a>
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

<div class="grid">

<?php foreach ($zgbs as $zgb):

$loadPlan = (float)$zgb->zgb_load_time_plan; if($loadPlan == 0) $loadPlan = 0.1; // Avoid division by zero
$loadReal = (float)$zgb->zgb_load_time_real;
$minutes = intdiv($loadReal, 60);
$secs = $loadReal % 60;
$loadRealView = sprintf("%d:%02d", $minutes, $secs);

$sortPlan = (float)$zgb->zgb_sort_time_plan; if($sortPlan == 0) $sortPlan = 0.1; // Avoid division by zero
$sortReal = (float)$zgb->zgb_sort_time_real;
$minutes = intdiv($sortReal, 60);
$secs = $sortReal % 60;
$sortRealView = sprintf("%d:%02d", $minutes, $secs);

$drivePlan = (float)$zgb->zgb_drive_time_plan; if($drivePlan == 0) $drivePlan = 0.1; // Avoid division by zero
$driveReal = (float)$zgb->zgb_drive_time_real;
$minutes = intdiv($driveReal, 60);
$secs = $driveReal % 60;
$driveRealView = sprintf("%d:%02d", $minutes, $secs);

$deliveryPlan = (float)$zgb->zgb_delivery_time_plan; if($deliveryPlan == 0) $deliveryPlan = 0.1; // Avoid division by zero
$deliveryReal = (float)$zgb->zgb_delivery_time_real;
$minutes = intdiv($deliveryReal, 60);
$secs = $deliveryReal % 60;
$deliveryRealView = sprintf("%d:%02d", $minutes, $secs);

$uid = htmlspecialchars($zgb->zgb_code);
?>

<div class="card" style="background: <?= getZgbColor($zgb) ?>;">
    <div class="code"><?= $uid ?></div>

    <div class="charts">

        <div class="chartBox" id="box_load_<?= $uid ?>">
          <canvas id="load_<?= $uid ?>" onclick="onChartClick('load_<?= $uid ?>',<?= $zgb->zgb_load_time_plan ?>)">
          </canvas>
          <div class="centerTimer" id="timer_load_<?= $uid ?>" data-plan-seconds="<?= $zgb->zgb_load_time_plan ?>"><?= $loadRealView ?></div>
        </div> 

        <div class="chartBox" id="box_sort_<?= $uid ?>">
          <canvas id="sort_<?= $uid ?>" onclick="onChartClick('sort_<?= $uid ?>',<?= $zgb->zgb_sort_time_plan ?>)">
          </canvas>
          <div class="centerTimer" id="timer_sort_<?= $uid ?>" data-plan-seconds="<?= $zgb->zgb_sort_time_plan ?>"><?= $sortRealView ?></div>
        </div>

        <div class="chartBox" id="box_drive_<?= $uid ?>">
            <canvas id="drive_<?= $uid ?>" onclick="onChartClick('drive_<?= $uid ?>',<?= $zgb->zgb_drive_time_plan ?>)">
            </canvas>
          <div class="centerTimer" id="timer_drive_<?= $uid ?>" data-plan-seconds="<?= $zgb->zgb_drive_time_plan ?>"><?= $driveRealView ?></div>
        </div>

        <div class="chartBox" id="box_delivery_<?= $uid ?>">
            <canvas id="delivery_<?= $uid ?>" onclick="onChartClick('delivery_<?= $uid ?>',<?= $zgb->zgb_delivery_time_plan ?>)">
            </canvas>
          <div class="centerTimer" id="timer_delivery_<?= $uid ?>" data-plan-seconds="<?= $zgb->zgb_delivery_time_plan ?>"><?= $deliveryRealView ?></div>
        </div>

    </div>
</div>

<script>

makeChart(<?= $kw->kw_id ?>, <?= $zgb->zgb_id ?>,"load_<?= $uid ?>", <?= $loadPlan ?>, <?= $loadReal ?>, "Be-/Entladen");
makeChart(<?= $kw->kw_id ?>, <?= $zgb->zgb_id ?>,"sort_<?= $uid ?>", <?= $sortPlan ?>, <?= $sortReal ?>, "Sortierung");
makeChart(<?= $kw->kw_id ?>, <?= $zgb->zgb_id ?>,"drive_<?= $uid ?>", <?= $drivePlan ?>, <?= $driveReal ?>, "Fahrten");
makeChart(<?= $kw->kw_id ?>, <?= $zgb->zgb_id ?>,"delivery_<?= $uid ?>", <?= $deliveryPlan ?>, <?= $deliveryReal ?>, "Zustellung");
</script>

<?php endforeach; ?>

</div>

</body>
</html>