<?php
// define("level","../../../");
// require_once(level."inc/standard_includes.php");

switch($_GET['ajax']) {

    /* ================= LISTE ================= */

    case 'get_reaction_exercises':
        $db->sql_query("
            SELECT *
            FROM reaction_exercises
            ORDER BY re_created_on DESC
        ");
        header('Content-Type: application/json');
        echo json_encode($db->fetch_all());
        break;


    /* ================= FORM ================= */

    case 'get_reaction_exercise_form':

        if ($_GET['id'] > 0) {
            $db->sql_query("SELECT * FROM reaction_exercises WHERE re_id=:id", ['id'=>$_GET['id']]);
            $exc = $db->get_next_res();
        } else {
            $exc = (object)['re_id'=>0,'re_title'=>'','re_description'=>''];
        }

        print "
        <form id='edit_reaction_form'>
            <input type='hidden' name='id' value='{$exc->re_id}'>

            <label>Titel<br>
                <input type='text' name='title' value=\"".htmlspecialchars($exc->re_title)."\" required>
            </label><br><br>

            <label>Beschreibung<br>
                <textarea name='description'>".htmlspecialchars($exc->re_description)."</textarea>
            </label><br><br>
        ";

        if ($exc->re_id > 0) {
            print "
                <h3>Positionen</h3>
                <div id='positions_container'>Loading...</div>

                <div id='add_position_buttons'>
                Add Position:
                    <button type='button' class='add-pos-btn' data-value='V'>V</button>
                    <button type='button' class='add-pos-btn' data-value='M'>M</button>
                    <button type='button' class='add-pos-btn' data-value='H'>H</button>
                </div>
            ";
        }

        print "<br><hr/><button type='submit'>Speichern</button></form><div style='height:150px;'></div>";
        break;

    /* ================= POSITIONEN ================= */

    case 'get_positions':

        $db->sql_query(
            "SELECT * FROM reaction_exercises_positions WHERE rep_re_id = :id ORDER BY rep_created_on ASC",
            ['id' => $_GET['exc_id']]
        );

        $positions = [];
        while ($data = $db->get_next_res()) {
            $positions[] = [
                'pos_id' => $data->rep_id,       // ID der Position
                'pos_desc' => $data->rep_pos_id ?? '', // Beschreibung (falls vorhanden)
                'created_on' => $data->rep_created_on  // korrektes Feld
            ];
        }

        // JSON-Ausgabe
        header('Content-Type: application/json');
        echo json_encode($positions);
        break;

    case 'add_position':
        // Neue Position anlegen
        $db->insert(['rep_re_id'=>$_GET['exc_id'],'rep_pos_id'=>$_GET['position']], 'reaction_exercises_positions');
        $pos_id = $db->last_inserted_id;
        print "OK";
        break;


    case 'delete_position':
        $db->delete('reaction_exercises_positions', 'rep_id', $_GET['pos_id']);
        print "OK";
        break;


    case 'delete_exercise':
        $db->delete('reaction_exercises','re_id', $_GET['id']);
        print "OK";
        break;


    case 'move_position':
        // Aktuelle Position abrufen
        $db->sql_query("SELECT * FROM reaction_exercises_positions WHERE re_pos_id=:id", ['id'=>$_GET['pos_id']]);
        $cur = $db->get_next_res();

        if (!$cur) {
            print "ERROR: Position nicht gefunden";
            exit;
        }

        // Richtung bestimmen
        $op = $_GET['dir']=='up' ? '<' : '>';
        $ord = $_GET['dir']=='up' ? 'DESC' : 'ASC';

        // Die Position finden, die getauscht werden soll
        $db->sql_query("
            SELECT *
            FROM reaction_exercises_positions
            WHERE re_pos_exc_id=:exc
              AND created_on {$op} :dt
            ORDER BY created_on {$ord}
            LIMIT 1
        ", ['exc'=>$cur->re_pos_exc_id, 'dt'=>$cur->created_on]);

        $swap = $db->get_next_res();

        if ($swap) {
            // Swap der Timestamps
            $db->sql_query("UPDATE reaction_exercises_positions SET created_on=:d WHERE re_pos_id=:id",
                ['d'=>$swap->created_on, 'id'=>$cur->re_pos_id]);
            $db->sql_query("UPDATE reaction_exercises_positions SET created_on=:d WHERE re_pos_id=:id",
                ['d'=>$cur->created_on, 'id'=>$swap->re_pos_id]);
            print "OK";
        } else {
            print "NO_SWAP";
        }
        break;


    /* ================= SAVE ================= */

    case 'save_reaction_exercise':
        if ($_GET['id'] > 0) {
            $db->sql_query("
                UPDATE reaction_exercises
                SET re_title=:t, re_description=:d
                WHERE re_id=:id
            ", ['t'=>$_GET['title'],'d'=>$_GET['description'],'id'=>$_GET['id']]);
        } else {
            $db->insert([
                're_title'=>$_GET['title'],
                're_description'=>$_GET['description'],
                're_created_by'=>$_SESSION['login_user']->id
            ], 'reaction_exercises');
        }
        print "OK";
        break;
}
