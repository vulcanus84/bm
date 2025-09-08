<?php
namespace Tournament;

switch ($_GET['action'])
{
  case 'get_title':
    $arr_tournaments = explode(',',$_GET['tournament_id']);
    foreach($arr_tournaments as $tournament)
    {
      $myTournament = new tournament($tournament);
      if(isset($_GET['curr_id'])&& $_GET['curr_id']==$tournament)
      {
        print "<div style='border-left:5px solid gray;padding:5px 20px 5px 20px;float:left;background-color:#CCC;font-size:24pt;font-weight:bold;'>{$myTournament->title}</div>";      
      }
      else
      {
        print "<div style='border-left:5px solid gray;padding:5px 20px 5px 20px;float:left;font-size:24pt;font-weight:bold;'>{$myTournament->title}</div>";      
      }
    }
    print "<div style='clear:both;font-size:12pt;'>&nbsp;</div>";
    break;

  case 'get_news':
    $db->sql_query("SELECT *, DATE_FORMAT(news_date, '%H:%i') as news_date_c FROM news WHERE news_tournament_id='".$_GET['tournament_id']."' ORDER BY news_date DESC");
    $x = "";
    while($d = $db->get_next_res())
    {
      $x.= "<span style='font-weight:bold;font-size:12pt;'>{$d->news_date_c} Uhr - {$d->news_title}</span><br/><span style='font-size:10pt;'>{$d->news_text}</span><hr/>";
    }
    print $x;
    break;

  case 'get_rounds':
    $db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
    if($db->count()>0)
    {
      if($myTournament->system=='Gruppenspiele')
      {
        $one_round_more = 2;
        print "<button id='round1'>Tabelle</button>";
      }
      else
      {
        $rounds = $db->count();
        $one_round_more = $rounds+1;
        while($d = $db->get_next_res())
        {
          print "<button id='round$d->game_round'>Runde $d->game_round</button>";
        }
      }
      if($myTournament->status=='Closed') { print "<button id='round$one_round_more'>Siegerehrung</button>"; }
    }
    else
    {
        print "<button id='no_round'>Noch keine Runde definiert</button>";
    }
    break;

  case 'get_number_of_rounds':
    // For closed tournaments, show one more round for the award ceremony
    // For group games, show only the overview and the award ceremony
    if($myTournament->system=='Gruppenspiele') {
      print ($myTournament->status == 'Closed') ? "2" : "1";
    } else {
      $db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
      print ($myTournament->status == 'Closed') ? $db->count()+1 : $db->count();
    }
    break;

  case 'get_users':
    print $myTournament->html->get_users_from_tournament('narrow');
    break;

  case 'get_courts':
    if($myTournament->system=='Gruppenspiele' AND $_GET['round']=='1') {
      print $myTournament->html->get_groupgame_overview();
    } else {
      if(isset($_GET['round']) && $myTournament->curr_round>=$_GET['round'] && $myTournament->system!='Gruppenspiele') {
        $i=1;
        for($i;$i<=$myTournament->number_of_courts;$i++) {
          $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
          if($db->count()>0) {
            $data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
            if($data->game_status=='Closed')
            {
              print "<div class='court' style='height:unset;' id='court$i'><img src='inc/php/court.php?action=fill&game_id=$data->game_id' class='img_court'/></div>"; 
            }
            else
            {
              print "<div class='court' style='height:unset;' id='court$i'><img src='inc/php/court.php?action=fill&game_id=$data->game_id' class='img_court'/></div>"; 
            }
          } else {
            if($myTournament->status!='Closed') { print "<div class='court' id='court$i'><img src='inc/php/court.php?action=clear' class='img_court'/></div>"; }
          }
        }
      }
    }

    // If the tournament is closed, show the award ceremony if on the last round
    if($myTournament->status=='Closed') {
      if($myTournament->curr_round==$_GET['round'] OR ($myTournament->system=='Gruppenspiele' AND $_GET['round']=='2')) {
        print $myTournament->html->get_award_ceremony();
      }
    }
    break;
}