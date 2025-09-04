<?php

use Tournament\tournament;

require_once('class_team.php');
require_once('class_tournament.php');

if(isset($_GET['user_id'])&& $_GET['user_id'] < 100000) { $myUser = new user($_GET['user_id']); } else { $myUser = new \user(); }

switch ($_GET['ajax']) {
  case 'load':
    print "<img src='inc/imgs/wuerfel.gif' class='wuefel'/>";
    break;

  case 'get_left_col':
    print $myTournament->html->get_users_from_tournament();
    break;

  case 'delete_permission':
    $html = "<h1>Willst du folgendes Turnier wirklich löschen?</h1>";
    $html.= "<h2>".$myTournament->title."</h2><h3>".nl2br($myTournament->description ?? '')."</h3>";
    $html.= "<div style='display:flex;flex-direction:row;gap:5px;'>";
    $html.= "<button id='delete_tournament' data-tournament-id='{$myTournament->id}' class='red'>Ja</button>";
    $html.= "<button id='abort_tournament_delete' class='green'>Nein</button>";
    $html.= "</div>";
    print $html;
    break;
  
    case 'add_as_seeded':  
      $seeding_pos = 1;
      foreach ($myTournament->arr_players as $player) {
        if($player->id==$_GET['user_id']) { $player_to_seed = $player; }
        if($player->seeding_no<99) { $seeding_pos++; }
      }
    
      if($myTournament->max_seeding_pos>=$seeding_pos)
      {
        $player_to_seed->seeding_no = $seeding_pos;
        $player_to_seed->save();
        print "OK";
      }
      else
      {
        print "Alle Setzplätze sind gefüllt";
      }
      break;

  case 'delete_last_seeding':
    $data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded < 99 ORDER BY group2user_seeded DESC LIMIT 1",array('t_id'=>$myTournament->id));
    if(isset($data->group2user_id)) { $db->sql_query("UPDATE group2user SET group2user_seeded=99 WHERE group2user_id= :d_id",array('d_id'=>$data->group2user_id)); }
    print "OK";
    break;

  case 'add_as_partner':
    try {
      print $myTournament->add_partner($_GET['user_id']);
      print "OK";
    } catch (\Throwable $th) {
      print $th;
    }
    break;

  case 'delete_team':
    $myTournament->arr_teams[$_GET['team_id']]->delete($_GET['team_id']);
    print "OK";
    break;

  case 'show':
    if(Count($myTournament->arr_rounds[$myTournament->curr_round-1]->arr_games)>0) {
      $curr_game = $myTournament->arr_rounds[$myTournament->curr_round-1]->arr_games[$_GET['court_id']-1];
      print "<img data-game-id='{$curr_game->id}' src='inc/php/court.php?action=fill&game_id={$curr_game->id}' class='img_court'/>";
    }
    else
    {
      print "<img src='inc/php/court.php?action=clear' class='img_court'/>";
    }
    break;

  case 'set_winner':
    $court_no = str_replace('court','',$_GET['court']);
    $curr_game = $myTournament->arr_rounds[$myTournament->curr_round-1]->arr_games[$court_no-1];
  
    if($_GET['winner_id']=='0')
    {
      $curr_game->winner = null;
      $curr_game->save();
    }
    else
    {
      if(substr($myTournament->system,0,6)=='Doppel')
      {
        if($curr_game->p1?->id==$_GET['winner_id']) { $winner2 = $curr_game->p3; }
        if($curr_game->p2?->id==$_GET['winner_id']) { $winner2 = $curr_game->p4; }
        if($curr_game->p3?->id==$_GET['winner_id']) { $winner2 = $curr_game->p1; }
        if($curr_game->p4?->id==$_GET['winner_id']) { $winner2 = $curr_game->p2; }
        $curr_game->winner = new user($_GET['winner_id']);
        $curr_game->winner2 = $winner2;
        $curr_game->save();
      }
      else
      {
        $curr_game->winner = $curr_game->round->tournament->arr_players[$_GET['winner_id']];
        $curr_game->save();
      }
  
      //Insert information in tournament log
      if($curr_game->p3)
      {
        if($curr_game->p1->id==$curr_game->winner->id) { $looser = $curr_game->p2; $looser2 = $curr_game->p4; } else { $looser = $curr_game->p1; $looser2 = $curr_game->p3; }
        $winner_txt = $curr_game->winner->login."/".$curr_game->winner2->login; 
        $looser_txt = $looser->login."/".$looser2->login;
      }
      else
      {
        if($curr_game->p1->id==$curr_game->winner->id) { $looser = $curr_game->p2; } else { $looser = $curr_game->p1; }
        $winner_txt = $curr_game->winner->login; $looser_txt = $looser->login;
      }
  
      $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->title.' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
    }
  
    //Show court
    if($curr_game->p1->id==1 OR $curr_game->p2->id==1) { $freilos=1; } else { $freilos=0; }
    print "<img src='inc/php/court.php?created_on=".time()."&action=fill&game_id={$curr_game->id}' class='img_court' data-freilos-field='{$freilos}' data-game-id='{$curr_game->id}'/>";
  
    //Update winners in group table
    if($myTournament->system=='Gruppenspiele') { $myTournament->update_winners(); }
    break;

  case 'set_points_and_winner':  
    $court_no = str_replace('court','',$_GET['court']);
    $curr_game = $myTournament->arr_rounds[$_GET['round']-1]->arr_games[$court_no-1];
  
    switch ($myTournament->counting) {
      case 'pointsOneSet':
        if($_GET['set1_p1']>$_GET['set1_p2']) { $winner = $curr_game->p1; $looser = $curr_game->p2; }
        if($_GET['set1_p1']<$_GET['set1_p2']) { $winner = $curr_game->p2; $looser = $curr_game->p1; }
        $curr_game->set1_p1_points = $_GET['set1_p1'];
        $curr_game->set1_p2_points = $_GET['set1_p2'];
        break;

      case 'official2sets':
      case '2sets11points':
      case '2setswinning':
        $wins_p1=0; $wins_p2=0;
        for($i=1;$i<4;$i++)
        {
          if($_GET['set'.$i.'_p1']>$_GET['set'.$i.'_p2']) { $wins_p1++; }
          if($_GET['set'.$i.'_p1']<$_GET['set'.$i.'_p2']) { $wins_p2++; }
        }
    
        if(max($wins_p1,$wins_p2)>0) {
          if($wins_p1>$wins_p2) { $winner = $curr_game->p1; $looser = $curr_game->p2; } else { $winner = $curr_game->p2; $looser = $curr_game->p1; }
        }
        $curr_game->set1_p1_points = $_GET['set1_p1'];
        $curr_game->set1_p2_points = $_GET['set1_p2'];
        $curr_game->set2_p1_points = $_GET['set2_p1'];
        $curr_game->set2_p2_points = $_GET['set2_p2'];
        $curr_game->set3_p1_points = $_GET['set3_p1'];
        $curr_game->set3_p2_points = $_GET['set3_p2'];
        break;
    }

    if(isset($winner))
    {
      //If double is played, set winner2
      if($curr_game->p3) { 
        if($curr_game->p1->id==$winner->id) { $winner2=$curr_game->p3; $looser2=$curr_game->p4; } else { $winner2 = $curr_game->p4; $looser2 = $curr_game->p3; } 
      }

      $curr_game->winner = $winner;
      if(isset($winner2)) { $curr_game->winner2 = $winner2; }

      //Insert information in tournament log
      if(isset($curr_game->p3)) {
        $winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
      } else {
        $winner_txt = $winner->login; $looser_txt = $looser->login;
      }
      $db->insert(array('news_tournament_id'=>$myTournament->id,'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->title.' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
    } else {
      $curr_game->winner = null;
    }
    $curr_game->save();

    if($curr_game->p1->id==1 OR $curr_game->p2->id==1) { $freilos=1; } else { $freilos=0; }
    print "<img src='inc/php/court.php?created_on=".time()."&action=fill&game_id={$curr_game->id}' class='img_court' data-freilos-field='{$freilos}' data-game-id='{$curr_game->id}'/>";
  
    if($myTournament->system=='Gruppenspiele')
    {
      $myTournament->calc->calc_ranking();
    }
  
    break;

  case 'clear':
    $myTournament->arr_rounds[$myTournament->curr_round-1]->arr_games[0]->delete();
    print "<img src='inc/php/court.php?action=clear' class='img_court'/>";
    break;

  case 'get_tournament_form':
    print $myTournament->html->get_tournament_form();
    break;


  case 'start_tournament':
    print $myTournament->start();
    break;

  case 'close_round':
    $all_games_ok = true;
    foreach ($myTournament->arr_rounds[$myTournament->curr_round-1]->arr_games as $game) {
      if(!isset($game->winner)) { $all_games_ok = false; break; }
    }
  
    if($all_games_ok)
    {
      $myTournament->arr_rounds[$myTournament->curr_round-1]->close();
      $myTournament->save();
      $myTournament->calc->calc_ranking();
      $new_round = $_GET['round']+1;
      print "OK;".$new_round;
    }
    else
    {
      print "Nicht alle Spiele eingetragen";
    }
    break;

  case 'reset_round':
    $db->sql_query("DELETE FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round > '".$_GET['round']."'");
    $db->sql_query("UPDATE games SET game_status='New' WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
    $myTournament->reload();
    $myTournament->calc->calc_ranking();
    $myTournament->save();
  
    print "OK;".$_GET['round'];
    break;

  case 'define_seeded_players':
    $db->update(array('group_status'=>'define_seeded_players'),'groups','group_id',$_GET['tournament_id']);
    print "OK";
    break;

  case 'close_tournament':
    $db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."'");
    $all_games_ok = true;
    while($d = $db->get_next_res())
    {
      if($d->game_status!='Closed')
      {
        if($myTournament->system=='Schoch') { $all_games_ok = false; break; }
        if($myTournament->system=='Gruppenspiele' AND $d->game_winner_id=='') { $all_games_ok = false; break; }
      }
    }
    if($all_games_ok)
    {
      if($myTournament->system=='Gruppenspiele')
      {
        $db->update(array('game_status'=>'Closed'),'games','game_group_id',$_GET['tournament_id']);
      }
      $db->update(array('group_status'=>'Closed'),'groups','group_id',$_GET['tournament_id']);
      print "OK";
    }
    else
    {
      print "Nicht alle Runden abgeschlossen";
    }
    break;

  case 'stopp_tournament':
    $myTournament->cancel();
    break;

  case 'define_games':
    $myTournament->calc->define_games();
    break;
          
  case 'get_all_users':
    print $myTournament->html->get_all_users($_GET['order_by']);
    break;

  case 'get_result':
    $court_no = str_replace('court','',$_GET['court']);
    $curr_game = $myTournament->arr_rounds[$_GET['round']-1]->arr_games[$court_no-1];
  
    $html = "<div class='result'>";
    $html.= "<h2>Wer hat in Runde {$_GET['round']} gewonnen?</h2>";
    $html.= "<table style='width:100%;'><tr>";
  
    //Double or single game
    if(isset($curr_game->p3)) { 
        $pic_width='50px'; 
        $user1_name = $curr_game->p1->login.'/'.$curr_game->p3->login; 
        $user2_name = $curr_game->p2->login.'/'.$curr_game->p4->login;
  
        $left_side = "<img class='img_user' data-user-id='{$curr_game->p1->id}' style='width:{$pic_width};cursor:pointer;' src='{$curr_game->p1->get_pic_path(true)}'>
                      <img class='img_user' data-user-id='{$curr_game->p1->id}' style='width:$pic_width;cursor:pointer;' src='".$curr_game->p3->get_pic_path(true)."'>"; 
        $right_side = "<img class='img_user' data-user-id='{$curr_game->p2->id}' style='width:{$pic_width};cursor:pointer;' src='{$curr_game->p2->get_pic_path(true)}'>
                      <img class='img_user' data-user-id='{$curr_game->p2->id}' style='width:$pic_width;cursor:pointer;' src='".$curr_game->p4->get_pic_path(true)."'>"; 
      } 
    else { 
      $pic_width='100px'; 
      $user1_name = $curr_game->p1->login; 
      $user2_name = $curr_game->p2->login; 
      $left_side = "<img class='img_user' data-user-id='{$curr_game->p1->id}' style='width:{$pic_width};cursor:pointer;' src='{$curr_game->p1->get_pic_path(true)}'>"; 
      $right_side = "<img class='img_user' data-user-id='{$curr_game->p2->id}' style='width:{$pic_width};cursor:pointer;' src='{$curr_game->p2->get_pic_path(true)}'>"; 
    }
  
    $html.= "<td style='text-align:center;'>{$left_side}<br/>{$user1_name}</td>";
  
    switch ($myTournament->counting) {
      case 'win':
        $html.= "<td class='abort' style='text-align:center;font-size:35pt;' rowspan='2'>VS</td>";
        break;
      
      case 'pointsOneSet':
        $html.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
        $myHTML = new HTML($db);
        $_POST[$curr_game->location.'_set1_p1']=$curr_game->set1_p1_points;
        $_POST[$curr_game->location.'_set1_p2']=$curr_game->set1_p2_points;
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p2',false);
        $html.= "<p/><button class='save_game green'>Speichern</button>";
        $html.= "</td>";
        break;
      
      case 'official2sets':
        $html.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
        $myHTML = new HTML($db);
        $_POST[$curr_game->location.'_set1_p1']=$curr_game->set1_p1_points;
        $_POST[$curr_game->location.'_set1_p2']=$curr_game->set1_p2_points;
        $_POST[$curr_game->location.'_set2_p1']=$curr_game->set2_p1_points;
        $_POST[$curr_game->location.'_set2_p2']=$curr_game->set2_p2_points;
        $_POST[$curr_game->location.'_set3_p1']=$curr_game->set3_p1_points;
        $_POST[$curr_game->location.'_set3_p2']=$curr_game->set3_p2_points;
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set2_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set2_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set3_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set3_p2',false);
        $html.= "<br/>";
        $html.= "<p/><button class='green' onclick=\"set_points_and_winner('{$curr_game->location}'); \">Speichern</button>";
        $html.= "</td>";
        break;
      
      case '2sets11points':
        $html.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
        $myHTML = new HTML($db);
        $_POST[$curr_game->location.'_set1_p1']=$curr_game->set1_p1_points;
        $_POST[$curr_game->location.'_set1_p2']=$curr_game->set1_p2_points;
        $_POST[$curr_game->location.'_set2_p1']=$curr_game->set2_p1_points;
        $_POST[$curr_game->location.'_set2_p2']=$curr_game->set2_p2_points;
        $_POST[$curr_game->location.'_set3_p1']=$curr_game->set3_p1_points;
        $_POST[$curr_game->location.'_set3_p2']=$curr_game->set3_p2_points;
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set1_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set1_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set2_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set2_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set3_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$curr_game->location.'_set3_p2',false);
        $html.= "<br/>";
        $html.= "<p/><button class='green' onclick=\"set_points_and_winner('{$curr_game->location}'); \">Speichern</button>";
        $html.= "</td>";
        break;
  
      case '2setswinning':
        $html.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
        $myHTML = new HTML($db);
        $_POST[$curr_game->location.'_set1_p1']=$curr_game->set1_p1_points;
        $_POST[$curr_game->location.'_set1_p2']=$curr_game->set1_p2_points;
        $_POST[$curr_game->location.'_set2_p1']=$curr_game->set2_p1_points;
        $_POST[$curr_game->location.'_set2_p2']=$curr_game->set2_p2_points;
        $_POST[$curr_game->location.'_set3_p1']=$curr_game->set3_p1_points;
        $_POST[$curr_game->location.'_set3_p2']=$curr_game->set3_p2_points;
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set1_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set2_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set2_p2',false);
        $html.= "<br/>";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set3_p1',false);
        $html.= ":";
        $html.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$curr_game->location.'_set3_p2',false);
        $html.= "<br/>";
        $html.= "<p/><button class='green' onclick=\"set_points_and_winner('{$curr_game->location}'); \">Speichern</button>";
        $html.= "</td>";
    
        break;
    }
    $html.= "<td style='text-align:center;'>{$right_side}<br/>{$user2_name}</td>";
    $html.= "</tr></table>";
    $html.= "</div>";
    print $html;
    break;

  case 'add_user':
    $db->sql_query("DELETE FROM group2user WHERE group2user_group_id = '$_GET[tournament_id]' AND group2user_user_id='$_GET[user_id]'");
    $db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>$_GET['user_id']),'group2user');
    print $db->last_inserted_id;
    break;

  case 'remove_user':
    $html = "";
    $db->sql_query("DELETE FROM group2user WHERE group2user_group_id = '$_GET[tournament_id]' AND group2user_user_id='$_GET[user_id]'");
    $db->sql_query("SELECT * FROM location2user WHERE location2user_user_id='$_GET[user_id]'");
    while($d = $db->get_next_res())
    {
      $html.= $d->location2user_location_id.",";
    }
    print substr($html,0,-1);
    break;

  case 'show_user_info':
    if($myTournament->system=='Doppel_fix') {
      print $myTournament->arr_teams[$_GET['user_id']]->get_tournament_info();
    } else {
      print $myTournament->arr_players[$_GET['user_id']]->get_tournament_info();
    }
    break;

  case 'new_user':
    print $myUser->get_user_form('min',false);
    break;

  case 'show_infos':
    print $myUser->get_user_form('all',true);
    break;

  case 'show_history':
    print $myUser->get_user_history();
    break;
    
  case 'delete_user':
    $db->delete('users','user_id',$_GET['user_id']);
    break;

  case 'add_user':
    $db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>$_GET['user_id']),'group2user');
    print $db->last_inserted_id;
    break;

  case 'delete_permission_user':
    $data = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='$_GET[user_id]'");
    $db->sql_query("SELECT MAX(group_title) as group_title, MAX(DATE_FORMAT(group_created,'%d.%m.%Y')) as c_date FROM games
                    LEFT JOIN groups on game_group_id = group_id
                    WHERE game_player1_id = '$_GET[user_id]' OR game_player2_id='$_GET[user_id]'
                    GROUP BY group_id");
    if($db->count()>0)
    {
      $html = "<h2>".$data->user_account." ist noch in folgenden Turnieren eingetragen und kann nicht gelöscht werden.</h2><p>";
      while($d = $db->get_next_res())
      {
        $html.= $d->c_date." / ".$d->group_title."</br>";
      }
      $html.= "<h2>Tipp: Sie können Spieler auch ausblenden</h2>";
    }
    else
    {
      $html = "<h1>Willst du ".$data->user_account." wirklich löschen?</h1>";
      $html.= "<div style='display:flex;flex-direction:row;gap:5px;'>";
      $html.= "<button class='red' onclick='delete_user($_GET[user_id]);'>Ja</button>";
      $html.= "<button class='green' onclick='window.location=\"$_SERVER[PHP_SELF]?user_id=$_GET[user_id]\"'>Nein</button>";
    }
    print $html;
    break;
  
  case 'delete_pic':
  case 'delete_user':
    $pic_path = "user_pics/".$_GET['user_id'].".png";
    $pic_path_t = "user_pics/".$_GET['user_id']."_t.png";
    $pic_path_star = "user_pics/".$_GET['user_id']."_stars.png";
    $pic_path_star_t = "user_pics/".$_GET['user_id']."_stars_t.png";
    if(file_exists($pic_path)) { unlink($pic_path); }
    if(file_exists($pic_path_t)) { unlink($pic_path_t); }
    if(file_exists($pic_path_star)) { unlink($pic_path_star); }
    if(file_exists($pic_path_star_t)) { unlink($pic_path_star_t); }
    if($_GET['ajax']=='delete_user')
    {
      $db->sql_query("DELETE FROM users WHERE user_id='$_GET[user_id]'");
    }
    break;
  
  case 'get_left_col_users':
    print $myTournament->html->get_all_users($_GET['order_by']);
    break;

  case 'save_user':
    $folder = 'user_pics/';
		$username = $_POST['user_account'];
		
		//Check if account exist
		if(isset($_POST['user_id']) && $_POST['user_id']>0) {
			$db->sql_query("SELECT * FROM users	WHERE user_account=:uacc AND user_id<>:uid",array('uacc'=>$_POST['user_account'],'uid'=>$_POST['user_id']));
		} else {
			$db->sql_query("SELECT * FROM users	WHERE user_account=:uacc",array('uacc'=>$_POST['user_account']));
		}

		//if exist, try to add with underline and number an check again for 10 times, after that a sql will appear
		if($db->count()>0) {
			$x = 1;
			while($x < 10)
			{
				$new_account = $_POST['user_account']."_".$x;
				$db->sql_query("SELECT * FROM users	WHERE user_account=:uid",array('uid'=>$new_account));
				if($db->count()==0) { $_POST['user_account'] = $new_account; break; }
				$x++;
			}
		}

		if(isset($_POST['user_id']) && $_POST['user_id']>0)
		{
			$birthday = $_POST['user_birthday'];
			$user_id = $_POST['user_id'];
			if(isset($_POST['user_hide'])) { $user_hide = '1'; } else { $user_hide = '0'; }
			//Update User
			if($birthday=='')
			{
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_birthday'=>null,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
			else
			{
				$birthday = $helper->date2iso($_POST['user_birthday']);
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_birthday'=>$birthday,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
		}
		else
		{
			$db->insert(array('user_account'=>$_POST['user_account'],'user_gender'=>$_POST['user_gender']),'users');
			$user_id = $db->last_inserted_id;
			$page->change_parameter('user_id',$user_id);
		}

		//Update Training locations
		//Check current locations
		$db->sql_query("SELECT * FROM location_permissions
							LEFT JOIN locations ON loc_permission_loc_id = location_id
							LEFT JOIN (SELECT * FROM location2user WHERE location2user_user_id='".$user_id."') as lj ON lj.location2user_location_id = locations.location_id
							WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
							ORDER BY location_name");
		while($d = $db->get_next_res())
		{
			if($d->location2user_id>0) 
			{ 
				//Shoud be checked, otherwise it was changed
				if(!isset($_POST['loc_'.$d->location_id])) 
				{ 
					//Not checked, therefor remove location
					$db->delete('location2user','location2user_id',$d->location2user_id);
				}
			}
			else
			{
				//Shoud NOT be checked, otherwise it was changed
				if(isset($_POST['loc_'.$d->location_id])) 
				{ 
					//Checked, therefor add location
					$db->insert(array('location2user_user_id'=>$user_id,'location2user_location_id'=>$d->location_id),'location2user');
				}
			}
		}
    if (!empty($_FILES['pictures']) && $_FILES['pictures']['error'][0] == 0) {
      foreach ($_FILES["pictures"]["error"] as $key => $error) 
      {
        if ($error == UPLOAD_ERR_OK) 
        {
          //Add user to DB
          if(file_exists($folder.$user_id.'.png')) { unlink($folder.$user_id.'.png'); }
  
          $tmp_name = $_FILES["pictures"]["tmp_name"][$key];
          // basename() kann Directory Traversal Angriffe verhindern; weitere
          // Gültigkeitsprüfung/Bereinigung des Dateinamens kann angebracht sein
          $name = basename($_FILES["pictures"]["name"][$key]);
          move_uploaded_file($tmp_name, $folder.$name);
  
          //Crop image to 1:1 ratio
          $filename = $folder.$name;
          $im = imagecreatefromstring(file_get_contents($filename));

          $exif = @exif_read_data($filename);
  
          if (isset($exif['Orientation']))
          {
            switch ($exif['Orientation'])
            {
            case 3:
              // Need to rotate 180 deg
                $im = imagerotate($im, 180, 0);
              break;
  
            case 6:
              // Need to rotate 90 deg clockwise
                $im = imagerotate($im, -90, 0);
              break;
  
            case 8:
              // Need to rotate 90 deg counter clockwise
                $im = imagerotate($im, 90, 0);
              break;
            }
          }

          
          $w = imagesx($im);
          $h = imagesy($im);
  
          $size = min($w,$h);
  
          if($w>$h) { $diff_x = ($w-$h)/2; } else { $diff_x = 0; }
          if($w<$h) { $diff_y = ($h-$w)/2; } else { $diff_y = 0; }
  
          $image_s = imagecrop($im, ['x' => $diff_x, 'y' => $diff_y, 'width' => $size, 'height' => $size]);
          imagedestroy($im);
  
          // $index = imagecolorclosest ( $image_s,  1,1,1 ); // get black color
          // imagecolorset($image_s,$index,10,10,10); // SET NEW COLOR
  
          //Round mask
          $width = imagesx($image_s);
          $height = imagesy($image_s);
  
          $newwidth = 500;
          $newheight = 500;
  
          $image = imagecreatetruecolor($newwidth, $newheight);
          imagealphablending($image, true);
          imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
  
  
          //Create mask (filled transparent circle)
          $mask = imagecreatetruecolor($newwidth, $newheight);
          $transparent = imagecolorallocate($mask, 255, 0, 0);
          imagecolortransparent($mask,$transparent);
          imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth-5, $newheight-5, $transparent);
  
          //Merge player picture in the mask with the transparent circle
          imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
          //Define another color for transparency
          $red = imagecolorallocate($mask, 255, 0, 0);
          imagecolortransparent($image,$red);
          //Fill it from the top left corner (like a fill function in a drawing app)
          imagefill($image, 0, 0, $red);
    
          //output, save and free memory
          imagepng($image,$folder.$user_id.'.png');
  
          //*********************************
          //Create Thumbnail
          //*********************************
  
          //Round mask
          $width = imagesx($image_s);
          $height = imagesy($image_s);
  
          $newwidth = 120;
          $newheight = 120;
  
          $image = imagecreatetruecolor($newwidth, $newheight);
          imagealphablending($image, true);
          imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
  
          //create masking
          $mask = imagecreatetruecolor($newwidth, $newheight);
  
          $transparent = imagecolorallocate($mask, 255, 0, 0);
          imagecolortransparent($mask,$transparent);
  
          imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth, $newheight, $transparent);
  
          $red = imagecolorallocate($mask, 0, 0, 0);
          imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
          imagecolortransparent($image,$red);
          imagefill($image, 0, 0, $red);

          //output, save and free memory
          imagepng($image,$folder.$user_id.'_t.png');
  
          $filename_new = 'uploads/'.microtime().$name;
          rename($folder.$name, $filename_new);
  
          imagedestroy($image);
          imagedestroy($image_s);
          imagedestroy($mask);
  
          }
        // Es wurde eine Datei hochgeladen
       }		
		}
		$myUser = new \user($user_id);
		$myUser->create_star_image();
    print $myUser->id;
    break;

  case 'reactivate_tournament':
    $myTournament = new tournament($_GET['tournament_id']);
    $myTournament->reactivate();
    print "OK".$myTournament->id;      
    break;

  case 'delete_tournament':
    try {
      $myTournament = new tournament($_GET['tournament_id']);
      $myTournament->delete();
      print "OK";
    } catch (\Throwable $th) {
      print $th->getMessage();
    }
    break;

  case 'save_tournament':
    if($_POST['tournament_title']!='')
    {
      $myTournament = new tournament($_POST['tournament_id']);
      $myTournament->title = $_POST['tournament_title'];
      $myTournament->description = $_POST['tournament_description'];
    
      if(isset($_POST['tournament_system'])) { $myTournament->system = $_POST['tournament_system']; }
      if(isset($_POST['tournament_counting'])) { $myTournament->counting = $_POST['tournament_counting']; }
      $myTournament->location = new location($_POST['created_by_location']);  
      $myTournament->save();
      print "OK".$myTournament->id;      
    }
    else
    {
      throw new \Exception("Titel muss ausgefüllt werden");
    }
    break;

}