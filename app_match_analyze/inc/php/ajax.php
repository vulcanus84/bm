<?php
switch($_GET['ajax'])
{
  case 'delete_point':
    $db->delete('match_analyzes_points','ma_point_id',$_GET['point_id']);
    break;

  case 'save_point':
    $sql = "SELECT ma_reason_id FROM match_analyzes_reasons WHERE ma_reason_level1=:level1";
    $arr_params = array();
    $arr_params['level1'] = $_GET['level1'];
    if(isset($_GET['level2']) && $_GET['level2']!='') {
      $sql .= " AND ma_reason_level2=:level2";
      $arr_params['level2'] = $_GET['level2'];
    } else {
      $sql .= " AND (ma_reason_level2 IS NULL OR ma_reason_level2='')";
    }
    if(isset($_GET['level3']) && $_GET['level3']!='') {
      $sql .= " AND ma_reason_level3=:level3";
      $arr_params['level3'] = $_GET['level3'];
    } else {
      $sql .= " AND (ma_reason_level3 IS NULL OR ma_reason_level3='')";
    }
    if(isset($_GET['level4']) && $_GET['level4']!='') {
      $sql .= " AND ma_reason_level4=:level4";
      $arr_params['level4'] = $_GET['level4'];
    } else {
      $sql .= " AND (ma_reason_level4 IS NULL OR ma_reason_level4='')";
    }

    $data = $db->sql_query_with_fetch($sql,$arr_params);
    $reason_id = $data->ma_reason_id;

    if($reason_id>0) {
      $db->insert(array(
        'ma_point_ma_id'=>$_GET['ma_id'],
        'ma_point_set'=>$_GET['set'],
        'ma_point_winner'=>$_GET['winner'],
        'ma_point_reason_id'=>$reason_id,
      ),'match_analyzes_points');
      print "OK>".$db->last_inserted_id;
    } else {
      throw new Exception("Grund nicht gefunden!");
    }
    break;

  case 'get_reasons_as_json':
    $db->sql_query("SELECT * FROM match_analyzes_reasons ORDER BY ma_reason_level1, ma_reason_level2, ma_reason_level3, ma_reason_level4");
    $data = $db->fetch_all();
    header('Content-Type: application/json');
    echo json_encode($data); 
    break;

  case 'get_points_as_json':
    $db->sql_query("SELECT * FROM match_analyzes_points 
                    LEFT JOIN match_analyzes_reasons ON match_analyzes_points.ma_point_reason_id = match_analyzes_reasons.ma_reason_id
                    WHERE ma_point_ma_id=:ma_id AND ma_point_set=:set 
                    ORDER BY ma_point_created_on ASC",array('ma_id'=>$_GET['ma_id'],'set'=>$_GET['set']));
    $data = $db->fetch_all();
    header('Content-Type: application/json');
    echo json_encode($data); 
    break;

  case 'confirm_delete':
    print "<h1 class='delete_confirmation'>Wirklich löschen?</h1>";
    print "<button class='delete' id='delete_".$_GET['ma_id']."'>Ja</button>";
    print "<button class='abort_delete'id='abort_delete_".$_GET['ma_id']."'>Nein</button>";
    break;

  case 'delete_entry':
    $db->delete('match_analyzes','ma_id',$_GET['ma_id']);
    break;

  case 'save_entry':
    $updateData = [];
    $updateData['ma_created_on'] = $_GET['journal_date'] ?? null;
    $updateData['ma_created_by'] = explode(';', $_GET['trainer_id'] ?? '')[0] ?? null;
    $updateData['ma_description'] = $_GET['description'] ?? null;

    $fields = [
        'trainee_id',
        'trainee_partner_id',
        'opponent_id',
        'opponent_partner_id',
        'trainee_name',
        'trainee_partner_name',
        'opponent_name',
        'opponent_partner_name'
    ];

    foreach ($fields as $f) {
        $updateData['ma_' . $f] = (isset($_GET[$f]) && $_GET[$f] !== '') ? $_GET[$f] : null;
    }



    if((isset($_GET['ma_id']) && $_GET['ma_id']>0)) {
      //Update existing entry
      $db->update($updateData, 'match_analyzes', 'ma_id', $_GET['ma_id']);
    } else {
      //New entry
      $db->insert($updateData,'match_analyzes');
    }
    break;

  case 'show_location_details':
    if(isset($_GET['ma_id']) && $_GET['ma_id']>0) {
      $ma_id = $_GET['ma_id'];
      $d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(ma_created_on,'%Y-%m-%d')  as curr_date FROM match_analyzes WHERE ma_id='$_GET[ma_id]'");
      $trainer_id = $d->ma_created_by;
      $trainee_id = $d->ma_trainee_id;
    } else {
      $d = null;
      $ma_id = '';
      $trainer_id = $_SESSION['login_user']->id;
      $trainee_id = '';
    }

    if($_GET['location_id']=='none') {
      break;
    } elseif($_GET['location_id']=='custom') {
      switch($_GET['section']) {
        case 'pl_loc':
          $db_field = 'ma_trainee_name';
          break;
        case 'plpa_loc':
          $db_field = 'ma_trainee_partner_name';
          break;
        case 'op_loc':
          $db_field = 'ma_opponent_name';
          break;
        case 'oppa_loc':
          $db_field = 'ma_opponent_partner_name';
          break;
      }
      $value = isset($d->$db_field) ? $d->$db_field : '';
      print "<input type='text' id='custom_name_{$_GET['section']}' placeholder='Name eingeben' value='".htmlspecialchars($value, ENT_QUOTES)."' />";
      break;
    } else {
      $location_id = intval($_GET['location_id']);
    }

    $db->sql_query("
        SELECT *
        FROM users
        LEFT JOIN (
            SELECT * 
            FROM match_analyzes 
            WHERE ma_id = '$ma_id'
        ) AS ma_temp 
            ON (
                ma_temp.ma_trainee_id = users.user_id 
                OR ma_temp.ma_trainee_partner_id = users.user_id
                OR ma_temp.ma_opponent_id = users.user_id
                OR ma_temp.ma_opponent_partner_id = users.user_id
            )
        LEFT JOIN location2user 
            ON location2user.location2user_user_id = users.user_id
        LEFT JOIN locations 
            ON location2user.location2user_location_id = locations.location_id
        WHERE 
            (user_hide != '1' OR ma_temp.ma_trainee_id > 0)
            AND user_id > 1
            AND locations.location_id = {$location_id}
        ORDER BY 
            locations.location_name, 
            user_account
    ");

    switch($_GET['section']) {
      case 'pl_loc':
        $db_field = 'ma_trainee_id';
        break;
      case 'plpa_loc':
        $db_field = 'ma_trainee_partner_id';
        break;
      case 'op_loc':
        $db_field = 'ma_opponent_id';
        break;
      case 'oppa_loc':
        $db_field = 'ma_opponent_partner_id';
        break;
    }

    while($d = $db->get_next_res())
    {
      $player = new user($d->user_id);
      $activated = 'activated_' . $_GET['section'];;
      $deactivated = 'deactivated_' . $_GET['section'];

      print "<div class='" . ($d->user_id == $d->$db_field ? $activated : $deactivated) . "' id='{$_GET['section']}_{$d->user_id}_{$d->location_id}' ><img src='".$player->get_pic_path(true)."'/><br/>{$player->login}</div>";
    }

    break;


  case 'show_edit':
    if(isset($_GET['ma_id']) && $_GET['ma_id']>0) {
      $ma_id = $_GET['ma_id'];
      $d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(ma_created_on,'%Y-%m-%d')  as curr_date FROM match_analyzes WHERE ma_id='$_GET[ma_id]'");
      $trainer_id = $d->ma_created_by;
      
      $trainee = ($d->ma_trainee_id > 0) ? $d->ma_trainee_id : $d->ma_trainee_name;
      $trainee_partner = ($d->ma_trainee_partner_id > 0) ? $d->ma_trainee_partner_id : $d->ma_trainee_partner_name;
      $opponent = ($d->ma_opponent_id > 0) ? $d->ma_opponent_id : $d->ma_opponent_name;
      $opponent_partner = ($d->ma_opponent_partner_id > 0) ? $d->ma_opponent_partner_id : $d->ma_opponent_partner_name;
     
    } else {
      $d = null;
      $ma_id = '';
      $trainer_id = $_SESSION['login_user']->id;
      $trainee = null;
      $trainee_partner = null;
      $opponent = null;
      $opponent_partner = null;
    }

    print "<h2>Datum</h2>";
    print "<input id='journal_date' type='date' value='" . ($d->curr_date ?? date('Y-m-d')) . "'/>";
    print "<hr/>";
    print "<h2>Beschreibung</h2>";
    print "<textarea id='training_description'>".($d->ma_description ?? '') . "</textarea>";
    print "<hr/>";

    print "<h2>Trainer</h2>";
    print "<div>";
    $db->sql_query("SELECT * FROM users 
                    LEFT JOIN location2user ON location2user.location2user_user_id = users.user_id 
                    LEFT JOIN locations ON location2user.location2user_location_id = locations.location_id 
                    WHERE user_hide!='1' AND user_id>1 AND locations.location_name = '_Trainer'
                    ORDER BY locations.location_name, user_account");

    while($d = $db->get_next_res())
    {
      $player = new user($d->user_id);
      print "<div class='" . ($d->user_id == $trainer_id ? 'activated_trainer' : 'deactivated_trainer') . "' id='img_trainer_{$d->user_id}'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>";
    }

    print "</div>";
    print "<div style='clear: both;'>";
    print "<hr/>";

    $last_group = null;
    
    $userLocations = getUserLocations($db, $_SESSION['login_user']->id);

    show_player_section('Spieler', 'pl', $trainee, $db);
    show_player_section('Gegner', 'op', $opponent, $db);
    show_player_section('Partner des Spielers', 'plpa', $trainee_partner,$db);
    show_player_section('Partner des Gegners', 'oppa', $opponent_partner, $db);

    print "</div><p/><div class='save'><button class='save_entry' id='save_entry_".$ma_id."'>Speichern</button></div>";
    break;

  default:
    throw new Exception("Ungültiger AJAX-Aufruf!<br/>".$_GET['ajax']);
    break;
}


function getUserLocations($db, $userId) {
    $userId = (int)$userId;

    $sql = "
        SELECT l.location_id, l.location_name
        FROM locations l
        JOIN location_permissions lp 
            ON lp.loc_permission_loc_id = l.location_id
        JOIN location2user l2u
            ON l2u.location2user_location_id = l.location_id
        JOIN users u
            ON u.user_id = l2u.location2user_user_id
        WHERE lp.loc_permission_user_id = {$userId}
          AND u.user_hide != '1'
        GROUP BY l.location_id, l.location_name
        ORDER BY l.location_name
    ";

    $db->sql_query($sql);

    $locations = [];
    while ($row = $db->get_next_res()) {
        $locations[] = [
            'location_id'   => $row->location_id,
            'location_name' => $row->location_name
        ];
    }

    return $locations;
}

function show_player_section($sectionTitle, $sectionIdPrefix, $selectedUserId, $db) {
    $userLocations = getUserLocations($db, $_SESSION['login_user']->id);
    if (is_numeric($selectedUserId) && $selectedUserId > 0) { 
        // DB-Abfrage für numerische User-ID
        $d = $db->sql_query_with_fetch("
            SELECT location2user_location_id
            FROM location2user
            LEFT JOIN locations ON location2user_location_id = location_id
            WHERE location2user_user_id = " . intval($selectedUserId) . "
            ORDER BY location_name ASC
            LIMIT 1
        ");
        
        // Prüfen, ob die Location existiert
        $selectedLoc = ($d && !empty($d->location2user_location_id))
            ? $d->location2user_location_id
            : 'none';

    } elseif (is_string($selectedUserId) && $selectedUserId !== '') {
        // Text → custom
        $selectedLoc = 'custom';
    } else {
        // leer / sonstiges → none
        $selectedLoc = 'none';
    }

    print "<div id='{$sectionIdPrefix}'>";
    print "<h2>{$sectionTitle}</h2>";
    print "<select id='{$sectionIdPrefix}_loc' name='location_filter'>";

    // feste Zusatzoptionen
    $noneSelected   = ($selectedLoc === 'none')   ? 'selected' : '';
    $customSelected = ($selectedLoc === 'custom') ? 'selected' : '';

    print "<option value='none' $noneSelected>-- Nicht vorhanden --</option>";
    print "<option value='custom' $customSelected>-- Eigener Name --</option>";

    // dynamische Standort-Optionen
    foreach ($userLocations as $location) {
        $id   = htmlspecialchars($location['location_id']);
        $name = htmlspecialchars($location['location_name']);

        $selected = ($id == $selectedLoc) ? 'selected' : '';

        print "<option value='{$id}' {$selected}>{$name}</option>";
    }

    print "</select>";
    print "<div id='{$sectionIdPrefix}_div'></div>";
    print "</div>";
    print "<hr style='clear:both;' />";
}