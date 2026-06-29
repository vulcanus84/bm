<?php

header('Content-Type: application/json');

define("level","../");                                //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");      //Load all necessary files (DB-Connection, User-Login, etc.)
require_once("inc/checkToken.php");                   //Load function to check token

if (!checkToken($db)) {
    echo json_encode(["valid" => false,"reason" => "Invalid token"]);
} else {
  echo json_encode(["valid" => true]);
}

?>