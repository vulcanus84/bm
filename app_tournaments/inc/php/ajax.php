<?php

require_once('class_team.php');



if($_GET['ajax']=='load') { print "<img src='wuerfel.gif' class='wuefel'/>"; }
if($_GET['ajax']=='get_left_col') { print $myTournament->get_users_from_tournament(); }

if($_GET['ajax']=='delete_permission')
{
  $html = "<h1>Willst du folgendes Turnier wirklich löschen?</h1>";
  $html.= "<h2>".$myTournament->title."</h2><h3>".nl2br($myTournament->description)."</h3>";
  $html.= "<button onclick='window.location=\"index.php?action=delete_tournament&tournament_id=".$myTournament->id."\"' style='background-color:red;'>Ja</button>";
  $html.= "<button onclick='window.location=\"index.php\"'>Nein</button>";
  print $html;
}

if($_GET['ajax']=='add_as_seeded')
{
  $max_seeding_pos = round($myTournament->get_number_of_players()/2,0);
  if($max_seeding_pos>8) { $max_seeding_pos=8; }

  $db->sql_query("SELECT * FROM group2user
                  WHERE group2user_group_id= :t_id AND group2user_seeded < 99",array('t_id'=>$myTournament->id));
  $seeding_pos = $db->count()+1;
  if($max_seeding_pos>=$seeding_pos)
  {
    $data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_user_id= :u_id",array('t_id'=>$myTournament->id,'u_id'=>$_GET['user_id']));
    $db->update(array('group2user_seeded'=>$seeding_pos),'group2user','group2user_id',$data->group2user_id);
    print "OK";
  }
  else
  {
    print "Alle Setzplätze sind gefüllt";
  }
}

if($_GET['ajax']=='delete_last_seeding')
{
  $data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded < 99 ORDER BY group2user_seeded DESC LIMIT 1",array('t_id'=>$myTournament->id));
  if(isset($data->group2user_id)) { $db->sql_query("UPDATE group2user SET group2user_seeded=99 WHERE group2user_id= :d_id",array('d_id'=>$data->group2user_id)); }
  print "OK";
}


if($_GET['ajax']=='add_as_partner')
{
  try {
    print $myTournament->add_partner($_GET['user_id']);
    print "OK";
  } catch (\Throwable $th) {
    print $th;
  }
}

if($_GET['ajax']=='delete_team')
{
  foreach ($myTournament->arr_teams as $key => $team) {
    if($team->arr_players[0]->id==$_GET['user_id'] OR $team->arr_players[1]->id==$_GET['user_id']) {
      $myTournament->arr_teams[$key]->delete($key);
      break;
    }
  }
  print "OK";
}


if($_GET['ajax']=='show')
{
  $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
  if($db->count()>0)
  {
    $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
    print "<img src='court.php?action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
  }
  else
  {
    print "<img src='court.php?action=clear' class='court'/>";
  }
}

if($_GET['ajax']=='set_winner')
{
  $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
  if($_GET['winner_id']=='0')
  {
    $db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL'),'games','game_id',$data->game_id);
  }
  else
  {
    if(substr($myTournament->system,0,6)=='Doppel')
    {
      if($data->game_player1_id==$_GET['winner_id']) { $winner2 = $data->game_player3_id; }
      if($data->game_player2_id==$_GET['winner_id']) { $winner2 = $data->game_player4_id; }
      if($data->game_player3_id==$_GET['winner_id']) { $winner2 = $data->game_player1_id; }
      if($data->game_player4_id==$_GET['winner_id']) { $winner2 = $data->game_player2_id; }
      $db->update(array('game_winner_id'=>$_GET['winner_id'],'game_winner2_id'=>$winner2),'games','game_id',$data->game_id);
    }
    else
    {
      $db->update(array('game_winner_id'=>$_GET['winner_id']),'games','game_id',$data->game_id);
    }

    //Insert information in tournament log
    $winner = new user($_GET['winner_id']);
    $game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
    if(substr($myTournament->system,0,6)=='Doppel')
    {
      $winner2 = new user($winner2);
      if($game->game_player1_id==$_GET['winner_id']) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
      $winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
    }
    else
    {
      if($game->game_player1_id==$_GET['winner_id']) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
      $winner_txt = $winner->login; $looser_txt = $looser->login;
    }

    $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
  }
  $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

  //Show court
  if($db->count()>0)
  {
    print "<img src='court.php?created_on=".time()."&action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
  }
  else
  {
    print "<img src='court.php?action=clear' class='court' onclick='check_result($_GET[court_id]);'/>";
  }

  //Update winners in group table
  if($myTournament->system=='Gruppenspiele')
  {
    $myTournament->update_winners();
  }
}

if($_GET['ajax']=='set_points_and_winner')
{
  $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

  if($myTournament->counting=='pointsOneSet')
  {
    $winner_id=0;
    if($_GET['set1_p1']>$_GET['set1_p2']) { $winner_id = $data->game_player1_id; $looser = new user($data->game_player2_id); }
    if($_GET['set1_p1']<$_GET['set1_p2']) { $winner_id = $data->game_player2_id; $looser = new user($data->game_player1_id); }

    //If double is played, set winner2
    if($data->game_player3_id>0) { if($data->game_player1_id==$winner_id) { $winner2_id=$data->game_player3_id; } else { $winner2_id = $data->game_player4_id; } } else { $winner2_id=null; }

    if($winner_id!=0)
    {
      $db->update(array('game_winner_id'=>$winner_id,'game_winner2_id'=>$winner2_id,'game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2']),'games','game_id',$data->game_id);

      //Insert information in tournament log
      $winner = new user($winner_id);
      $game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
      if($data->game_player3_id>0)
      {
        $winner2 = new user($winner2_id);
        if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
        $winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
      }
      else
      {
        if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
        $winner_txt = $winner->login; $looser_txt = $looser->login;
      }
      $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');

    }
    else
    {
      $db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL','game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2']),'games','game_id',$data->game_id);
    }
  }


  if($myTournament->counting=='official2sets' OR $myTournament->counting=='2sets11points' OR $myTournament->counting=='2setswinning')
  {
    $wins_p1=0; $wins_p2=0;
    for($i=1;$i<4;$i++)
    {
      if($_GET['set'.$i.'_p1']>$_GET['set'.$i.'_p2']) { $wins_p1++; }
      if($_GET['set'.$i.'_p1']<$_GET['set'.$i.'_p2']) { $wins_p2++; }
    }

    if(max($wins_p1,$wins_p2)>0)
    {
      if($wins_p1>$wins_p2) { $winner_id=$data->game_player1_id; $looser = new user($data->game_player2_id); } else { $winner_id=$data->game_player2_id; $looser = new user($data->game_player1_id); }

      //If double is played, set winner2
      if($data->game_player3_id>0) { if($data->game_player1_id==$winner_id) { $winner2_id=$data->game_player3_id; } else { $winner2_id = $data->game_player4_id; } } else { $winner2_id=null; }

      $db->update(array('game_winner_id'=>$winner_id,'game_winner2_id'=>$winner2_id,'game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2'],'game_set2_p1'=>$_GET['set2_p1'],'game_set2_p2'=>$_GET['set2_p2'],'game_set3_p1'=>$_GET['set3_p1'],'game_set3_p2'=>$_GET['set3_p2']),'games','game_id',$data->game_id);

      //Insert information in tournament log
      $winner = new user($winner_id);
      $game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
      if($data->game_player3_id>0)
      {
        $winner2 = new user($winner2_id);
        if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
        $winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
      }
      else
      {
        if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
        $winner_txt = $winner->login; $looser_txt = $looser->login;
      }
      $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
    }
    else
    {
      $db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL','game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2'],'game_set2_p1'=>$_GET['set2_p1'],'game_set2_p2'=>$_GET['set2_p2'],'game_set3_p1'=>$_GET['set3_p1'],'game_set3_p2'=>$_GET['set3_p2']),'games','game_id',$data->game_id);
    }
  }

  $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

  if($db->count()>0)
  {
    print "<img src='court.php?created_on=".time()."&action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
  }
  else
  {
    print "<img src='court.php?action=clear' class='court' onclick='check_result($_GET[court_id]);'/>";
  }
  if($myTournament->system=='Gruppenspiele')
  {
    $myTournament->update_winners();
  }

}

if($_GET['ajax']=='clear')
{
  $db->sql_query("DELETE FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='".$_GET['round']."'");
  print "<img src='court.php?action=clear' class='court'/>";
}
if($_GET['ajax']=='get_tournament_form') { print $myTournament->get_tournament_form(); }
if($_GET['ajax']=='delete_user') { print $myTournament->get_users_to_delete($_GET['tournament_id'],$db); }
if($_GET['ajax']=='start_tournament') { print $myTournament->start(); }
if($_GET['ajax']=='close_round')
{
  $db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
  $all_games_ok = true;
  while($d = $db->get_next_res())
  {
    if($d->game_winner_id==0) { $all_games_ok = false; break; }
  }
  if($all_games_ok)
  {
    $d = $db->sql_query("UPDATE games SET game_status='Closed' WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
    $new_round = $_GET['round']+1;
    $db->update(array('group_round'=>$new_round),'groups','group_id',$_GET['tournament_id']);
    $myTournament->calc_BHZ();
    print "OK;".$new_round;
  }
  else
  {
    print "Nicht alle Spiele eingetragen";
  }
}

if($_GET['ajax']=='reset_round')
{
  $db->sql_query("DELETE FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round > '".$_GET['round']."'");
  $db->sql_query("UPDATE games SET game_status='New' WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
  $myTournament->calc_BHZ();

  print "OK;".$_GET['round'];
}

if($_GET['ajax']=='define_seeded_players')
{
  $db->update(array('group_status'=>'define_seeded_players'),'groups','group_id',$_GET['tournament_id']);
  print "OK";
}

if($_GET['ajax']=='close_tournament')
{

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
}

if($_GET['ajax']=='stopp_tournament')
{
  $db->sql_query("DELETE FROM games WHERE game_group_id='$_GET[tournament_id]'");
  $db->sql_query("DELETE FROM news WHERE news_tournament_id='$_GET[tournament_id]'");
  $db->sql_query("DELETE FROM group2user WHERE group2user_user_id='1' AND group2user_group_id='$_GET[tournament_id]'"); //Remove Dummy user if exist
  $db->update(array('group_status'=>'New','group_round'=>'1'),'groups','group_id',$_GET['tournament_id']);
  $db->update(array('group2user_partner_id'=>'NULL'),'group2user','group2user_group_id',$_GET['tournament_id']); //Reset Partner definition (only used in "Doppel_fix")
  //$db->update(array('group2user_seeded'=>'99'),'group2user','group2user_group_id',$_GET['tournament_id']); //Reset Partner definition (only used in "Doppel_fix")
  $myTournament->calc_BHZ();
}

if($_GET['ajax']=='save_tournament')
{
  $myTournament = new tournament();
  $myTournament->title = $_GET['name'];
  $myTournament->description = $_GET['desc'];
  $myTournament->save();
  print $myTournament->id;
}

if($_GET['ajax']=='define_games')
{
  //Tournament with Freilos
  $with_freilos=false;
  $db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='1'");
  if($db->count()==1) { $with_freilos=true; $myLogger->write_to_log("Tournament","Freilos vorhanden"); }

  if(substr($myTournament->system,0,6)=='Doppel')
  {
    $users_on_court = null;
    $arr_ids = null;
    $arr_opponents = null;
    $w_str = null;
    $w_str2 = null;
    $db2 = clone($db);
    $db3 = clone($db);
    $court_nr=1;

    //Get players order by wins
    $db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY group2user_wins DESC,group2user_BHZ DESC, RAND()");
    if($myTournament->system=='Doppel_dynamisch')
    {
      $p1=null;$p2=null;$p3=null;$p4=null;
      //If a single should be played, define first

      if($db->count()%4 != 0 OR $with_freilos)
      {
        $myLogger->write_to_log("Tournament","Anzahl Teilnehmer nicht durch 4 teilbar");
        //select all player, but not Freilos
        $w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1'";

        //get all single games of current tournament and extract users
        $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id IS NULL AND (game_player1_id='1' OR game_player2_id='1')");
        if($db3->count()>0)
        {
          while($d = $db3->get_next_res())
          {
            $w_str .= " AND group2user_user_id!='".$d->game_player1_id."' AND group2user_user_id!='".$d->game_player2_id."'";
          }
        }

        //if Freilos is activated, set always one of the single players with this user
        if($with_freilos) { $p1 = '1'; $limit = '1'; } else { $limit = '2'; }
        $db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, rand() LIMIT $limit");
        //if there are no more player which are not played single, choose randomly
        if($db3->count()<$limit) 
        { 
          $myLogger->write_to_log("Tournament","Keine Person fürs Freilos gefunden, suche per Zufall jemanden aus");
          $db3->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY rand() LIMIT $limit"); 
        }
        else
        {
          $myLogger->write_to_log("Tournament","Freilos sauber zugeteilt, folgender SQL String wurde verwendet");
          $myLogger->write_to_log("Tournament","SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, rand() LIMIT $limit");
        }
        while($d = $db3->get_next_res())
        {
          if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
        }

        //insert games and set players on court, that they are not available anymore for the other games
        if($limit==1) { $win_id = $p2; } else { $win_id = null; }
        $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_winner_id'=>$win_id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
        $court_nr++;
        $users_on_court.= $p1.'/'.$p2.'/';
        $p1=null;$p2=null;$p3=null;$p4=null;

        $open_players = count($myTournament->arr_players) - 2;
        //Do we need a single game?
        if($open_players % 4 > 0)
        {
          $myLogger->write_to_log("Tournament","Einzelspiel definieren");
          //Combine where-string which excludes all player, which are allready assigned
          $w_str = "WHERE group2user_group_id='$_GET[tournament_id]'";
          $arr_users = explode('/',$users_on_court);
          foreach($arr_users as $user)
          {
            if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
          }

          //get all single games of current tournament and extract users
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id IS NULL AND game_player1_id!='1' AND game_player2_id!='1'");
          if($db3->count()>0)
          {
            while($d = $db3->get_next_res())
            {
              $w_str .= " AND group2user_user_id!='".$d->game_player1_id."' AND group2user_user_id!='".$d->game_player2_id."'";
            }
          }

          $db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, group2user_BHZ ASC, rand() LIMIT 2");
          if($db3->count()==2)
          {
            //Found two players with no single games
            while($d = $db3->get_next_res())
            {
              if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
            }
            $myLogger->write_to_log("Tournament","2 Spieler gefunden, die noch keine Einzel gespielt haben");

            //insert games and set players on court, that they are not available anymore for the other games
            $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
            $court_nr++;
            $users_on_court.= $p1.'/'.$p2.'/';
            $p1=null;$p2=null;$p3=null;$p4=null;
          }
          else
          {
            $limit = 2;
            if($db3->count()==1)
            {
              //Found one player with no single games
              $d = $db3->get_next_res();
              $p1 = $d->group2user_user_id;
              $limit = 1;
              $myLogger->write_to_log("Tournament","1 Spieler gefunden, der noch keine Einzel gespielt hat, suche noch jemanden per Zufall");
            }
            else
            {
              $myLogger->write_to_log("Tournament","Keine Spieler gefunden, die noch keine Einzel gespielt haben, suche per Zufall aus");
            }
            $db3->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1' AND group2user_user_id!=$p1 ORDER BY rand() LIMIT $limit");								
            //Get another player randomly
            while($d = $db3->get_next_res())
            {
              if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
            }
            //insert games and set players on court, that they are not available anymore for the other games
            $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
            $court_nr++;
            $users_on_court.= $p1.'/'.$p2.'/';
            $p1=null;$p2=null;$p3=null;$p4=null;
          }
        }
      }

      while($d = $db->get_next_res())
      {
        $w_str2 = null;
        //Check if current player allready is assigned to a game
        if(strpos($users_on_court,$d->group2user_user_id)===false)
        {
          $curr_user_id = $d->group2user_user_id;
          if($p1==null) { $p1 = $curr_user_id; } else { $p2 = $curr_user_id; }
          //Combine where-string which excludes all player, which are allready assigned
          $w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$curr_user_id'";
          $arr_users = explode('/',$users_on_court);
          foreach($arr_users as $user)
          {
            if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
          }

          //Combine where-string with all players which are allready partner of current player
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player1_id = '$curr_user_id'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player3_id."'"; }
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player2_id = '$curr_user_id'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player4_id."'"; }
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id = '$curr_user_id'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player4_id = '$curr_user_id'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }


          //Search best opponent which is not allready assigned and has not allready played against current player
          $db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins ASC, rand() LIMIT 1");

          //If someone has been found take it
          if($db3->count()==1)
          {
            $d3 = $db3->get_next_res();
            if($p3==null) { $p3 = $d3->group2user_user_id; } else { $p4 = $d3->group2user_user_id; }
            $users_on_court.= $curr_user_id.'/'.$d3->group2user_user_id.'/';
          }
          //if nobody has been found, take one of the not allready assigned opponent by random
          else
          {
            $db3->sql_query("SELECT * FROM group2user $w_str ORDER BY rand() LIMIT 1");
            if($db3->count()==1)
            {
              $d3 = $db3->get_next_res();
              if($p3==null) { $p3 = $d3->group2user_user_id; } else { $p4 = $d3->group2user_user_id; }
              $users_on_court.= $curr_user_id.'/'.$d3->group2user_user_id.'/';
            }
          }
          if($p4>0)
          {
            $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_player3_id'=>$p3,'game_player4_id'=>$p4,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
            $court_nr++;
            $p1=null;$p2=null;$p3=null;$p4=null;
          }
        }
      }
    }

    if($myTournament->system=='Doppel_fix')
    {
      $arr_on_court = array();

      while($d = $db->get_next_res())
      {
        $w_str2 = null;
        //Check if current player allready is assigned to a game
        if(!in_array($d->group2user_user_id,$arr_on_court))
        {
          $p1 = $d->group2user_user_id;
          $arr_on_court[] = $p1;
          $d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p1'");
          $p3 = $d3->group2user_partner_id;
          $arr_on_court[] = $p3;
          //Combine where-string which excludes all player, which are allready assigned
          $w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$p1' AND group2user_user_id!='$p3'";
          foreach($arr_on_court as $user)
          {
            if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
          }

          //Combine where-string with all players which are allready opponents of current player
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND (game_player1_id = '$p1' OR game_player3_id = '$p1')");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."' AND group2user_user_id != '".$d3->game_player4_id."'"; }
          $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND (game_player2_id = '$p1' OR game_player4_id = '$p1')");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."' AND group2user_user_id != '".$d3->game_player3_id."'"; }

          //Search best opponent which is not allready assigned and has not allready played against current player
          $db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins DESC, rand() LIMIT 1");

          //If someone has been found take it
          if($db3->count()==1)
          {
            $d3 = $db3->get_next_res();
            $p2 = $d3->group2user_user_id;
            $arr_on_court[] = $p2;
            $d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p2'");
            $p4 = $d3->group2user_partner_id;
            $arr_on_court[] = $p4;
          }
          //if nobody has been found, take one of the not allready assigned opponent by random
          else
          {
            $db3->sql_query("SELECT * FROM group2user $w_str ORDER BY rand() LIMIT 1");
            if($db3->count()==1)
            {
              $d3 = $db3->get_next_res();
              $p2 = $d3->group2user_user_id;
              $arr_on_court[] = $p2;
              $d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p2'");
              $p4 = $d3->group2user_partner_id;
              $arr_on_court[] = $p4;
            }
          }

          //Insert players
          $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_player3_id'=>$p3,'game_player4_id'=>$p4,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
          $court_nr++;
          $p1=null;$p2=null;$p3=null;$p4=null;
        }
      }
    }

    print "OK";
  }
  else
  {
    //Order to define games
    $db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='{$myTournament->id}' ORDER BY group2user_wins DESC, group2user_seeded ASC, RAND()");
    if($db->count()>1)
    {
      if(isset($_GET['round']))
      {
        $users_on_court = null;
        $my_user = null;
        $w_str = null;
        $w_str2 = null;
        $db2 = clone($db);
        $db3 = clone($db);
        $court_nr=1;

        //Freilos Handling
        $db2->sql_query("SELECT * FROM group2user WHERE group2user_group_id='{$myTournament->id}' AND group2user_user_id='1'");
        if($db2->count()>0)
        {
          $w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1'";

          $db3->sql_query("SELECT * FROM games WHERE game_group_id='{$myTournament->id}' AND game_player1_id = '1'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }

          $db3->sql_query("SELECT * FROM games WHERE game_group_id='{$myTournament->id}' AND game_player2_id = '1'");
          while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }

          $data = $db3->sql_query_with_fetch("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_seeded DESC, group2user_wins ASC, group2user_BHZ ASC, group2user_FBHZ ASC, rand() LIMIT 1");

          $my_user = new user(1);
          $opponent = new user($data->group2user_user_id);
          $curr_game = $myTournament->arr_rounds[$myTournament->curr_round]->add_game();
          $curr_game->p1 = $my_user;
          $curr_game->p2 = $opponent;
          $curr_game->winner = $opponent;

          switch ($myTournament->counting) {
            case '2setswinning':
            case 'official2sets':
              $curr_game->set1_p1_points = 10;
              $curr_game->set1_p2_points = 21;
              $curr_game->set2_p1_points = 10;
              $curr_game->set2_p2_points = 21;
              break;

            case '2sets11points':
              $curr_game->set1_p1_points = 5;
              $curr_game->set1_p2_points = 11;
              $curr_game->set2_p1_points = 5;
              $curr_game->set2_p2_points = 11;
              break;

            default:
              # code...
              break;
          }
          $curr_game->save();
          $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>'Freilos ausgelost','news_text'=>"Im Turnier {$myTournament->title} hat {$opponent->login} das Freilos bekommen."),'news');

          $users_on_court.= $my_user->id.'/'.$opponent->id.'/';
          $opponent = null;
          $my_user = null;

          $court_nr++;
        }

        $x = $users_on_court;

        while($d = $db->get_next_res())
        {
          $w_str2 = null;
          if(strpos($users_on_court,$d->group2user_user_id)===false)
          {
            $curr_user_id = $d->group2user_user_id;
            $w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$curr_user_id'";
            $arr_users = explode('/',$users_on_court);
            foreach($arr_users as $user)
            {
              if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
            }

            $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player1_id = '$curr_user_id'");
            while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }
            $db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player2_id = '$curr_user_id'");
            while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }
            $db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins DESC, group2user_seeded DESC, rand() LIMIT 1");

            if($db3->count()==1)
            {
              $d3 = $db3->get_next_res();
              $opponent_user_id = $d3->group2user_user_id;

              $my_user = new user($curr_user_id);
              $opponent = new user($opponent_user_id);
              $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');

              $users_on_court.= $my_user->id.'/'.$opponent->id.'/';
              $opponent = null;
              $my_user = null;

              $court_nr++;
            }
            else
            {
              $db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins DESC, group2user_seeded DESC, rand() LIMIT 1");
              if($db3->count()==1)
              {
                $d3 = $db3->get_next_res();
                $opponent_user_id = $d3->group2user_user_id;

                $my_user = new user($curr_user_id);
                $opponent = new user($opponent_user_id);
                $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');

                $users_on_court.= $my_user->id.'/'.$opponent->id.'/';
                $opponent = null;
                $my_user = null;

                $court_nr++;
              }
            }
          }
        }
        $db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>'Neue Runde ausgelost','news_text'=>"Im Turnier {$myTournament->title} wurde eine neue Runde ausgelost."),'news');
        print "OK";
      }
    }
    else
    {
      print "Zu wenig Teilnehmer";
    }
  }
} //end of define games

if($_GET['ajax']=='get_all_users') { print $myTournament->get_all_users($db); }

if($_GET['ajax']=='get_result')
{
  $myTournament->load_by_game_id($_GET['game_id']);

  $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_round='$_GET[round]' AND game_group_id='$_GET[tournament_id]' AND game_location='$_GET[court]'");
  $u1 = new user($data->game_player1_id);
  $u2 = new user($data->game_player2_id);
  if($data->game_player3_id>0) { $u3 = new user($data->game_player3_id); }
  if($data->game_player4_id>0) { $u4 = new user($data->game_player4_id); }

  $x = "<div class='result'>";
  $x.= "<h2>Wer hat in Runde ".$_GET['round']." gewonnen?</h2>";
  $x.= "<table style='width:100%;'><tr>";
  if($myTournament->counting=='win')
  {
    if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
    if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "<td style='text-align:center;font-size:35pt;' rowspan='2' onclick='set_winner(0,$data->game_location);'>VS</td>";

    if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
    if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "</tr><tr>";
    $x.= "<td style='text-align:center;'>".$user1_name."</td>";
    $x.= "<td style='text-align:center;'>".$user2_name."</td>";
    $x.= "</tr></table>";
  }
  if($myTournament->counting=='pointsOneSet')
  {
    if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
    if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
    $myHTML = new HTML($db);
    $_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
    $_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
    $x.= "<p/><button onclick=\"set_points_and_winner('pointsOneSet','".$data->game_location."',$('#".$data->game_location."_set1_p1').val(),$('#".$data->game_location."_set1_p2').val());\">Speichern</button>";
    $x.= "</td>";

    if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
    if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "</tr><tr>";
    $x.= "<td style='text-align:center;'>".$user1_name."</td>";
    $x.= "<td style='text-align:center;'>".$user2_name."</td>";
    $x.= "</tr></table>";
  }
  if($myTournament->counting=='official2sets')
  {
    if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
    if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
    $myHTML = new HTML($db);
    $_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
    $_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
    $_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
    $_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
    $_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
    $_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p2',false);
    $x.= "<br/>";
    $x.= "<p/><button onclick=\"set_points_and_winner('official2sets','".$data->game_location."',
            $('#".$data->game_location."_set1_p1').val(),
            $('#".$data->game_location."_set1_p2').val(),
            $('#".$data->game_location."_set2_p1').val(),
            $('#".$data->game_location."_set2_p2').val(),
            $('#".$data->game_location."_set3_p1').val(),
            $('#".$data->game_location."_set3_p2').val()
            ); \">Speichern</button>";
    $x.= "</td>";

    if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
    if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "</tr><tr>";
    $x.= "<td style='text-align:center;'>".$user1_name."</td>";
    $x.= "<td style='text-align:center;'>".$user2_name."</td>";
    $x.= "</tr></table>";
  }

  if($myTournament->counting=='2sets11points')
  {
    if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
    if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
    $myHTML = new HTML($db);
    $_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
    $_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
    $_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
    $_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
    $_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
    $_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set1_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set1_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set2_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set2_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set3_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set3_p2',false);
    $x.= "<br/>";
    $x.= "<p/><button onclick=\"set_points_and_winner('2sets11points','".$data->game_location."',
            $('#".$data->game_location."_set1_p1').val(),
            $('#".$data->game_location."_set1_p2').val(),
            $('#".$data->game_location."_set2_p1').val(),
            $('#".$data->game_location."_set2_p2').val(),
            $('#".$data->game_location."_set3_p1').val(),
            $('#".$data->game_location."_set3_p2').val()
            ); \">Speichern</button>";
    $x.= "</td>";

    if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
    if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "</tr><tr>";
    $x.= "<td style='text-align:center;'>".$user1_name."</td>";
    $x.= "<td style='text-align:center;'>".$user2_name."</td>";
    $x.= "</tr></table>";
  }

  if($myTournament->counting=='2setswinning')
  {
    if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
    if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
    $myHTML = new HTML($db);
    $_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
    $_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
    $_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
    $_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
    $_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
    $_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p2',false);
    $x.= "<br/>";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p1',false);
    $x.= ":";
    $x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p2',false);
    $x.= "<br/>";
    $x.= "<p/><button onclick=\"set_points_and_winner('2setswinning','".$data->game_location."',
            $('#".$data->game_location."_set1_p1').val(),
            $('#".$data->game_location."_set1_p2').val(),
            $('#".$data->game_location."_set2_p1').val(),
            $('#".$data->game_location."_set2_p2').val(),
            $('#".$data->game_location."_set3_p1').val(),
            $('#".$data->game_location."_set3_p2').val()
            ); \">Speichern</button>";
    $x.= "</td>";

    if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
    $x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
    if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
    $x.= "</td>";

    $x.= "</tr><tr>";
    $x.= "<td style='text-align:center;'>".$user1_name."</td>";
    $x.= "<td style='text-align:center;'>".$user2_name."</td>";
    $x.= "</tr></table>";
  }


  $x.= "</div>";
  print $x;
}

if($_GET['ajax']=='add_user')
{
  $db->sql_query("DELETE FROM group2user WHERE group2user_group_id = '$_GET[tournament_id]' AND group2user_user_id='$_GET[user_id]'");
  $db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>$_GET['user_id']),'group2user');
  print $db->last_inserted_id;
}

if($_GET['ajax']=='remove_user')
{
  $x = "";
  $db->sql_query("DELETE FROM group2user WHERE group2user_group_id = '$_GET[tournament_id]' AND group2user_user_id='$_GET[user_id]'");
  $db->sql_query("SELECT * FROM location2user WHERE location2user_user_id='$_GET[user_id]'");
  while($d = $db->get_next_res())
  {
    $x.= $d->location2user_location_id.",";
  }
  print substr($x,0,-1);
}

if($_GET['ajax']=='show_user_info')
{
  $u1 = new user($_GET['user_id']);
  $db->sql_query("SELECT * FROM games WHERE game_group_id = '$_GET[tournament_id]' AND (game_player1_id='$_GET[user_id]' OR game_player2_id='$_GET[user_id]' OR game_player3_id='$_GET[user_id]' OR game_player4_id='$_GET[user_id]')");
  $x = "<div>";
  $x.= "<h1>Bisherige Spiele von ".$u1->login."</h1>";
  $x.= "<table style='width:100%;'>";
  while($data = $db->get_next_res())
  {
    $u1 = null; $u2 = null; $u3 = null; $u4 = null;
    $winner = null;
    $invert = null;

    if($data->game_player1_id==$_GET['user_id'] OR $data->game_player3_id==$_GET['user_id'])
    {
      $invert = true;
      $u1 = new user($data->game_player1_id);
      $u2 = new user($data->game_player2_id);
      if($data->game_player3_id>0)
      {
        $u3 = new user($data->game_player3_id);
        $u4 = new user($data->game_player4_id);
      }
    }

    if($data->game_player2_id==$_GET['user_id'] OR $data->game_player4_id==$_GET['user_id'])
    {
      $invert = false;
      $u2 = new user($data->game_player1_id);
      $u1 = new user($data->game_player2_id);
      if($data->game_player3_id>0)
      {
        $u3 = new user($data->game_player4_id);
        $u4 = new user($data->game_player3_id);
      }
    }

    $x.= "<tr>";
    $x.= "<td style='text-align:center;'><h2>Runde ".$data->game_round."</h2></td>";
    $x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u1->get_pic_path()."'><br/>".$u1->login."</td>";
    if(isset($u3)) { $x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u3->get_pic_path()."'><br/>".$u3->login."</td>"; }
    $x.= "<td style='text-align:center;'><h2>gegen</h2></td>";
    $x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u2->get_pic_path()."' onclick=\"show_user_games('".$u2->id."');\"><br/>".$u2->login."</td>";
    if(isset($u4)) { $x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u4->get_pic_path()."' onclick=\"show_user_games('".$u4->id."');\"><br/>".$u4->login."</td>"; }
    if($data->game_winner_id!='')
    {
      if($myTournament->counting=='win')
      {
        if($data->game_winner_id==$_GET['user_id'] OR $data->game_winner2_id==$_GET['user_id'])
        {
          $x.= "<td style='text-align:center;'><h1 style='color:green;'>Gewonnen!</h1></td>";
        }
        else
        {
          $x.= "<td style='text-align:center;'><h1 style='color:red;'>Verloren!</h1></td>";
        }
      }
      else
      {
        $txt = "<span style='font-size:16pt;font-weight:bold;'>";
        if($invert)
        {
          if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p1.":".$data->game_set1_p2; }
          if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p1.":".$data->game_set2_p2; }
          if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p1.":".$data->game_set3_p2; }
        }
        else
        {
          if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p2.":".$data->game_set1_p1; }
          if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p2.":".$data->game_set2_p1; }
          if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p2.":".$data->game_set3_p1; }
        }
        $txt.= "</span>";

        if($data->game_winner_id==$_GET['user_id'] OR $data->game_winner2_id==$_GET['user_id'])
        {
          $x.= "<td style='text-align:center;'><h1 style='color:green;'>".$txt."</h1></td>";
        }
        else
        {
          $x.= "<td style='text-align:center;'><h1 style='color:red;'>".$txt."</h1></td>";
        }

      }
      if($data->game_duration>0)
      {
        $x.= "<td style='text-align:center;font-size:14pt;'>Spieldauer<br/>".gmdate("H:i:s", $data->game_duration)."</td>";
      }
    }
    else
    {
      $x.= "<td style='text-align:center;' colspan='2'><h2 style='font-style:italic;'>Noch nicht gespielt</h2></td>";
    }
    $x.= "</tr>";
    $x.= "<tr><td colspan='8'><hr/></td></tr>";
  }
  $x.= "</table>";
  $x.= "</div>";
  print $x;
}

//************************************************************************************
