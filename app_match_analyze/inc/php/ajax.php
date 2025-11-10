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
    if((isset($_GET['ma_id']) && $_GET['ma_id']>0)) {
      //Update existing entry
      $db->update(array(
        'ma_created_on'=>$_GET['journal_date'],
        'ma_created_by'=>explode(';',$_GET['trainer_id'])[0],
        'ma_description'=>$_GET['description'],
        'ma_trainee_id'=>explode(';',$_GET['trainee_id'])[0] ?? null,
        'ma_opponent_name'=>$_GET['opponent_name']
      ),'match_analyzes','ma_id',$_GET['ma_id']);
    } else {
      //New entry
      $db->insert(array(
        'ma_created_on'=>$_GET['journal_date'],
        'ma_created_by'=>explode(';',$_GET['trainer_id'])[0],
        'ma_description'=>$_GET['description'],
        'ma_trainee_id'=>explode(';',$_GET['trainee_id'])[0] ?? null,
        'ma_opponent_name'=>$_GET['opponent_name']
      ),'match_analyzes');
    }
    break;

  case 'show_edit':
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
    print "<h2>Datum</h2>";
    print "<input id='journal_date' type='date' value='" . ($d->curr_date ?? date('Y-m-d')) . "'/>";
    print "<hr/>";
    print "<h2>Beschreibung</h2>";
    print "<textarea id='training_description'>".($d->ma_description ?? '') . "</textarea>";
    print "<hr/>";
    print "<h2>Name des Gegners</h2>";
    print "<input type='text' id='opponent_name' value='". ($d->ma_opponent_name ?? '') ."'/>";
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
    print "<h2>Spieler</h2>";

    $last_group = null;
    $db->sql_query("
        SELECT l.* 
        FROM locations l
        JOIN location_permissions lp ON lp.loc_permission_loc_id = l.location_id
        WHERE lp.loc_permission_user_id = '{$_SESSION['login_user']->id}'
        ORDER BY l.location_name
    ");

    while($d = $db->get_next_res()) {
      print "<button class='location_select' id='btn_location_{$d->location_id}'>{$d->location_name}</button>";
    }

    $db->sql_query("SELECT *
                    FROM users
                    LEFT JOIN (
                        SELECT * 
                        FROM match_analyzes 
                        WHERE ma_id = '$ma_id'
                    ) AS ma_temp 
                        ON (
                            ma_temp.ma_trainee_id = users.user_id 
                            OR ma_temp.ma_trainee_partner_id = users.user_id
                        )
                    LEFT JOIN location2user 
                        ON location2user.location2user_user_id = users.user_id
                    LEFT JOIN locations 
                        ON location2user.location2user_location_id = locations.location_id
                    WHERE 
                        (user_hide != '1' OR ma_temp.ma_trainee_id > 0)
                        AND user_id > 1
                    ORDER BY 
                        locations.location_name, 
                        user_account;
    ");

    while($d = $db->get_next_res())
    {
      if($last_group!=$d->location_name)
      {
        if($last_group!=null) { print "</div>"; }
        print "<div class='location' id='div_location_{$d->location_id}'>";
        $last_group = $d->location_name;
      }
      $player = new user($d->user_id);
      print "<div class='" . ($d->user_id == $trainee_id ? 'activated' : 'deactivated') . "' id='img_{$d->user_id}_{$d->location_id}' ><img src='".$player->get_pic_path(true)."'/><br/>{$player->login}</div>";
    }

    print "</div><hr class='end_line'/><p/><div class='save'><button class='save_entry' id='save_entry_".$ma_id."'>Speichern</button></div>";
    break;

  default:
    throw new Exception("Ungültiger AJAX-Aufruf!<br/>".$_GET['ajax']);
    break;
}