<?php
namespace Tournament;

class calc 
{
  private $tournament;
  private $db;
  private $db2;

  public function __construct(Tournament $tournament) {
      $this->tournament = $tournament;
      $this->db = $tournament->db;
      $this->db2 = clone($this->db);
  }

  function calc_ranking() {
    foreach ($this->tournament->arr_players as $player) {
      $player->calc_wins();
    }
    foreach ($this->tournament->arr_players as $player) {
      $player->calc_BHZ('main');
    }
    foreach ($this->tournament->arr_players as $player) {
      $player->calc_BHZ('fine');
      $player->save();
    }
  }

  function define_games() {
		
		//Tournament with Freilos
		$with_freilos=false;
		$arr_players_available = $this->tournament->arr_players;

		$this->tournament->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='1'");
		if($this->tournament->db->count()==1) { $with_freilos=true; $this->tournament->logger->write_to_log("Tournament","Freilos vorhanden"); }

		if(substr($this->tournament->system,0,6)=='Doppel')
		{
			$users_on_court = null;
			$arr_ids = null;
			$arr_opponents = null;
			$w_str = null;
			$w_str2 = null;
			$db2 = clone($this->tournament->db);
			$db3 = clone($this->tournament->db);
			$court_nr=1;

			//Get players order by wins
			$this->tournament->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY group2user_wins DESC,group2user_BHZ DESC, RAND()");

			if($this->tournament->system=='Doppel_dynamisch')
			{
				$p1=null;$p2=null;$p3=null;$p4=null;
				//If a single should be played, define first

				if($this->tournament->db->count()%4 != 0 OR $with_freilos)
				{
					$this->tournament->logger->write_to_log("Tournament","Anzahl Teilnehmer nicht durch 4 teilbar");
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
						$this->tournament->logger->write_to_log("Tournament","Keine Person fürs Freilos gefunden, suche per Zufall jemanden aus");
						$db3->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY rand() LIMIT $limit"); 
					}
					else
					{
						$this->tournament->logger->write_to_log("Tournament","Freilos sauber zugeteilt, folgender SQL String wurde verwendet");
						$this->tournament->logger->write_to_log("Tournament","SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, rand() LIMIT $limit");
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

					$open_players = count($this->tournament->arr_players) - 2;
					//Do we need a single game?
					if($open_players % 4 > 0)
					{
						$this->tournament->logger->write_to_log("Tournament","Einzelspiel definieren");
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
							$this->tournament->logger->write_to_log("Tournament","2 Spieler gefunden, die noch keine Einzel gespielt haben");

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
								$this->tournament->logger->write_to_log("Tournament","1 Spieler gefunden, der noch keine Einzel gespielt hat, suche noch jemanden per Zufall");
							}
							else
							{
								$this->tournament->logger->write_to_log("Tournament","Keine Spieler gefunden, die noch keine Einzel gespielt haben, suche per Zufall aus");
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

				while($d = $this->tournament->db->get_next_res())
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

			if($this->tournament->system=='Doppel_fix')
			{
        $arr_teams_available = $this->tournament->arr_teams;
				
        for ($court_nr=$court_nr; $court_nr <= $this->tournament->number_of_courts; $court_nr++) { 
					$curr_team = $arr_teams_available[0];

					$arr_teams_available = array_filter($arr_teams_available,function($team) use ($curr_team) { return $curr_team->arr_players[0]!=$team->arr_players[0]; }); 
					$arr_teams_available = array_values($arr_teams_available);
					
          $opponent = $arr_teams_available[0];
					$arr_opponents = $arr_teams_available;
					//Remove all teams you have allready played against
					foreach ($this->tournament->arr_rounds as $round) {
						foreach ($round->arr_games as $game) {
              //Is player in left side, remove right side
							if($game->p1->id == $curr_team->arr_players[0]->id || $game->p3->id == $curr_team->arr_players[0]->id) { 
                $arr_opponents = array_filter($arr_opponents,function($team) use ($game) { return $team->id!=$team->combine_ids($game->p2->id,$game->p4->id); }); 
              }
							if($game->p2->id == $curr_team->arr_players[0]->id || $game->p4->id == $curr_team->arr_players[0]->id) { 
                $arr_opponents = array_filter($arr_opponents,function($team) use ($game) { return $team->id!=$team->combine_ids($game->p1->id,$game->p3->id); }); 
              }
						}
					}
					$arr_opponents = array_values($arr_opponents);
					if(Count($arr_opponents)>0) { $opponent = $arr_opponents[0]; }

					// //Remove all players with different number of wins
					// $arr_opponents = array_filter($arr_opponents, function ($user) use ($player) { return $user->wins == $player->wins; }); 
					// $arr_opponents = array_values($arr_opponents);
					// if(Count($arr_opponents)>0) { $opponent = $arr_opponents[0]; }

					$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
					$curr_game->p1 = $curr_team->arr_players[0];
					$curr_game->p3 = $curr_team->arr_players[1];
					$curr_game->p2 = $opponent->arr_players[0];
					$curr_game->p4 = $opponent->arr_players[1];
					$curr_game->save();

					//Remove team from available list
					$arr_teams_available = array_filter($arr_teams_available, function($team) use ($opponent) { return $team->id != $opponent->id; });
					$arr_teams_available = array_values($arr_teams_available);

        }
			}

			print "OK";
		}
		else
		{
			$users_on_court = [];
			$my_user = null;
			$court_nr=1;

			if(Count($this->tournament->arr_players)>Count($this->tournament->arr_rounds)) //Stop it, if they played against each opponent
			{
				//Freilos Handling
				$freilos = new \user(1);

				$user_ids = array_map(fn($u) => $u->id, $this->tournament->arr_players);
				if(in_array($freilos->id,$user_ids))
				{
					$arr_players_available = array_filter($arr_players_available,function($user) use ($freilos) { return $user->id!=$freilos->id; }); 
					$arr_players_available = array_values($arr_players_available);
					$opponent = $arr_players_available[Count($arr_players_available)-1];
					$arr_opponents = $arr_players_available;

					//Remove all players you have allready played against
					foreach ($this->tournament->arr_rounds as $round) {
						foreach ($round->arr_games as $game) {
							if($game->p1->id == $freilos->id) { $arr_opponents = array_filter($arr_opponents,function($user) use ($game) { return $user->id!=$game->p2->id; }); }
							if($game->p2->id == $freilos->id) { $arr_opponents = array_filter($arr_opponents,function($user) use ($game) { return $user->id!=$game->p1->id; }); }
						}
					}
					$arr_opponents = array_values($arr_opponents);
					if(Count($arr_opponents)>0) { $opponent = $arr_opponents[Count($arr_opponents)-1]; }

					$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
					$curr_game->p1 = $freilos;
					$curr_game->p2 = $opponent;
					$curr_game->winner = $opponent;

					switch ($this->tournament->counting) {
						case 'win':
							$curr_game->winner = $opponent;
							break;

						case '2setswinning':
						case 'official2sets':
							$curr_game->set1_p1_points = 10;
							$curr_game->set1_p2_points = 21;
							$curr_game->set2_p1_points = 10;
							$curr_game->set2_p2_points = 21;
							$curr_game->winner = $opponent;
							break;

						case '2sets11points':
							$curr_game->set1_p1_points = 5;
							$curr_game->set1_p2_points = 11;
							$curr_game->set2_p1_points = 5;
							$curr_game->set2_p2_points = 11;
							$curr_game->winner = $opponent;
							break;

					}
					$curr_game->save();
					$this->tournament->db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>'Freilos ausgelost','news_text'=>"Im Turnier {$this->tournament->title} hat {$opponent->login} das Freilos bekommen."),'news');

					$arr_players_available = array_filter($arr_players_available, function($user) use ($freilos,$opponent) { return $user->id != $freilos->id && $user->id != $opponent->id ; });
					$arr_players_available = array_values($arr_players_available);
					$court_nr++;
				}
				
				//find best suitable player
				for ($court_nr=$court_nr; $court_nr <= $this->tournament->number_of_courts; $court_nr++) { 
					$player = $arr_players_available[array_key_first($arr_players_available)];
					unset($arr_players_available[$player->id]);
					$opponent = $arr_players_available[array_key_first($arr_players_available)];
					$arr_opponents = $arr_players_available;

					//Remove all players you have allready played against
					foreach ($this->tournament->arr_rounds as $round) {
						foreach ($round->arr_games as $game) {
							if($game->p1->id == $player->id) { unset($arr_opponents[$game->p2->id]); }
							if($game->p2->id == $player->id) { unset($arr_opponents[$game->p1->id]); }
						}
					}
					if(Count($arr_opponents)>0) { $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

					//If player is seeded and we are in first round, remove all other seeded players
					if($player->seeding_no<99 && $this->tournament->curr_round==1) {
						$arr_opponents = array_filter($arr_opponents, function ($user) { return $user->seeding_no == 99; }); 
						$arr_opponents = array_values($arr_opponents);
					}
					if(Count($arr_opponents)>0) { $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

					//Remove all players with different number of wins
					$arr_opponents = array_filter($arr_opponents, function ($user) use ($player) { return $user->wins == $player->wins; }); 
					$arr_opponents = array_values($arr_opponents);
					if(Count($arr_opponents)>0) { $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

					$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
					$curr_game->p1 = $player;
					$curr_game->p2 = $opponent;
					$curr_game->save();

					//Remove players from available list
					unset($arr_players_available[$player->id]);
          unset($arr_players_available[$opponent->id]);
				}
				$this->tournament->db->insert(array('news_tournament_id'=>$this->tournament->id,'news_title'=>'Neue Runde ausgelost','news_text'=>"Im Turnier {$this->tournament->title} wurde eine neue Runde ausgelost."),'news');
				print "OK";
				
			}
			else {
				print "Zu viele Runden für die Anzahl an Spieler";
			}

		}
	}

  function calc_team_id($a, $b) {
    $sorted = [$a, $b];
    sort($sorted); // garantiert gleiche Reihenfolge
    return implode('', $sorted); // z.B. "3_7"
  }

}