<?php
include 'inc/php/db.php';

$db = new db();

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdfText = $_POST['pdf_text'] ?? '';

    if (!empty($pdfText)) {
        $lines = preg_split("/\R/", $pdfText);
        $ln_no = 1;

        foreach ($lines as $line) {
            if($ln_no == 1) {
            preg_match('/(\d{2}-\d{4})/', $line, $matches);
            $zgbKW = $matches[1] ?? "";
            $kwId = generateKW($zgbKW);
            } else {
            if(trim($line) === "") continue; // Skip empty lines
            generateZGB($line, $kwId);
            }
            $ln_no++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ZGB Parser</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        textarea {
            width: 100%;
            height: 300px;
        }

        .result {
            margin-top: 20px;
            padding: 15px;
            background: #f4f4f4;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div style="display:flex;align-items:center;gap:15px;">

    <h1>ZGB PDF Text Parser</h1>

    <div style="margin-left:auto;display:flex;gap:10px;">
        <a href='index.php'><button style="padding:8px 16px;border-radius:6px;cursor:pointer;">Home</button></a>
    </div>

</div>


<form method="post">
    <label>PDF Text einfügen:</label><br>
    Beispiel (Tab-getrennt, erste Zeile enthält KW-Jahr):
    <pre>
Springer Dispo KW 23-2026 Hübscher Claude.xlsb
6248-0	Alberswil		25-561	Fr	2	231	0	231	24	KG	1	39.00%	-16.00%	-2.00%	-8.00%	13.00%	4.00%	-46.00%	0	0.3	0.5	2.3	0	3.2
4665-11	Oftringen	Wolfbach	25-822	Mi	3	250	151	401	41	KG	12	-1.00%	-13.00%	-1.00%	-8.00%	-23.00%	4.00%	-32.00%	0.1	0.4	0	1.9	0	2.5
4663-5	Aarburg	Dürrberg	25-840	Fr	2	212	261	473	41	KG	40	-20.00%	-1.00%	2.00%	-8.00%	-27.00%	4.00%	-38.00%	0.1	0.4	0	1.6	0	2.1
4665-2	Oftringen	Langernweg	25-161	Mi	3	204	169	373	33	KG	40	-20.00%	-19.00%	-2.00%	-8.00%	-49.00%	5.00%	-41.00%	0.1	0.3	0.1	1.2	0	1.7
6147-0	Altbüron	Linden	25-388	Do	2	248	0	248	28	KG	1	39.00%	-15.00%	-2.00%	-8.00%	14.00%	4.00%	-41.00%	0	0.4	0.4	2.6	0	3.5
6252-3	Dagmersellen	Stengelmatt	25-821	Do	2	366	0	366	40	KG	1	39.00%	-8.00%	-1.00%	-8.00%	22.00%	1.00%	-42.00%	0.1	0.5	0.4	3.4	0	4.4
4800-7	Zofingen	Zentrum	25-403	Mi	3	278	157	435	42	KG	1	39.00%	-6.00%	1.00%	-8.00%	26.00%	3.00%	-38.00%	0.1	0.5	0.1	3.3	0	4
    </pre>
    <textarea name="pdf_text" placeholder="Hier den kopierten PDF-Text einfügen..."><?= htmlspecialchars($_POST['pdf_text'] ?? '') ?></textarea>
    <br><br>
    <button type="submit">Absenden</button>
</form>

<?php if ($result !== null): ?>
    <div class="result">
        <h3>Resultat</h3>
        <pre><?php print_r($result); ?></pre>
    </div>
<?php endif; ?>

</body>
</html>

<?php

function generateKW(string $zgbKW) {
   global $db;

   $stmt = $db->connection->prepare("
        INSERT INTO quickmail_kws (kw_user_id, kw_year, kw_week)
        VALUES (:user_id, :year, :week)
        ON DUPLICATE KEY UPDATE
            kw_id = LAST_INSERT_ID(kw_id)
    ");

    $stmt->execute([
        ':user_id' => 106,
        ':year'    => substr($zgbKW, 3, 4),
        ':week'    => substr($zgbKW, 0, 2)
    ]);

    return $db->connection->lastInsertId();
}

function generateZGB(string $text, int $kwId) {
    global $db;
    $parts = preg_split('/\t/', trim($text));
    if($parts[19]==0) $parts[19] = 0.05;
    if($parts[20]==0) $parts[20] = 0.05;
    if($parts[21]==0) $parts[21] = 0.05;
    if($parts[22]==0) $parts[22] = 0.05;

    $stmt = $db->connection->prepare("
      INSERT INTO quickmail_zgbs (
          zgb_kw_id,
          zgb_code,
          zgb_load_time_plan,
          zgb_sort_time_plan,
          zgb_drive_time_plan,
          zgb_delivery_time_plan,
          zgb_count_addressed,
          zgb_count_unaddressed,
          zgb_weight,
          zgb_delivery_count
      )
      VALUES (
          :kw_id,
          :code,
          :load_time,
          :sort_time,
          :drive_time,
          :delivery_time,
          :count_addressed,
          :count_unaddressed,
          :weight,
          :delivery_count
      )
      ON DUPLICATE KEY UPDATE
          zgb_load_time_plan = VALUES(zgb_load_time_plan),
          zgb_sort_time_plan = VALUES(zgb_sort_time_plan),
          zgb_drive_time_plan = VALUES(zgb_drive_time_plan),
          zgb_delivery_time_plan = VALUES(zgb_delivery_time_plan),
          zgb_count_addressed = VALUES(zgb_count_addressed),
          zgb_count_unaddressed = VALUES(zgb_count_unaddressed),
          zgb_weight = VALUES(zgb_weight),
          zgb_delivery_count = VALUES(zgb_delivery_count)
  ");


  $stmt->execute([
      ':kw_id'            => $kwId,
      ':code'             => $parts[3] ?? null,

      ':load_time'        => $parts[19]*3600 ?? null,
      ':sort_time'     => $parts[20]*3600 ?? null,
      ':drive_time'       => $parts[21]*3600 ?? null,
      ':delivery_time'    => $parts[22]*3600 ?? null,

      ':count_addressed'  => $parts[6] ?? null,
      ':count_unaddressed'=> $parts[7] ?? null,

      ':weight'           => $parts[9] ?? null,
      ':delivery_count'   => $parts[11] ?? null,
  ]);
}

?>