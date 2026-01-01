<?php
define("level","../");
require_once(level."inc/standard_includes.php");

if(!isset($_GET['exc_id'])) {
    die("Keine Übungs-ID übergeben!");
} else {
    $exc_id = intval($_GET['exc_id']);
    $exc_data = $db->sql_query_with_fetch("SELECT * FROM reaction_exercises WHERE re_id=:exc_id", ['exc_id'=>$exc_id]);
}

try {
    if(!IS_AJAX) {
        $myPage = new page();
        $myHTML = new html();
        $myPage->set_title($exc_data->re_title);
        if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

		$myPage->add_js_link('inc/js/chart.js');

        $myPage->add_js_link('inc/js/details.js');
        $myPage->add_css_link('inc/css/details.css');

        $myPage->add_content("<h1>{$myPage->get_title()}</h1>");

        $db->sql_query("SELECT
                            COALESCE(
                                NULLIF(CONCAT_WS(' ', user_firstname, user_lastname), ''), 
                                user_account
                            ) AS user_fullname,
                            user_id, user_hide
                            FROM users
                            WHERE user_hide != 1
                            ORDER BY user_fullname ASC");
        $myPage->add_content("<div><a href='overview.php?exc_id=".$_GET['exc_id']."'><button class='orange'><<</button></a>");
        $myPage->add_content($myHTML->get_selection($db,'user_selection','user_id','user_fullname',""));
        $myPage->add_content("</div>");

        $myPage->add_content("
        <div id='cube_infos' style='display:none;'>
            <!-- Kontrollbuttons -->
            <div id='buttons_header' class='toggle-header' onclick='toggleButtons()'>
                <span id='button-toggle-icon' class='toggle-icon'>▼</span>
                <span>Kontrollbuttons</span>
            </div>
            <div id='buttons_list'>
                <button class='red' id='delete_data'>Daten löschen</button>
                <button class='green' value='Starten' id='start'>Starten</button>
            </div>

            <!-- Sensoren -->
            <div id='sensor_header' class='toggle-header' onclick='toggleSensors()'>
                <span id='sensor-toggle-icon' class='toggle-icon'>▼</span>
                <span>Sensoren</span>
            </div>
            <div id='sensor_list'></div>

            <!-- Diagramm -->
            <div id='chart_header' class='toggle-header' onclick='toggleChart()'>
                <span id='chart-toggle-icon' class='toggle-icon'>▼</span>
                <span>Diagramm</span>
            </div>
            <div id='chart_container'>
                <canvas id='reaction_chart'></canvas>
            </div>

            <!-- Sonstiges -->
            <div id='misc_header' class='toggle-header' onclick='toggleMisc()'>
                <span id='misc-toggle-icon' class='toggle-icon'>▼</span>
                <span>Sonstiges</span>
            </div>
            <div id='misc_container'></div>

        </div>
        ");
        print $myPage->get_html_code();
    } else {
        switch($_GET['ajax']) {
            case 'delete_data':
                $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
                $db->sql_query("DELETE FROM reaction_exercises_positions_live WHERE repl_user_id=:userId", ['userId'=>$userId]);
                print "OK";
                break;

            case 'set_user':
                $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
                $db->sql_query("UPDATE reaction_exercises_cubes SET rec_user_id = :userId WHERE rec_re_id=:exc_id", ['userId'=>$userId, 'exc_id'=>$exc_id]);
                print "OK";
                break;  

            case 'assign_sensor':
                $mac = isset($_GET['mac']) ? $_GET['mac'] : null;
                $excId = isset($_GET['excId']) ? intval($_GET['excId']) : null;
                $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;

                $db->sql_query("SELECT * FROM reaction_exercises WHERE re_id=:exc_id", ['exc_id'=>$excId]);
                if($db->count() > 0) {
                    $db->sql_query("UPDATE reaction_exercises_cubes SET rec_re_id = :excId, rec_user_id = :userId WHERE rec_mac=:mac", ['userId'=>$userId, 'excId'=>$excId, 'mac'=>$mac]);
                    print "OK";
                } else {
                    throw new Exception("Übung mit der ID $excId nicht gefunden.");
                }
                break;

            case 'set_status':
                $status = isset($_GET['status']) ? $_GET['status'] : "idle";
                if($status == "Stoppen") {
                    $status = "running";
                } else {
                    $status = "idle";
                }
                $db->sql_query("UPDATE reaction_exercises_cubes SET rec_status = :status WHERE rec_re_id=:exc_id", ['status'=>$status, 'exc_id'=>$exc_id]);
                print "OK";
                break;

            case 'get_cube_infos':
                $db->sql_query("SELECT * FROM reaction_exercises_cubes 
                                                    LEFT JOIN reaction_exercises_positions ON rec_expected_pos_id = rep_id
                                                    LEFT JOIN users ON rec_user_id = user_id
                                                    LEFT JOIN reaction_exercises ON rec_re_id = re_id
                                                    ORDER BY rec_last_update DESC");
                while ($cube = $db->get_next_res()) {
                    $data['sensors'][] = $cube;
                }

                // JSON ausgeben
                header('Content-Type: application/json');
                echo json_encode($data);
                break;

            default:
                break;
        }
    }
} catch (Exception $e) {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
}
?>
