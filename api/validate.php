<?php

header('Content-Type: application/json');

define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)

$data = json_decode(file_get_contents("php://input"), true);
$token = $data["token"];

$db->sql_query("SELECT * FROM sessions
                        WHERE token = :token",array('token'=>$token));
$daten = $db->get_next_res();
if ($db->count()==0)
{
    echo json_encode(["valid" => false]);
    exit;
} else {
    if (strtotime($daten->expires_at) < time() || $daten->revoked) {
        echo json_encode(["valid" => false]);
        exit;
    }
}
?>