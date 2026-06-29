<?php

header('Content-Type: application/json');

define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)

$data = json_decode(file_get_contents("php://input"), true);

$username = $data["username"];
$password = $data["password"];

$db->sql_query("SELECT * FROM users
                        WHERE user_account = :user_account OR user_email = :user_account",array('user_account'=>$username));
$daten = $db->get_next_res();
if ($db->count()==0)
{
  if(trim($username)!='')
  {
    echo json_encode([
        "success" => false,
        "error" => "Invalid username"
    ]);
  }
}
else
{
  if (hash('sha256', $password)==$daten->user_password)
  {
    $token = bin2hex(random_bytes(32));
    $db->insert(array('user_id'=>$daten->user_id,'token'=>$token,'expires_at'=>date('Y-m-d H:i:s', strtotime('+7 days'))),'sessions');

    echo json_encode([
        "success" => true,
        "token" => $token
    ]);
  }
  else
  {
    echo json_encode([
        "success" => false,
        "error" => "Invalid password"
    ]);
  }
}
?>