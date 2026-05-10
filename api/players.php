<?php

header('Content-Type: application/json');


define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)

$myPage = new page();
$db->sql_query($myPage->get_setting('sql_user_selection'));

$players = [];
while ($row = $db->get_next_res()) {
    $players[] = [
        "id" => $row->user_id,
        "name" => $row->user_account,
        "image" => "none"
    ];
}

echo json_encode($players);