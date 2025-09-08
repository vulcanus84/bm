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
		
		$arr_players_available = $this->tournament->arr_players;
		$arr_players_available_single = array();

		if(substr($this->tournament->system,0,6)=='Doppel')
		{
			$arr_opponents = null;
			$court_nr=1;

			if($this->tournament->system=='Doppel_dynamisch')
			{
				//Freilos Handling
				$retVal = $this->freilos_handling();
				if(is_numeric($retVal)) {
					unset($arr_players_available[$retVal]);
					unset($arr_players_available[1]);
					$court_nr++;
				} else {
					//Fehlerhandling wenn alle bereits Freilos hatten
				}

				//If odd number of players, define single game
				if(Count($arr_players_available)%4 != 0)
				{
					$this->tournament->logger->write_to_log("Tournament","Einzelspiel definieren");
					$arr_players_available_single = $arr_players_available;

					// 1. Initialisiere alle Spieler mit 0 Einzelspielen
					$singleCounts = [];
					foreach ($arr_players_available_single as $user) {
							$singleCounts[$user->id] = 0;
					}

					// 2. Einzelspiele mitzählen
					foreach ($this->tournament->arr_rounds as $round) {
							foreach ($round->arr_games as $game) {
									if ($game->p3 === null) { // Einzelspiel
										// Kein Freilos mitzählen
										if($game->p1->id != 1 && $game->p2->id !=1) {
											if (isset($singleCounts[$game->p1->id])) $singleCounts[$game->p1->id]++;
											if (isset($singleCounts[$game->p2->id])) $singleCounts[$game->p2->id]++;
										} 
									}
							}
					}

					// 3. Spieler nach minimalen Einzelspielen auswählen
					$minSingles = min($singleCounts);
					$available = array_filter($arr_players_available_single, fn($user) => $singleCounts[$user->id] === $minSingles);
					if (count($available) === 1) {
						$available_tmp = array_filter($arr_players_available_single, fn($user) => $singleCounts[$user->id] === $minSingles+1);
						$available = array_merge($available, [$available_tmp[array_key_first($available_tmp)]]);
					}
					
					$arr_players_available_single = array_values($available);

					if(Count($arr_players_available_single)>1)
					{
						shuffle($arr_players_available_single);
						$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
						$curr_game->p1 = $arr_players_available_single[0];
						$curr_game->p2 = $arr_players_available_single[1];
						unset($arr_players_available[$curr_game->p1->id]);
						unset($arr_players_available[$curr_game->p2->id]);
						$curr_game->save();
						$court_nr++;
					} else {
						print "Zu viele Runden für die Anzahl an Spieler";
						$this->tournament->arr_rounds[$this->tournament->curr_round-1]->delete();
						return;
					}
				}

				// Define teams
				while (Count($arr_players_available) > 0) {

					// Safety check to avoid infinite loop
					if(Count($arr_players_available) < 2) {
							error_log("Not enough players to form a team.");
							break;
					}

					$player = $arr_players_available[array_key_first($arr_players_available)];
					unset($arr_players_available[$player->id]);

					$arr_partners = $arr_players_available;

					// Remove all players you have already played with
					foreach ($this->tournament->arr_rounds as $round) {
							foreach ($round->arr_games as $game) {
									// Is a double game?
									if ($game->p3) {
											if ($game->p1->id == $player->id) { unset($arr_partners[$game->p3->id]); }
											if ($game->p2->id == $player->id) { unset($arr_partners[$game->p4->id]); }
											if ($game->p3->id == $player->id) { unset($arr_partners[$game->p1->id]); }
											if ($game->p4->id == $player->id) { unset($arr_partners[$game->p2->id]); }
									}
							}
					}

					// If no partners left, reset the list (have to play again with someone)
					if (count($arr_partners) == 0) {
						$arr_partners = $arr_players_available;
					}

					// Get partners with the biggest distance in wins
					$maxDistanz = 0;
					$besteKandidaten = [];

					foreach ($arr_partners as $k) {
							$distanz = abs($player->wins - $k->wins);

							if ($distanz > $maxDistanz) {
									$maxDistanz = $distanz;
									$besteKandidaten = [$k];
							} elseif ($distanz === $maxDistanz) {
									$besteKandidaten[] = $k;
							}
					}

					// Choose randomly one of the best candidates
					$partner =  $besteKandidaten[array_rand($besteKandidaten)];
					unset($arr_players_available[$partner->id]);

					$this->tournament->add_team(intval($player->id), intval($partner->id));
				}


				$arr_teams = $this->tournament->arr_teams;
				for ($court_nr=$court_nr; $court_nr <= $this->tournament->number_of_courts; $court_nr++) { 
					$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
					$curr_game->t1 = $arr_teams[array_key_first($arr_teams)];
					unset($arr_teams[$curr_game->t1->id]);
					$curr_game->t2 = $arr_teams[array_key_first($arr_teams)];
					unset($arr_teams[$curr_game->t2->id]);
					$curr_game->p1 = $curr_game->t1->arr_players[0];
					$curr_game->p3 = $curr_game->t1->arr_players[1];
					$curr_game->p2 = $curr_game->t2->arr_players[0];
					$curr_game->p4 = $curr_game->t2->arr_players[1];
					$curr_game->save();			
				}

				$this->tournament->db->insert(array('news_tournament_id'=>$this->tournament->id,'news_title'=>'Neue Runde ausgelost','news_text'=>"Im Turnier {$this->tournament->title} wurde eine neue Runde ausgelost."),'news');
				print "OK";
			}

			if($this->tournament->system=='Doppel_fix')
			{
				if(Count($this->tournament->arr_teams)>Count($this->tournament->arr_rounds)) //Stop it, if they played against each opponent
				{
					$arr_teams_available = $this->tournament->arr_teams;
				
					for ($court_nr=$court_nr; $court_nr <= $this->tournament->number_of_courts; $court_nr++) { 
						$curr_team = $arr_teams_available[array_key_first($arr_teams_available)];
						unset($arr_teams_available[$curr_team->id]);
						
						$opponent = $arr_teams_available[array_key_first($arr_teams_available)];
						$arr_opponents = $arr_teams_available;

						//Remove all teams you have allready played against
						foreach ($this->tournament->arr_rounds as $round) {
							foreach ($round->arr_games as $game) {
								//Is team in left side, remove right side
								if($game->t1->id == $curr_team->id) { unset($arr_opponents[$game->t2->id]); }
								if($game->t2->id == $curr_team->id) { unset($arr_opponents[$game->t1->id]); }
							}
						}
						if(Count($arr_opponents)>0) { $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

						// //Remove all teams with different number of wins
						$arr_opponents = array_filter($arr_opponents, function ($team) use ($curr_team) { return $team->wins == $curr_team->wins; }); 
						$arr_opponents = array_values($arr_opponents);
						shuffle($arr_opponents);
						if(Count($arr_opponents)>0) { $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

						$curr_game = $this->tournament->arr_rounds[$this->tournament->curr_round-1]->add_game();
						$curr_game->p1 = $curr_team->arr_players[0];
						$curr_game->p3 = $curr_team->arr_players[1];
						$curr_game->p2 = $opponent->arr_players[0];
						$curr_game->p4 = $opponent->arr_players[1];
						$curr_game->t1 = $curr_team;
						$curr_game->t2 = $opponent;
						$curr_game->save();

						//Remove team from available list
						unset($arr_teams_available[$opponent->id]);
					}
					print "OK";
				}
				else {
					print "Zu viele Runden für die Anzahl an Teams";
				}
	
			}

		}
		else
		{
			$court_nr=1;
			if(Count($this->tournament->arr_players)>Count($this->tournament->arr_rounds)) //Stop it, if they played against each opponent
			{
				//Freilos Handling
				$retVal = $this->freilos_handling();
				if(is_numeric($retVal)) {
					unset($arr_players_available[$retVal]);
					unset($arr_players_available[1]);
					$court_nr++;
				} else {
					//Fehlerhandling wenn alle bereits Freilos hatten
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
						if(Count($arr_opponents)>0) { shuffle($arr_opponents); $opponent = $arr_opponents[array_key_first($arr_opponents)]; }
					}
					
					//Remove all players with different number of wins
					$arr_opponents = array_filter($arr_opponents, function ($user) use ($player) { return $user->wins == $player->wins; }); 
					$arr_opponents = array_values($arr_opponents);
					if(Count($arr_opponents)>0) { shuffle($arr_opponents); $opponent = $arr_opponents[array_key_first($arr_opponents)]; }

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

	function freilos_handling() {
			//Freilos Handling
			$arr_players_available = $this->tournament->arr_players;
			$freilos = new player($this->tournament,1);
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

				//Remove all players with more wins than the worst player
				$min_wins = min(array_map(fn($u) => $u->wins, $arr_opponents));
				$arr_opponents = array_filter($arr_opponents, function ($user) use ($min_wins) { return $user->wins == $min_wins; });

				//Shuffle remaining players and take first one
				shuffle($arr_opponents); $arr_opponents = array_values($arr_opponents);

				if(Count($arr_opponents)>0) { 
					$opponent = $arr_opponents[array_key_first($arr_opponents)];
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
					return $opponent->id;

				} else { return "Alle Spieler haben bereits gegen den Freilos Spieler gespielt"; }
			} else { return "Kein Freilos bei den Spielern dabei";}
	}


}