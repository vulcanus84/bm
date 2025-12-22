<?php
define("level","../");
require_once(level."inc/standard_includes.php");
if(isset($_GET['mode']) &&  $_GET['mode'] === 'ping') {
    header('Content-Type: application/json');
    echo json_encode(["status"=>"OK"]);
    die();
}

if(isset($_GET['mode']) &&  $_GET['mode'] === 'admin') {
    // Admin-Mode: Show all active cubes
    $myPage = new page();
    $myPage->set_title("Reaction Game");

    $myPage->add_content("<table border='1' style='width:400px;'>");
    $myPage->add_content("<tr><th>MAC Adresse</th><th>Distanz</th><th>Last Update</th></tr>");
    $db->sql_query("SELECT * FROM reaction_exercises_cubes");
    while($data = $db->get_next_res()) {
        $myPage->add_content("<tr>");
        $myPage->add_content("<td style='text-align:center;'>" . htmlspecialchars($data->re_actCubes_mac) . "</td>");
        $myPage->add_content("<td style='text-align:right;'>" . htmlspecialchars($data->re_actCubes_distance) . "</td>");
        $myPage->add_content("<td style='text-align:right;'>" . htmlspecialchars($data->re_actCubes_lastUpdate) . "</td>");
        $myPage->add_content("</tr>");
    }
    $myPage->add_content("</table>");

    print $myPage->get_html_code();

} else {
    if(isset($_GET['mac']) &&  $_GET['mac'] !== '') {
        // *****************************************
        // UPDATE cube info on Server
        // *****************************************
        // Check if cube exists
        $db->sql_query("SELECT * FROM reaction_exercises_cubes WHERE rec_mac=:mac", ['mac'=>$_GET['mac']]);
        if($db->count() > 0) {
            $cube_data = $db->get_next_res();
            // Update last connection time
            $data = ["rec_last_update" => date("Y-m-d H:i:s")];
            if(isset($_GET['distance'])) { $data["rec_distance"] = $_GET['distance']; } else { $data["rec_distance"] = null; }
            if(isset($_GET['sequence'])) { $data["rec_sequence"] = $_GET['sequence']; } else { $data["rec_sequence"] = null; }

            if (isset($_GET['nextPos']) && $_GET['nextPos'] != 0) {
                $data["rec_expected_pos_id"] = $_GET['nextPos'];
                $db->sql_query("SELECT * FROM reaction_exercises_positions WHERE rep_id=:posId", ['posId'=>$_GET['nextPos']]);
                if($db->count() == 0) { $data["rec_expected_pos_id"] = null; }
            } else {
                $data["rec_expected_pos_id"] = null;
            }

            $db->update(
                $data,
                "reaction_exercises_cubes",
                "rec_mac",
                $_GET['mac']
            );
        } else {
            // Create cube entry
            $db->insert([
                "rec_mac"         => $_GET['mac'] ?? ''
            ], "reaction_exercises_cubes");
        }
        // *****************************************
        // Prepare response to update cube
        // *****************************************
        if(isset($cube_data) && $cube_data->rec_status != 'running') {
            $response['excId'] = $cube_data->rec_re_id;
            $data = $db->sql_query_with_fetch("SELECT * FROM reaction_exercises_cubes WHERE rec_re_id=:id", ['id'=>$response['excId']]);
            $data2 = $db->sql_query_with_fetch(
                "SELECT GROUP_CONCAT(rep_pos_id ORDER BY rep_id ASC) AS sequence,
                        GROUP_CONCAT(rep_id ORDER BY rep_id ASC) AS sequenceIds
                FROM reaction_exercises_positions
                WHERE rep_re_id = :id",
                ['id' => $response['excId']]
            );

            // Normal mode: Update or create active cube entry
            $response = [
                "status" => $data->rec_status,
                "sequence" => $data2->sequence,
                "sequenceIds" => $data2->sequenceIds,
                "userId" => $data->rec_user_id,
                "exerciseId" => $data->rec_re_id
            ];
        } else {
            $response = [
                "status" => "running"
            ];
        }

    } else {
        $response['status'] = "error";
        $response['message'] = "No MAC address provided.";
    }
    // JSON ausgeben
    header('Content-Type: application/json');
    echo json_encode($response);
}