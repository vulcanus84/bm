<?php
$now = date("Y-m-d H:i:s");

switch ($_GET['ajax']) {
  case 'set_start_time':
    $db->update(array('court_status'=>'play','court_started_on'=>$now,'court_stopped_on'=>NULL),'courts','court_no',$_GET['court']);
    //Get no court image
    print get_court($db, $_GET['court']);
    break;

  case 'stopp_time':
    $db->update(array('court_status'=>'stopped','court_stopped_on'=>$now),'courts','court_no',$_GET['court']);
    //Get no court image
    print get_court($db, $_GET['court']);
    break;

  case 'resume':
    $db->update(array('court_status'=>'play','court_stopped_on'=>NULL),'courts','court_no',$_GET['court']);
    break;

  case 'save_court':
    $data = $db->sql_query_with_fetch("SELECT *,(TIME_TO_SEC(court_stopped_on) - TIME_TO_SEC(court_started_on)) AS seconds FROM courts WHERE court_no='$_GET[court]'");
    $db->update(array('game_duration'=>$data->seconds,'game_started_on'=>$data->court_started_on,'game_stopped_on'=>$data->court_stopped_on),'games','game_id',$data->court_game_id);
    $db->update(array('court_status'=>'empty','court_started_on'=>NULL,'court_stopped_on'=>NULL,'court_game_id'=>NULL),'courts','court_no',$_GET['court']);
    //Get no court image
    print get_court($db, $_GET['court']);
    break;

  case 'get_open_games':
    print "<div style='font-size:20pt;'>Offene Spiele  <img src='".level."inc/imgs/refresh.png' alt='Refresh' style='width:2vw;' onclick='get_open_games();'/><hr></div>";	
    $last_game_round = '';
    $last_tournament = '';
    $db2 = clone($db);
    $db->sql_query("SELECT * FROM games 
                      LEFT JOIN courts ON court_game_id = games.game_id
                      LEFT JOIN groups ON game_group_id = group_id
                      WHERE game_status='New' AND game_duration is NULL AND court_no IS NULL
                      ORDER BY game_round ASC, game_created_on ASC, group_title ASC 
                      LIMIT 30");
    while($d = $db->get_next_res())
    {
      if($last_tournament!=$d->group_title) 
      { 
        print "<div style='font-size:20pt;'>$d->group_title</div>";	
        $last_tournament = $d->group_title; 
        $last_game_round='';
      }
      if($last_game_round!=$d->game_round) 
      { 
        print "<div style='font-size:12pt;'>Runde $d->game_round</div>";	
        $last_game_round = $d->game_round; 
      }
      $db2->sql_query("SELECT *,DATE_FORMAT(game_stopped_on,'%Y-%m-%d %H:%i:%s') as stopped,DATE_FORMAT(game_started_on,'%Y-%m-%d %H:%i:%s') as started FROM games
                        LEFT JOIN groups ON game_group_id = group_id
                        WHERE group_status='Started' AND game_stopped_on IS NOT NULL AND (game_player1_id='$d->game_player1_id' OR game_player2_id='$d->game_player1_id') ORDER BY game_stopped_on DESC");
      if($db2->count()>0)
      {
        $d2 = $db2->get_next_res();
        $resttime_p1 = $helper->datediff($d2->stopped,'','s');
        $resttime_p1_txt = gmdate("H:i",$resttime_p1);
      }
      else
      {
        $resttime_p1 = 9999;
        $resttime_p1_txt = "99:99";
      }
      $db2->sql_query("SELECT *,DATE_FORMAT(game_stopped_on,'%Y-%m-%d %H:%i:%s') as stopped,DATE_FORMAT(game_started_on,'%Y-%m-%d %H:%i:%s') as started FROM games
                        LEFT JOIN groups ON game_group_id = group_id
                        WHERE group_status='Started' AND game_stopped_on IS NOT NULL AND (game_player1_id='$d->game_player2_id' OR game_player2_id='$d->game_player2_id') ORDER BY game_stopped_on DESC");
      if($db2->count()>0)
      {
        $d2 = $db2->get_next_res();
        $resttime_p2 = $helper->datediff($d2->stopped,'','s');
        $resttime_p2_txt = gmdate("H:i",$resttime_p2);
      }
      else
      {
        $resttime_p2 = 9999;
        $resttime_p2_txt = "99:99";
      }
      if($resttime_p1<900 OR $resttime_p2<900) { $zus_txt = 'background-color:orange;'; } else { $zus_txt = ''; }
      print "<div class='draggable' style='border-radius:10px;padding:0px;margin-bottom:5px;$zus_txt' id='$d->game_id'>";
      print "	<table style='border-spacing:0px;'><tr><td style='text-align:center;width:10%;'><img src='sleep.svg' style='width:100%;'/><br/>$resttime_p1_txt</td>";
      print "	<td><img src='court.php?action=fill&game_id=$d->game_id' class='court'/></td>";
      print "	<td style='text-align:center;width:10%;'><img src='sleep.svg' style='width:100%;'/><br/>$resttime_p2_txt</td></tr></table>";
      print "</div>";	
    }
    break;

  case 'refresh_court':
    //Update court information
    $court_no = str_replace('court','',$_GET['court']);
    $db->sql_query("SELECT * FROM courts WHERE court_no='$court_no'"); 
    if($db->count()>0) 
    {
      if(!isset($_GET['game_id']) OR $_GET['game_id']=='')
      {
        $db->update(array('court_no'=>$court_no,'court_game_id'=>NULL,'court_status'=>'empty','court_started_on'=>NULL,'court_stopped_on'=>NULL),'courts','court_no',$court_no);
      }
      else
      {
        $db->update(array('court_status'=>'assigned','court_game_id'=>$_GET['game_id']),'courts','court_no',$court_no);
      }
    }
    else
    {
      if(!isset($_GET['game_id']) OR $_GET['game_id']=='')
      {
      }
      else
      {
        $db->insert(array('court_no'=>$court_no,'court_status'=>'assigned','court_game_id'=>$_GET['game_id']),'courts');
      }
    }
    //Get no court image
    print get_court($db, $court_no);

    break;
}