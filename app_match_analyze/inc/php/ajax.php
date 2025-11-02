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

  case 'add_entry':
    $db->insert(array('ma_created_by'=>$_SESSION['login_user']->id),'match_analyzes');
    break;

  case 'confirm_delete':
    print "<h1 class='delete_confirmation'>Wirklich löschen?</h1>";
    print "<button class='delete' id='delete_".$_GET['ma_id']."'>Ja</button>";
    print "<button class='abort_delete'id='abort_delete_".$_GET['ma_id']."'>Nein</button>";
    break;

  case 'delete_entry':
    $db->delete('match_analyzes','ma_id',$_GET['ma_id']);
    break;

  case 'save_text':
    $db->update(array('ma_description'=>$_GET['text']),'match_analyzes','ma_id',$_GET['ma_id']);
    break;

  case 'save_opponent':
    $db->update(array('ma_opponent_name'=>$_GET['text']),'match_analyzes','ma_id',$_GET['ma_id']);
    break;

  case 'save_players':
    //Add selected players
    $arr_players = explode(';',$_GET['players']);
    if(count($arr_players)>0) { $db->update(array('ma_trainee_id'=>$arr_players[0]),'match_analyzes','ma_id',$_GET['ma_id']); } else { $db->update(array('ma_trainee_id'=>null),'match_analyzes','ma_id',$_GET['ma_id']); }
    if(count($arr_players)>1) { $db->update(array('ma_trainee_partner_id'=>$arr_players[1]),'match_analyzes','ma_id',$_GET['ma_id']); } else { $db->update(array('ma_trainee_partner_id'=>null),'match_analyzes','ma_id',$_GET['ma_id']); }  
    break;

  case 'save_trainer':
    //Add selected players
    $arr_trainer = explode(';',$_GET['trainer_id']);
    foreach($arr_trainer as $trainer)
    {
      if($trainer > 0) { 
        $db->update(array('ma_created_on'=>$_GET['journal_date'],'ma_created_by'=>$trainer),'match_analyzes','ma_id',$_GET['ma_id']); 
      }
    }
    break;

  case 'show_text':
    $d = $db->sql_query_with_fetch("SELECT * FROM match_analyzes WHERE ma_id='".$_GET['ma_id']."'");
    print "<textarea id='training_description'>".$d->ma_description."</textarea>";
    print "<br/><button class='save_text' id='save_text_".$d->ma_id."'>Speichern</button>";
    break;

  case 'show_opponent':
    $d = $db->sql_query_with_fetch("SELECT * FROM match_analyzes WHERE ma_id='".$_GET['ma_id']."'");
    print "Name des Gegner / der Gegner<br/><input type='text' id='opponent_name' value='{$d->ma_opponent_name}'/>";
    print "<p/><button class='save_text' id='save_opponent_".$d->ma_id."'>Speichern</button>";
    break;

  case 'show_players':
    $last_group = null;
    //$db->sql_query("SELECT * FROM journal2user WHERE journal2user_ma_id='".$_GET['ma_id']."'");
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
                        WHERE ma_id = '$_GET[ma_id]'
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
      if($d->ma_trainee_id!='')
      {
        $player = new user($d->user_id);
        print "<div class='activated' id='img_{$d->user_id}_{$d->location_id}' ><img src='".$player->get_pic_path(true)."'/><br/>{$player->login}</div>";
      }
      else
      {
        $player = new user($d->user_id);
        print "<div class='deactivated' id='img_{$d->user_id}_{$d->location_id}'><img src='".$player->get_pic_path(true)."'/><br/>{$player->login}</div>";
      }
    }
    print "</div>";
    print "<hr class='end_line' /><p/><div class='save'><button class='save_players' id='save_players_{$_GET['ma_id']}'>Speichern</button></div>";
    print "<br/><br/><br/><br/>";  
    break;

  case 'show_trainer':
    $d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(ma_created_on,'%Y-%m-%d')  as curr_date FROM match_analyzes WHERE ma_id='$_GET[ma_id]'");
    print "<h1>Datum</h1>";
    print "<input id='journal_date' type='date' value='".$d->curr_date."'/>";
    print "<hr/>";
    print "<h1>Trainer</h1>";
    $db->sql_query("SELECT * FROM users 
                    LEFT JOIN (SELECT * FROM match_analyzes WHERE ma_id='$_GET[ma_id]') as ma_temp ON ma_temp.ma_created_by = users.user_id
                    LEFT JOIN location2user ON location2user.location2user_user_id = users.user_id 
                    LEFT JOIN locations ON location2user.location2user_location_id = locations.location_id 
                    WHERE user_hide!='1' AND user_id>1 AND locations.location_name = '_Trainer'
                    ORDER BY locations.location_name, user_account
    ");

    while($d = $db->get_next_res())
    {
      if($d->ma_created_by!='')
      {
        $player = new user($d->ma_created_by);
        print "<div class='activated' id='img_{$d->user_id}'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>";
      }
      else
      {
        $player = new user($d->user_id);
        print "<div class='deactivated' id='img_{$d->user_id}'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>";
      }
    }
    print "<hr class='end_line'/><p/><div class='save'><button class='save_trainer' id='save_trainer_".$_GET['ma_id']."'>Speichern</button></div>";
    break;

  default:
    throw new Exception("Ungültiger AJAX-Aufruf!<br/>".$_GET['ajax']);
    break;
}

//************************************************************************************




