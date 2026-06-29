<?php

function checkToken($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    $token = $data["token"] ?? null;

    if (!$token) {
        return false;
    }

    $db->sql_query("SELECT * FROM sessions
                            WHERE token = :token",array('token'=>$token));
    $daten = $db->get_next_res();
        if ($db->count()==0)
        {
            return false;
        } else {
            if (strtotime($daten->expires_at) < time() || $daten->revoked) {
                return false;
            } else {
                return true;
            }
        }

}