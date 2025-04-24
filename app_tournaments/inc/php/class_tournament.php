<?php
namespace Tournament;

require_once('class_round.php');
require_once('class_game.php');
require_once('class_team.php');
require_once('../inc/php/class_location.php');

class tournament
{
	public $id;
	public $db;
	public $db2; //For loops with second db access
	public $title;
	public $description;
	public $system;
	public $counting;
	public $location;
	public $status;
	public $number_of_courts;
	public $logger;
	public $number_of_seedings;
	public $max_seeding_pos;
	private $tournament_rounds;
	public $arr_rounds = [];
	public $arr_players = []; // Used in Single
	public $arr_teams = [];	// Used in Doppel
	public $curr_round = null;

	public $html;
	public $calc;

	function __construct($tournament_id=null) {
		$this->db= new \db();
		$this->db2 = new \db();
		$this->logger = new \log();
		require_once('class_tournament/html.php');
		$this->html = new html($this);

		require_once('class_tournament/calculations.php');
		$this->calc = new calc($this);


		if($tournament_id!=null) { $this->load($tournament_id); }
	}

	function load_by_game_id($game_id) {
		$data = $this->db->sql_query_with_fetch("SELECT * FROM games WHERE game_id=:uid",array('uid'=>$game_id));
		$this->load($data->game_group_id);
	}

	function load($tournament_id) {
		if(!is_numeric($tournament_id)) { return; }

		$this->db->sql_query("SELECT * FROM groups WHERE group_id= :tournament_id",array('tournament_id'=>$tournament_id));
		if($this->db->count()==1)
		{
			$data = $this->db->get_next_res();
			$this->title = $data->group_title;
			$this->description = $data->group_description;
			$this->number_of_courts = $data->group_courts;
			$this->system = $data->group_system;
			$this->counting = $data->group_counting;
			$this->status = $data->group_status;
			$this->location = new \location($data->group_created_by_location);
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id= :tournament_id ORDER BY game_round DESC",array('tournament_id'=>$tournament_id));
			if($this->db->count()>0)
			{
				$data = $this->db->get_next_res();
			}
			else
			{
				$this->add_round(1);
			}
			$this->id=$tournament_id;
			//Load Players or Teams
			$this->db->sql_query("SELECT * FROM group2user 
														LEFT JOIN users ON group2user_user_id = user_id 
														WHERE group2user_group_id= :tournament_id 
														ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_account ASC",
														array('tournament_id'=>$tournament_id));
			while($d = $this->db->get_next_res())
			{
				if($d->group2user_partner_id>0)
				{
					$this->add_team($d->group2user_user_id,$d->group2user_partner_id);
				}
				$myPlayer = $this->add_player($d->group2user_user_id);
				if($myPlayer->seeding_no<99) { $this->number_of_seedings++; }
			}

			$this->max_seeding_pos = round(Count($this->arr_players)/2,0);
			if($this->max_seeding_pos>8) { $this->max_seeding_pos=8; }		

			//Load games and group it to rounds
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id= :tournament_id ORDER BY game_round ASC",array('tournament_id'=>$tournament_id));
			if($this->db->count()>0)
			{
				while($d = $this->db->get_next_res())
				{
					if(!isset($curr_round)) 
					{ 
						$curr_round = $this->add_round($d->game_round);	
					}
					else
					{
						if($curr_round->id != $d->game_round) 
						{ 
							$curr_round = $this->add_round($d->game_round);
						}
					}
					$curr_round->add_game($d->game_id);
				}
				if($curr_round->status=='Closed') 
				{ 
					$this->add_round(); 
				}
			}
		}
		else
		{
			throw new \Exception("Tournament with the following ID not found: ".$tournament_id);
		}

	}

	function save() {
    $arr_fields = [];
    //Players (all of class user)

    //General information
    if($this->title!=null) { $arr_fields['group_title'] = $this->title; }
    if($this->description!=null) { $arr_fields['group_description'] = $this->description; }
		if($this->location!=null) { $arr_fields['group_created_by_location'] = $this->location->id; }
		if($this->system!=null) { $arr_fields['group_system'] = $this->system; }
		if($this->counting!=null) { $arr_fields['group_counting'] = $this->counting; }
		if($this->status!=null) { $arr_fields['group_status'] = $this->status; }
		if($this->number_of_courts!=null) { $arr_fields['group_courts'] = $this->number_of_courts; }
  
    //Update or insert
		try {
			if($this->id!=null)
			{
				if(count($arr_fields)>0) { $this->db->update($arr_fields,'groups','group_id',$this->id); }
			}
			else
			{
				$this->db->insert($arr_fields,'groups');
				$this->id = $this->db->last_inserted_id;
			}
		} catch (\Throwable $th) {
			print $th->getMessage();
		}

		foreach ($this->arr_rounds as $round) {
			$round->save();
		}	

		foreach ($this->arr_teams as $team) {
			$team->save();
		}	

		foreach ($this->arr_players as $player) {
			$player->save();
		}	
	}

	function add_round() {
		$x = $this->arr_rounds [] = new round($this);
		return $x;
	}

	function add_player($id) {
		return $this->arr_players [$id] = new player($this, $id);
	}

	function add_team($id,$id2) {
		$team_id = $this->calc->calc_team_id($id,$id2);
		return $this->arr_teams [$team_id] = new team($this,$id,$id2);
	}

	function add_partner($id) {
		$arr_undefined_players = [];
		$arr_undefined_players = array_filter($this->arr_players, function ($player) use ($arr_undefined_players) {
			return !in_array($player->id, $arr_undefined_players);
		});
		$myTeam = $this->add_team($arr_undefined_players[0]->id,$id);
		$myTeam->save();
	}

	
	function objectArrayDiffByProperty(array $all, array $subset, string $property): array {
    $subset_values = array_map(fn($obj) => $obj->$property, $subset);

    return array_filter($all, fn($obj) => !in_array($obj->$property, $subset_values));
	}
	
	function check() {
		$players_count = count($this->arr_players);
		switch ($this->system) {
			case 'Gruppenspiele':
				if($players_count<3 OR $players_count>6) { return "Teilnehmeranzahl für Gruppenspiele muss zwischen 3 und 6 liegen \n Du hast aktuell {$players_count} ausgewählt" ; }
				break;

			case 'Schoch':
				if($players_count<4) { return "Für Schoch müssen es mindestens 4 Spieler sein \n Du hast aktuell nur {$players_count} ausgewählt"; }
				break;

			case 'Doppel_fix':
				if($this->status == 'New') {
					if($players_count>7) {
						//Teilnehmer ungerade?
						if($players_count % 4 != 0) {
							return "Teilnehmerzahl muss durch 4 teilbar sein (4,8,12,16, etc.)  \n Aktuell hast du {$players_count} Spieler ausgewählt";
						}
					}
					else {
						return "Zu wenig Teilnehmer für Doppel (min. 8 Spieler)  \n Du hast aktuell nur {$players_count} ausgewählt";
					}
				}
				else {
					if($players_count>0) {
						return "Noch nicht alle Teams definiert";
					}
				}
				break;
	
		}

	}

	function start() {
		
		try 
		{
			//check if all conditions are ok
			$start_checks = $this->check();
			if($start_checks!='') { return $start_checks; }
			$players_count = count($this->arr_players);
			
			if($this->system=='Gruppenspiele')
			{
				//Define all games on start of the tournament, $order_of_play contains rounds, games and players
				switch ($players_count) {
					case '3':
						$this->number_of_courts = 1;
						$order_of_play = [ [[0,1]],[[0,2]],[[1,2]] ];
						break;

					case '4':
						$this->number_of_courts = 2;
						$order_of_play = [ [[0,1],[2,3]],[[0,2],[1,3]],[[1,2],[0,3]] ];
						break;

					case '5':
						$this->number_of_courts = 2;
						$order_of_play = [ [[0,1],[2,3]],[[0,2],[1,4]],[[0,3],[2,4]],[[0,4],[1,3]],[[1,2],[3,4]] ];
						break;

					case '6':
						$this->number_of_courts = 3;
						$order_of_play = [ [[0,1],[2,3],[4,5]],[[0,2],[1,4],[3,5]],[[0,3],[1,5],[2,4]],[[0,4],[1,3],[2,5]],[[0,5],[1,2],[3,4]] ];
						break;
				}

				foreach($order_of_play as $round)
				{
					$curr_round = $this->add_round();
					foreach($round as $game)
					{
						$curr_game = $curr_round->add_game();
						$curr_game->p1 = $this->arr_players[$game[0]];
						$curr_game->p2 = $this->arr_players[$game[1]];
					}
				}
				$this->status = "Started";
				$this->save();
			}
		
			if($this->system=='Schoch')
			{
				//Teilnehmer ungerade?
				if(Count($this->arr_players) % 2 != 0) { $this->add_player(1); }
				$this->status = "Started";
				$this->number_of_courts = ceil(Count($this->arr_players)/2);
				$this->save();
			}
		
			if($this->system=='Doppel_dynamisch')
			{
				if($players_count>3)
				{
					$anz_courts = ceil($players_count/4);
					$rest = $players_count % 4;
					if($rest==3) { $anz_courts = $anz_courts + 1; } //one court for the single game and one to show the freilos
					//Teilnehmer ungerade?, Freilos hinzufügen
					if($players_count % 2 != 0)
					{
						$this->db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>'1'),'group2user');
						$players_count++;
					}
					$this->db->update(array('group_status'=>'Started','group_round'=>'1','group_courts'=>$anz_courts),'groups','group_id',$_GET['tournament_id']);
				}
				else
				{
					print "Zu wenig Teilnehmer für Doppel (min. 4 Spieler)";
				}
			}
		
			if($this->system=='Doppel_fix')
			{
				if($this->status=='New') {
					$this->status = "Define_teams";
					$this->number_of_courts = ceil(count($this->arr_players)/4);
					$this->save();
				} else {
					$this->status = "Started";
					$this->save();
				}
			}
			print "OK";
		} catch (\Throwable $th) {
			print $th->getMessage();
		}

	}

	function cancel() {

		try {
			$this->db->sql_query("DELETE FROM games WHERE game_group_id='{$this->id}'");
			$this->db->sql_query("DELETE FROM news WHERE news_tournament_id='{$this->id}'");
			$this->db->sql_query("DELETE FROM group2user WHERE group2user_user_id='1' AND group2user_group_id='{$this->id}'"); //Remove Dummy user if exist
			$this->status = "New";
			$this->calc->calc_ranking();
			$this->save();

			foreach ($this->arr_players as $player) {
				$player->reset();
			}
			foreach ($this->arr_teams as $team) {
				$team->delete();
			}
			$this->save();
		} catch (\Throwable $th) {
			print $th->getMessage();
		}
	}




}
?>
