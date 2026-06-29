<?php

header('Content-Type: application/json');
define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
require_once(level."api/inc/checkToken.php");     //Load function to check token

if (!checkToken($db)) {
    echo json_encode(["valid" => false,"reason" => "Invalid token"]);
    exit;
}

$db->sql_query("SELECT * FROM users WHERE user_hide = 0 ORDER BY user_account ASC");
$img_path = "app_tournament/user_pics/";

$players = [];
while ($row = $db->get_next_res()) {
    if(file_exists(level . $img_path . $row->user_id . "_stars.png")) {
        $img_full = $img_path . $row->user_id . "_stars.png";
    } else {
        $img_full = $img_path . $row->user_id . ".png";
    }

    if(file_exists(level . $img_path . $row->user_id . "_stars_t.png")) {
        $img_thumbnail = $img_path . $row->user_id . "_stars_t.png";
    } else {
        $img_thumbnail = $img_path . $row->user_id . "_t.png";
    }

    $players[] = [
        "id" => $row->user_id,
        "name" => $row->user_account,
        "firstname" => $row->user_firstname,
        "lastname" => $row->user_lastname,
        "image_thumbnail" => $img_thumbnail,
        "image_full" => $img_full,
    ];
}

echo json_encode($players);