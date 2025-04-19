<?php
require_once('class_round.php');
require_once('class_game.php');
require_once('class_team.php');

class tournament
{
	public $id;
	public $db;
	public $title;
	public $description;
	public $system;
	public $counting;
	public $location;
	public $status;
	public $number_of_courts;
	private $number_of_seedings;
	private $tournament_rounds;
	public $arr_rounds = [];
	public $arr_players = []; // Used in Single
	public $arr_teams = [];	// Used in Doppel
	public $curr_round = null;

	function __construct($tournament_id=null)
	{
		$this->db= new db();

		if($tournament_id!=null) { $this->load($tournament_id); }
	}

	function load_by_game_id($game_id)
	{
		$data = $this->db->sql_query_with_fetch("SELECT * FROM games WHERE game_id=:uid",array('uid'=>$game_id));
		$this->load($data->game_group_id);
	}

	function load($tournament_id)
	{
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
			$this->location = new location($data->group_created_by_location);
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id= :tournament_id ORDER BY game_round DESC",array('tournament_id'=>$tournament_id));
			if($this->db->count()>0)
			{
				$data = $this->db->get_next_res();
				$this->tournament_rounds = $data->game_round +1;
			}
			else
			{
				$this->tournament_rounds = 1;
			}
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :tournament_id AND group2user_seeded IS NOT NULL",array('tournament_id'=>$tournament_id));
			$this->number_of_seedings = $this->db->count();
			$this->id=$tournament_id;
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id= :tournament_id ORDER BY game_round ASC",array('tournament_id'=>$tournament_id));
			//Load games and group it to rounds
			if($this->db->count()>0) { $this->curr_round = $this->db->count(); } else { $this->curr_round = 1; }
			if(!isset($_GET['round'])) { $_GET['round'] = $this->curr_round;}
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
			//Test Output
			/*
			foreach ($this->arr_rounds as $round) {
				print "<h1>".$round->id."</h1>";
				foreach ($round->arr_games as $game) {
					print "<h2>".$game->status."</h2>";
				}
			}
			*/


			//Load Players or Teams
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :tournament_id",array('tournament_id'=>$tournament_id));
			$arr_defined_players = [];
			while($d = $this->db->get_next_res())
			{
				if($d->group2user_partner_id>0) 
				{
					if(!in_array($d->group2user_user_id,$arr_defined_players)) {
						$this->add_team($d->group2user_user_id,$d->group2user_partner_id);
						$arr_defined_players[] = $d->group2user_user_id;
						$arr_defined_players[] = $d->group2user_partner_id;
					}
				} 
				else 
				{
					$this->add_player($d->group2user_user_id);
				}
			}
		}
		else
		{
			throw new Exception("Tournament with the following ID not found: ".$tournament_id);
		}

	}

	function add_round()
	{
		return $this->arr_rounds [] = new round($this);
	}

	function add_player($id)
	{
		return $this->arr_players [] = new player($this, $id);
	}

	function add_team($id,$id2)
	{
		return $this->arr_teams [] = new team($this,$id,$id2);
	}

	function save()
	{
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
	}

	function get_number_of_seedings()
	{
    return $this->number_of_seedings;
  }

	function get_rounds()
	{
    return $this->tournament_rounds;
  }

	function get_users_for_seedings()
  {
  	$x = "";
  	$arr_users = array();
		$x.= "<h1>Teilnehmer</h1>";
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												WHERE group2user_group_id='".$this->id."' AND group2user_seeded = 99
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	$first = true;
  	while($d = $this->db->get_next_res())
  	{
  		$my_user = new user($d->group2user_user_id);
  		$x.= "<div class='user_mit_BHZ' onclick='add_as_seeded($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."</div>";
  		$my_user = null;
  	}
  	return $x;
  }


  function get_users_from_tournament($mode='default')
  {
  	$x = "";
  	if($mode!='narrow')
  	{
 			$x.= "<h1>Teilnehmer</h1>";
 			if(!isset($_GET['round']))
 			{
 				$x.="<a href='match_pdf.php?tournament_id=".$this->id."' target='_blank'>Alle Matchblätter</a>";
 			}
 			else
 			{
 				$x.="<a href='match_pdf.php?tournament_id=".$this->id."&round=".$_GET['round']."' target='_blank'>Matchblätter Runde ".$_GET['round']."</a>";
 			}
  	}

  	$last_wins = "";
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												WHERE group2user_group_id = '$this->id'
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	$first = true;
  	$arr_displayed = array();

  	while($d = $this->db->get_next_res())
  	{
  		if($last_wins!=$d->group2user_wins)
  		{
  			if($mode=='narrow')
  			{
	  			if(!$first) { $x.= "</div>"; }
	  			if($d->group2user_wins<>1)
	  			{
	  				$x.= "<div class='siege' style='border-right:0px solid #DDD;margin-right:10px;padding-right:10px;float:left;'>".$d->group2user_wins.' Siege<p>';
	  			}
	  			else
	  			{
	  				$x.= "<div class='siege' style='border-right:0px solid #DDD;margin-right:10px;padding-right:10px;float:left;'>".$d->group2user_wins.' Sieg<p>';
	  			}
	  			$first = false;
  			}
	  		else
	  		{
					if($d->group2user_wins<>1)
					{
						$x.= "<hr style='clear:both;'/><div class='siege'>".$d->group2user_wins.' Siege</div>';
					}
					else
					{
						$x.= "<hr style='clear:both;'/><div class='siege'>".$d->group2user_wins.' Sieg</div>';
					}
	  		}
  		}

  		$my_user = new user($d->group2user_user_id);
			if($this->system=='Doppel_fix')
			{
				if(!in_array($my_user->id,$arr_displayed))
				{
					$this->db2 = clone($this->db);
					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='".$this->id."' AND group2user_user_id='$my_user->id'");
					$partner = new user($d2->group2user_partner_id);
					$x.= "<div class='user_mit_BHZ' onclick='show_user_games($my_user->id);'>".$my_user->get_picture(false,null,'80px',false).$partner->get_picture(false,null,'80px',true)."<br/>".$my_user->login." & ".$partner->login."<br/>".$d->group2user_BHZ.".".$d->group2user_FBHZ."</div>";
					$arr_displayed[] = $my_user->id;
					$arr_displayed[] = $partner->id;
				}
			}
			else
			{
				$x.= "<div class='user_mit_BHZ' style='margin:0.1vw' onclick='show_user_games($my_user->id);'>".$my_user->get_picture(false,null,'5.5vw',false)."<br/>".$my_user->login."<br/>".$d->group2user_BHZ.".".$d->group2user_FBHZ."</div>";
			}
		$my_user = null;
  		$last_wins = $d->group2user_wins;
  	}
  	if($mode=='narrow') { $x.= "</div>"; }
  	return $x;
  }

  function get_all_users($js_event_name,$group_by='alphabetical')
	{
		$x = "";
		$header = new header_mod();
		$x.="<a href='".$header->change_parameter('order_by','alphabetical')."'><img style='height:48px;' src='".level."inc/imgs/sort_az_descending.png' title='Alphabetisch' alt='Alphabetisch' /></a>";
		$x.="<a href='".$header->change_parameter('order_by','gender')."'><img style='height:48px;' src='".level."inc/imgs/male_female.png' title='Geschlecht' alt='Geschlecht' /></a>";
		$x.="<a href='".$header->change_parameter('order_by','age')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_age.png' title='Alter' alt='Alter' /></a>";
		$x.="<a href='".$header->change_parameter('order_by','location')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_location.png' title='Trainingsort' alt='Trainingsort' /></a>";
		$x.="<hr>";
		$this->db->sql_query("SELECT * FROM location_permissions
										LEFT JOIN locations ON loc_permission_loc_id = location_id
										WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'
										ORDER BY location_name");
		if($this->db->count()>1)
		{
			$x.="<form id='change_location_filter' action='".$header->change_parameter('action','change_location_filter')."' method='POST'>";
			$x.="<select name='location' style='width:90%;margin:2.5%;' onchange=\"$('#change_location_filter').submit();\">";
			$x.="<option value=''>-- Alle Standorte --</option>";
			while ($d=$this->db->get_next_res())
			{
				$x.="<option";
				if(isset($_GET['location_filter']) && $_GET['location_filter']==$d->location_id) {$x.=" selected='1'"; }
				$x.=" value='".$d->location_id."'>".$d->location_name."</option>";
			}
			$x.="</select>";
			$x.="</form>";
			$x.="<hr style='margin:0px;'>";
			if(isset($_GET['show_hidden']) AND $_GET['show_hidden']=='1')
			{
				$w_str = "WHERE user_id!='1'";
			}
			else
			{
				$w_str = "WHERE user_id!='1' AND user_hide<1";
			}		
	
			//Check permissions
			$this->db->sql_query("SELECT * FROM location_permissions
														LEFT JOIN locations ON loc_permission_loc_id = location_id
														WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
			if($this->db->count()==0) { $w_str.= " AND user_id=0"; } else { $w_str.= " AND ("; }
	
			if(isset($_GET['location_filter']) && is_numeric($_GET['location_filter']))
			{
				$w_str.= "location2user_location_id=".$_GET['location_filter'].")";
			}
			else
			{
				$i=0;
				while($d = $this->db->get_next_res())
				{
					if($i==0) { $w_str.= "location2user_location_id='$d->location_id'"; } else { $w_str.= " OR location2user_location_id='$d->location_id'"; }
					$i++;
				}
				if($this->db->count()>0) { $w_str.= ")"; }
			}
	
			if($this->id!=null)
			{
				$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '$this->id'");
				while($data = $this->db->get_next_res())
				{
					$w_str.= " AND user_id!='".$data->group2user_user_id."'";
				}
			}
	
			if($group_by=='alphabetical')
			{
				$this->db->sql_query("SELECT DISTINCT user_id,user_account FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str ORDER BY user_account ASC");
				$x.= "<h1>Spieler (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture(true,$js_event_name,null,true);
					$my_user = null;
				}
			}
	
			if($group_by=='gender')
			{
				$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str AND user_gender='Frau' ORDER BY user_account ASC");
				$x.= "<h1>Mädchen (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture(true,$js_event_name,null,true);
					$my_user = null;
				}
				$x.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
				$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str AND user_gender='Herr' ORDER BY user_account ASC");
				$x.= "<h1>Jungs (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture(true,$js_event_name,null,true);
					$my_user = null;
				}
			}
	
			if($group_by=='location')
			{
				$db2 = clone($this->db);
				$db2->sql_query("SELECT MAX(location_name) as location_name,MAX(location2user_location_id) as location2user_location_id
										FROM location2user
										LEFT JOIN locations ON location2user_location_id = locations.location_id
										LEFT JOIN users ON location2user_user_Id = users.user_id
										$w_str
										GROUP BY location2user_location_id
										ORDER BY location_name ASC");
				while($data2 = $db2->get_next_res())
				{
					$this->db->sql_query("SELECT * 
											FROM location2user
											LEFT JOIN locations ON location2user_location_id = locations.location_id
											LEFT JOIN users ON location2user_user_Id = users.user_id
											$w_str AND location2user_location_id='$data2->location2user_location_id'
											ORDER BY user_account ASC");
					$x.= "<section id='section_".$data2->location2user_location_id."' style='display:flex;flex-wrap:wrap;border-bottom:1px solid gray;'>";
					$x.= "<h1 style='flex-basis: 100%;'>$data2->location_name (".$this->db->count().")</h1><p/>";
					while($data = $this->db->get_next_res())
					{
						$my_user = new user($data->user_id);
						$x.= $my_user->get_picture(true,$js_event_name,null,true,true);
						$my_user = null;
					}
					$x.= "</section>";
				}
			}
	
			if($group_by=='age')
			{
				$db2 = clone($this->db);
				$db2->sql_query("SELECT 
										YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) as diff_years
											FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str 
										GROUP BY YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) 
										ORDER BY diff_years");
				while($data2 = $db2->get_next_res())
				{
					if($data2->diff_years=='')
					{
						$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
												LEFT JOIN users ON location2user_user_id = users.user_id 
												$w_str AND user_birthday IS NULL ORDER BY user_account ASC");
					}
					else
					{
						$this->db->sql_query("SELECT DISTINCT user_id, user_account, user_birthday FROM location2user 
												LEFT JOIN users ON location2user_user_id = users.user_id 
												$w_str  AND YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d'))='$data2->diff_years' 
												ORDER BY user_birthday DESC");
					}
					if($data2->diff_years=='')
					{
						$x.= "<h1>Unbekannt (".$this->db->count().")</h1>";
					}
					else
					{
						$x.= "<h1>".$data2->diff_years." Jahre (".$this->db->count().")</h1>";
					}
					while($data = $this->db->get_next_res())
					{
						$my_user = new user($data->user_id);
						$x.= $my_user->get_picture(true,$js_event_name,null,true);
						$my_user = null;
					}
					$x.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
				}
			}
	
			$x.="</div>";
		}				


		return $x;
	}


	function calc_BHZ()
	{
		$tournament_id = $this->id;

		$users = clone($this->db);
		$this->db2 = clone($this->db);

		//Insert count of wins
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id='$tournament_id' AND game_status='Closed' AND (game_winner_id='$my_user_id' OR game_winner2_id='$my_user_id')");
			$wins = $this->db->count();
			$this->db->sql_query("UPDATE group2user SET group2user_wins='$wins' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert BHZ
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;

			$BHZ = 0;
			if($this->system=='Doppel_dynamisch')
			{
				//Get own wins
				$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$my_user_id'");
				$BHZ = $d2->group2user_wins;

				//Get all games with current player involved
				$this->db->sql_query("SELECT * FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id = '$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$p1_wins=0; $p2_wins=0; $p3_wins=0; $p4_wins=0;

					//Get wins for all involved players
					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player1_id'");
					$p1_wins = $d2->group2user_wins;

					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player2_id'");
					$p2_wins = $d2->group2user_wins;

					if($d->game_player3_id>0)
					{
						$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player3_id'");
						$p3_wins = $d2->group2user_wins;

						$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player4_id'");
						$p4_wins = $d2->group2user_wins;
					}

					//Calculcate BHZ by subtracting wins of partner and add wins of both opponents
					if($d->game_player1_id==$my_user_id) { $BHZ = $BHZ - $p3_wins + $p2_wins + $p4_wins; }
					if($d->game_player2_id==$my_user_id) { $BHZ = $BHZ - $p4_wins + $p1_wins + $p3_wins; }
					if($d->game_player3_id==$my_user_id) { $BHZ = $BHZ - $p1_wins + $p2_wins + $p4_wins; }
					if($d->game_player4_id==$my_user_id) { $BHZ = $BHZ - $p2_wins + $p1_wins + $p3_wins; }
				}
			}
			else
			{
				$this->db->sql_query("SELECT *,CASE '$my_user_id' WHEN game_player1_id THEN game_player2_id WHEN game_player2_id THEN game_player1_id WHEN game_player3_id THEN game_player4_id WHEN game_player4_id THEN game_player3_id END player
												FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id='$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->player'");
					$BHZ = $BHZ + $d2->group2user_wins;
				}
			}
			$this->db->sql_query("UPDATE group2user SET group2user_BHZ='$BHZ' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert Fine-BHZ
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$BHZ = 0;
			if($this->system=='Doppel_dynamisch')
			{
				//Get own BHZ
				$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$my_user_id'");
				$BHZ = $d2->group2user_BHZ;

				//Get all games with current player involved
				$this->db->sql_query("SELECT * FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id = '$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$p1_wins=0; $p2_wins=0; $p3_wins=0; $p4_wins=0;

					//Get BHZ for all involved players
					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player1_id'");
					$p1_wins = $d2->group2user_BHZ;

					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player2_id'");
					$p2_wins = $d2->group2user_BHZ;

					if($d->game_player3_id>0)
					{
						$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player3_id'");
						$p3_wins = $d2->group2user_BHZ;

						$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->game_player4_id'");
						$p4_wins = $d2->group2user_BHZ;
					}

					//Calculcate FBHZ by subtracting BHZ of partner and add BHZ of both opponents
					if($d->game_player1_id==$my_user_id) { $BHZ = $BHZ - $p3_wins + $p2_wins + $p4_wins; }
					if($d->game_player2_id==$my_user_id) { $BHZ = $BHZ - $p4_wins + $p1_wins + $p3_wins; }
					if($d->game_player3_id==$my_user_id) { $BHZ = $BHZ - $p1_wins + $p2_wins + $p4_wins; }
					if($d->game_player4_id==$my_user_id) { $BHZ = $BHZ - $p2_wins + $p1_wins + $p3_wins; }
				}
			}
			else
			{
				$this->db->sql_query("SELECT *,CASE '$my_user_id' WHEN game_player1_id THEN game_player2_id WHEN game_player2_id THEN game_player1_id WHEN game_player3_id THEN game_player4_id WHEN game_player4_id THEN game_player3_id END player
												FROM games
												WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id' OR game_player3_id='$my_user_id' OR game_player4_id='$my_user_id')");

				while($d = $this->db->get_next_res())
				{
					$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$tournament_id' AND group2user_user_id='$d->player'");
					$BHZ = $BHZ + $d2->group2user_BHZ;
				}
			}
			$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$BHZ' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//if doubles is played, combine the BHZ of the two players
		if($this->system=='Doppel_fix')
		{
			$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
			$this->db2 = clone($this->db);
			$arr_done = array();
			while($my_user = $users->get_next_res())
			{
				if($my_user->group2user_partner_id>0)
				{
					if(!in_array($my_user->group2user_user_id,$arr_done))
					{
						$arr_done[] = $my_user->group2user_user_id;
						$arr_done[] = $my_user->group2user_partner_id;
						$BHZ=0; $FBHZ=0;
						$d2 = $this->db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id' AND group2user_user_id='$my_user->group2user_partner_id'");
						$BHZ = ($d2->group2user_BHZ + $my_user->group2user_BHZ) / 2;
						$FBHZ = ($d2->group2user_FBHZ + $my_user->group2user_FBHZ) / 2;
						$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$FBHZ', group2user_BHZ='$BHZ' WHERE group2user_user_id='$my_user->group2user_user_id' AND group2user_group_id='$tournament_id'");
						$this->db->sql_query("UPDATE group2user SET group2user_FBHZ='$FBHZ', group2user_BHZ='$BHZ' WHERE group2user_user_id='$d2->group2user_user_id' AND group2user_group_id='$tournament_id'");
					}
				}
			}
		}
	}

	function update_winners()
	{
		$tournament_id = $this->id;

		$users = clone($this->db);

		//Insert count of wins
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$this->db->sql_query("SELECT * FROM games WHERE game_group_id='$tournament_id' AND game_winner_id='$my_user_id'");
			$wins = $this->db->count();
			$this->db->sql_query("UPDATE group2user SET group2user_wins='$wins' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");
		}

		//Insert set/points won
		$users->sql_query("SELECT * FROM group2user WHERE group2user.group2user_group_id = '$tournament_id'");
		while($my_user = $users->get_next_res())
		{
			$my_user_id = $my_user->group2user_user_id;
			$sets_won = 0; $sets_loose = 0;
			$points_won = 0; $points_loose = 0;
			$this->db->sql_query("SELECT * FROM games
											WHERE game_group_id='$tournament_id' AND (game_player1_id = '$my_user_id' OR game_player2_id='$my_user_id')");

			while($d = $this->db->get_next_res())
			{
				if($d->game_player1_id==$my_user_id)
				{
					$points_won = $points_won + $d->game_set1_p1 + $d->game_set2_p1 + $d->game_set3_p1;
					$points_loose = $points_loose + $d->game_set1_p2 + $d->game_set2_p2 + $d->game_set3_p2;
					if($d->game_set1_p1>$d->game_set1_p2) { $sets_won++; } if($d->game_set1_p2>$d->game_set1_p1) { $sets_loose++; }
					if($d->game_set2_p1>$d->game_set2_p2) { $sets_won++; } if($d->game_set2_p2>$d->game_set2_p1) { $sets_loose++; }
					if($d->game_set3_p1>$d->game_set3_p2) { $sets_won++; } if($d->game_set3_p2>$d->game_set3_p1) { $sets_loose++; }
				}
				else
				{
					$points_won = $points_won + $d->game_set1_p2 + $d->game_set2_p2 + $d->game_set3_p2;
					$points_loose = $points_loose + $d->game_set1_p1 + $d->game_set2_p1 + $d->game_set3_p1;
					if($d->game_set1_p2>$d->game_set1_p1) { $sets_won++; } if($d->game_set1_p1>$d->game_set1_p2) { $sets_loose++; }
					if($d->game_set2_p2>$d->game_set2_p1) { $sets_won++; } if($d->game_set2_p1>$d->game_set2_p2) { $sets_loose++; }
					if($d->game_set3_p2>$d->game_set3_p1) { $sets_won++; } if($d->game_set3_p1>$d->game_set3_p2) { $sets_loose++; }
				}
			}

			$my_sets = $sets_won - $sets_loose;
			$my_points = $points_won - $points_loose;

			$this->db->sql_query("UPDATE group2user SET group2user_BHZ='$my_sets',group2user_FBHZ='$my_points' WHERE group2user_user_id='$my_user_id' AND group2user_group_id='$tournament_id'");

			//Special für Direktbegegnungen bei 2 Spielern mit gleich viel Siegen
			if($this->counting=='win')
			{
				$this->db->sql_query("UPDATE group2user SET group2user_BHZ='0' WHERE group2user_group_id='$tournament_id'");
				$users->sql_query("SELECT COUNT(*) as anz, MAX(group2user_wins) as anz_wins FROM group2user WHERE group2user.group2user_group_id = '$tournament_id' GROUP BY group2user_wins HAVING COUNT(*)='2'");
				$temp = clone($users);
				while($d = $users->get_next_res())
				{
					$temp->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '$tournament_id' AND group2user_wins = '".$d->anz_wins."'");
					$d_p1 = $temp->get_next_res();
					$d_p2 = $temp->get_next_res();

					$p1 = $d_p1->group2user_user_id;
					$p2 = $d_p2->group2user_user_id;

					$temp->sql_query("SELECT * FROM games WHERE (game_player1_id = '$p1' AND game_player2_id = '$p2' AND game_group_id='$tournament_id') OR (game_player2_id = '$p1' AND game_player1_id = '$p2' AND game_group_id='$tournament_id')");
					if($temp->count()==1)
					{
						$d_temp = $temp->get_next_res();
						$this->db->sql_query("UPDATE group2user SET group2user_BHZ='1' WHERE group2user_user_id='".$d_temp->game_winner_id."' AND group2user_group_id='$tournament_id'");
					}

				}
			}


		}

	}
	
	function objectArrayDiffByProperty(array $all, array $subset, string $property): array {
    $subset_values = array_map(fn($obj) => $obj->$property, $subset);

    return array_filter($all, fn($obj) => !in_array($obj->$property, $subset_values));
	}
	
	function add_partner($id) {
		$arr_undefined_players = [];
		$arr_undefined_players = array_filter($this->arr_players, function ($player) use ($arr_undefined_players) {
			return !in_array($player->id, $arr_undefined_players);
		});
		$this->add_team($arr_undefined_players[0]->id,$id);
		$this->save();
	}

  function get_users_for_teams()
  {
		$x = "<h1>Teilnehmer</h1>";

		$arr_defined_players = [];
		foreach ($this->arr_teams as $team) {
			foreach ($team->arr_players as $player) {
				$arr_defined_players[$player->id] = $player;
			}
		}	
		$arr_undefined_players = [];
		$arr_undefined_players = array_filter($this->arr_players, function ($player) use ($arr_undefined_players) {
			return !in_array($player->id, $arr_undefined_players);
		});
  	
		foreach ($arr_undefined_players as $key => $my_user) {
			if($key > 0) {
				$x.= $my_user->get_picture();
			}
		}
  	return $x;
  }


	function get_partner_definition()
	{
		$html = ""; $i=1;
		$arr_defined_players = [];

		//Show all teams and calculated missing players
		foreach($this->arr_teams as $t) {
			$arr_defined_players[] = $t->arr_players[0]->id;
			$arr_defined_players[] = $t->arr_players[1]->id;
			$team_id = $i-1;
			$html.= "<div data-team-id='{$team_id}' class='team'>";
			$html.= "Team {$i}<p>";
			$html.= "<div class='teams-player-left'>{$t->arr_players[0]->get_picture()}</div>";
			$html.= "<div class='teams-player-right'>{$t->arr_players[1]->get_picture()}</div>";
			$html.= "<button class='delete' onclick='delete_team({$t->arr_players[0]->id});'>Team löschen</button>";
			$html.= "</div>";
			$i++;
		}

		$arr_undefined_players = [];
		$arr_undefined_players = array_filter($this->arr_players, function ($player) use ($arr_undefined_players) {
			return !in_array($player->id, $arr_undefined_players);
		});

		//show first missing player
		$html.= "<div class='team'>";
		$html.= "Team {$i}<p>";
		$html.= "<div class='teams-player-left'>{$arr_undefined_players[0]->get_picture()}</div>";
		$html.= "<div class='teams-player-right'><img src='".level."inc/imgs/undefined.png'/></div>";
		$html.= "</div>";

		return $html;
	}

	function get_seeding_definition()
	{
		$x = "";
		$arr_users = array();

		$anz_seeds = round(count($this->arr_players)/2,0);
		if($anz_seeds>8) { $anz_seeds = 8; }

		for($i=1;$i <= $anz_seeds;$i++)
		{
			$x.= "<div style='text-align:center;float:left;width:140px;height:180px;border:1px solid gray;border-radius:1vw;padding:1vw;margin:0.5vw;'>";
			$x.= "Setzplatz $i<p>";
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded= :curr_seed",array('t_id'=>$this->id, 'curr_seed'=>$i));

			if($this->db->count()>0)
			{
				$data = $this->db->get_next_res();
				$myUser = new user($data->group2user_user_id);
				$x.= "<div style='margin:auto;width:120px;'>".$myUser->get_picture()."</div>";
			}
			else
			{
				$x.="<img style='width:120px;' src='".level."/inc/imgs/question.png' />";
			}
			$x.= "</div>";
		}
		$x.= "";
		return $x;
	}

	function get_all_tournaments()
	{
		$x = "";
		$db2 = clone($this->db);
		$w_str = "WHERE group_archived!='1'";
		//Check permissions
		$this->db->sql_query("SELECT * FROM location_permissions
													LEFT JOIN locations ON loc_permission_loc_id = location_id
													WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
		if($this->db->count()==0) { $w_str.= " AND user_id=0"; } else { $w_str.= " AND ("; }
		$i=0;
		while($d = $this->db->get_next_res())
		{
			if($i==0) { $w_str.= "group_created_by_location='$d->location_id'"; } else { $w_str.= " OR group_created_by_location='$d->location_id'"; }
			$i++;
		}
		if($this->db->count()>0) { $w_str.= ")"; }

		$this->db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as c_date FROM groups $w_str ORDER BY group_created DESC");
		$x = "<table style='width:95%;'><tr><td colspan='2'><h1>Turniere</h1></td></tr>";
		while($data = $this->db->get_next_res())
		{
			$x.= "<tr><td><h3 style='margin-bottom:5px;'>".$data->group_title."</h3><span style='font-style:italic;'>".$data->c_date."</span></td>";
			$x.= "<td style='text-align:right;width:100px;'><button style='background-color:#EEE;margin-right:1px;border:1px solid #CCC;' onclick='get_tournament_form($data->group_id)'><img src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' /></button>";
			$x.= "<button style='background-color:#EEE;margin-right:1px;border:1px solid #CCC;' onclick='window.location = \"index.php?tournament_id=$data->group_id\"'><img src='".level."inc/imgs/query/next.png' alt='Laden' /></button>";
			$x.= "<button style='background-color:#EEE;margin:0px;border:1px solid #CCC;' onclick='delete_tournament(".$data->group_id.");'><img src='".level."inc/imgs/query/delete.png' alt='Löschen' /></button></td></tr>";
			$x.= "<tr><td colspan='2'><hr/></td></tr>";
			$my_user = null;
		}
		$x.="</table>";
		return $x;
	}

	function get_tournament_form($id=null)
	{
		if($id!=null) { $this->load($id); }
		$html = "<form id='new_tournament' action='index.php?action=save_tournament' method='post'>";
		$html.= "<input type='hidden' name='tournament_id' value='".$this->id."'>";
		if($this->id!=null) 
		{ 
			$html.= "<h1>Turnier anpassen</h1>"; 
		} 
		else 
		{ 
			$html.= "<h1>Turnier erstellen</h1>"; 
			$this->status = "New";
		}
		$html.= "	<table style='width:100%;'>";
		$html.= "	<tr><td style='width:150px;'>Turniername:</td><td><input id='tournament_title' name='tournament_title' style='width:80%;' type='text' value='".$this->title."' required /></td></tr>";
		$html.= "	<tr><td style='width:150px;'>Spielsystem:</td>";
		$html.= "	<td>";
		$html.= "	 <select id='tournament_system' name='tournament_system' style='width:80%;'"; if($this->status!='New') { $html.= " disabled"; } $html.=">";
		$html.= "	   <option value='Schoch' "; if($this->system=='Schoch') { $html.= " selected='1'"; } $html.= ">Schoch</option>";
		$html.= "	   <option value='Gruppenspiele' "; if($this->system=='Gruppenspiele') { $html.= " selected='1'"; } $html.= ">Gruppenspiele</option>";
		$html.= "	   <option value='Doppel_dynamisch' "; if($this->system=='Doppel_dynamisch') { $html.= " selected='1'"; } $html.= ">Doppel (dynamische Partner)</option>";
		$html.= "	   <option value='Doppel_fix' "; if($this->system=='Doppel_fix') { $html.= " selected='1'"; } $html.= ">Doppel (fixe Partner)</option>";
		$html.= "	 </select>";
		$html.= "	</td>";
		$html.= "	</tr>";
		$html.= "	<tr><td style='width:150px;'>Zählweise:</td>";
		$html.= "	<td>";
		$html.= "	 <select id='tournament_counting' name='tournament_counting' style='width:80%;'";  if($this->status!='New') { $html.= " disabled"; } $html.=">";
		$html.= "	   <option value='win' "; if($this->counting=='win') { $html.= " selected='1'"; } $html.= ">Nur Sieg</option>";
		$html.= "	   <option value='pointsOneSet' "; if($this->counting=='pointsOneSet') { $html.= " selected='1'"; } $html.= ">Mit Punkten auf ein Satz</option>";
		$html.= "	   <option value='official2sets' "; if($this->counting=='official2sets') { $html.= " selected='1'"; } $html.= ">Offiziell (21 Punkte, 2 Gewinnsätze)</option>";
		$html.= "	   <option value='2sets11points' "; if($this->counting=='2sets11points') { $html.= " selected='1'"; } $html.= ">Verkürzt (11 Punkte, 2 Gewinnsätze, keine Verlängerung)</option>";
		$html.= "	   <option value='2setswinning' "; if($this->counting=='2setswinning') { $html.= " selected='1'"; } $html.= ">2 Gewinnsätze (Punkte frei)</option>";
		$html.= "	 </select>";
		$html.= "	</td>";
		$html.= "	</tr>";
		$html.= "	<tr><td style='vertical-align:top;'>Turnierbeschreibung:</td><td><textarea id='tournament_description' name='tournament_description' style='width:80%;height:100px;'>$this->description</textarea></td></tr>";
		$html.= "	<tr><td style='width:150px;'>Organisator:</td>";
		$html.= "	<td>";
		$html.= "	 <select id='created_by_location' name='created_by_location' style='width:80%;'>";
		$this->db->sql_query("SELECT * FROM location_permissions
										LEFT JOIN locations ON loc_permission_loc_id = location_id
										WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
										ORDER BY location_name");
		while($d = $this->db->get_next_res())
		{
			$html.= "	   <option value='$d->location_id' "; if($this->location!=null && $this->location->id==$d->location_id) { $html.= " selected='1'"; } $html.= ">$d->location_name</option>";
		}
		$html.= "	 </select>";
		$html.= "	</td>";
		$html.= "	</tr>";
		$html.= "	<tr><td><input style='border-radius:5px;background-color:green;color:white;' type='submit' value='Speichern'/></td></tr>";
		$html.= "</table>";
		$html.= "</form>";
		if($this->status=='Closed') { $html.= "<td><button style='background-color:orange;' onclick='window.location=\"index.php?action=reactivate_tournament&tournament_id=".$this->id."\";'>Reaktivieren</button></td>"; }
		return $html;
	}

	function get_users($category,$with_link=true,$group_by='alphabetical')
	{
		$this->db2 = clone($this->db);
		if($category=='unassigned') { $x = "<h1>Spieler</h1>"; }
		if($category=='assigned') { $x = "<h1>Teilnehmer</h1>"; }
		$this->db->sql_query("SELECT * FROM users ORDER by user_account");
		while($data = $this->db->get_next_res())
		{
			$this->db2->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '".$_GET['tournament_id']."' AND group2user_user_id='".$data->user_id."'");
			if($category=='assigned')
			{
				if($this->db2->count()>0)
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture($with_link,'add_user');
					$my_user = null;
				}
			}
			if($category=='unassigned')
			{
				if($data->user_id!='1')
				{
					if($this->db2->count()==0)
					{
						$my_user = new user($data->user_id);
						$x.= $my_user->get_picture($with_link,'add_user');
						$my_user = null;
					}
				}
			}
		}
		return $x;
	}
	
	function get_buttons()
	{
		$html = "";
		//Tournament loaded...
		if($this->id)
		{
			$html.= "<div class='menu_item'><a href='index.php?tournament_id=".$this->id."'><button style='background-color:purple;';>".$this->title."</button></a></div>";

			switch ($this->status) {
				case 'New':
					if($this->system=='Doppel_fix') { $txt = 'Teams definieren'; } else { $txt='Turnier starten'; }
					$html.="<div class='menu_item'><button onclick='start_tournament($this->id);'>$txt</button></div>";
					if(substr($this->system,0,6)!='Doppel') { $html.="<div class='menu_item'><button style='background-color:orange;' onclick='define_seeded_players();'>Setzplätze definieren</button></div>"; }
					break;
				
				case 'define_seeded_players':
					$html.="<div class='menu_item'><button onclick='start_tournament($this->id);'>Turnier starten</button></div>";
					$html.="<div class='menu_item'><button style='background-color:orange;' id='turnier_abbrechen' onclick='stopp_tournament($this->id);'>Abbrechen</button></div>";
					if($this->get_number_of_seedings()>0) { $html.="<div class='menu_item'><button style='background-color:red;' id='delete_last_seeding' onclick='delete_last_seeding($this->id);'>Letzten Setzplatz löschen</button></div>"; }
					break;
				
				case 'Define_teams':
					$html.="<div class='menu_item'><button onclick='start_tournament($this->id);'>Turnier starten</button></div>";
					break;
				
				case 'Started':
					$zus = "";
					$game_open = false;
					$this->db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
					if($this->db->count()>0)
					{
						while($d = $this->db->get_next_res())
						{
							if($d->game_status!='Closed') { $game_open = true; }
							if(isset($_GET['round'])) { if($d->game_round==$_GET['round']) { $zus = "style='background-color:orange;'"; }  else { $zus = ""; } }
	
							$html.="<div class='menu_item'><button ".$zus." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=$d->game_round\"';'>Runde $d->game_round</button></div>";
						}
					}
					if(!$game_open AND ($this->system=='Schoch' OR $this->system=='Doppel_dynamisch' OR $this->system=='Doppel_fix'))
					{
						if(isset($_GET['round'])) { if($this->get_rounds()==$_GET['round']) { $zus = "style='background-color:orange;'"; } else { $zus = ""; } }
						$html.="<div class='menu_item'><button ".$zus." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=".$this->get_rounds()."\"';'>Runde ".$this->get_rounds()."</button></div>";
					}
	
					if(isset($_GET['round']))
					{
						if($this->system=='Schoch' OR $this->system=='Doppel_dynamisch' OR $this->system=='Doppel_fix')
						{
							$this->db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='".$_GET['round']."'");
							$round_closed = false;
							while($d=$this->db->get_next_res())
							{
								if($d->game_status=='Closed') { $round_closed = true; break; }
							}
							if($this->db->count()>0) { $is_gelost=true; } else { $is_gelost = false; }
	
							if(!$round_closed)
							{
								if($is_gelost) { $zus = "style='display:none;' "; }  else { $zus = ""; }
								$html.="<div class='menu_item'><button id='auslosen' ".$zus."onclick='define_games();'>Auslosen</button></div>";
	
								if(!$is_gelost) { $zus = "style='display:none;' "; $zus2 = "style='display:none;' "; } else { $zus = ""; $zus2 = "style='background-color:red;'"; }
								$html.="<div class='menu_item'><button id='loeschen' ".$zus2."onclick='clear_it();'>Auslosung löschen</button></div>";
								$html.="<div class='menu_item'><button id='runde_schliessen' ".$zus."onclick='close_round();'>Runde abschliessen</button></div>";
							}
							else
							{
								$html.="<div class='menu_item'><button style='background-color:red;' id='runde_schliessen' ".$zus."onclick='reset_round(".$_GET['round'].");'>Auf Runde ".$_GET['round']." zurücksetzen</button></div>";
							}
						}
					}
	
					break;
	
				case 'Closed':
					$this->db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
					if($this->db->count()>0)
					{
						while($d = $this->db->get_next_res())
						{
							$zus_txt = "";
							if($d->game_status!='Closed') { $game_open = true; }
							if(isset($_GET['round'])) { if($d->game_round==$_GET['round']) { $zus_txt = "style='background-color:orange;'"; } }
							$html.="<div class='menu_item'><button ".$zus_txt." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=$d->game_round\"';'>Runde $d->game_round</button></div>";
						}
						$html.="<div class='menu_item'><button style='background-color:olive;' onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&mode=details\"';'>Turnierbericht</button></div>";
					}
					break;

				default:
					break;
			}
		}
		else
		{
			$html.="<div class='menu_item'><button onclick='get_tournament_form();'>Neues Turnier</button></div>";
		}
		return $html;
	}

	function check()
	{
		switch ($this->system) {
			case 'Gruppenspiele':
				if(count($this->arr_players)<3 OR count($this->arr_players)>6) { return "Teilnehmeranzahl für Gruppenspiele muss zwischen 3 und 6 liegen"; }
				break;
			
		}

	}

	function start()
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
			print "OK";
		}
	
		if($this->system=='Schoch')
		{
			if(count($this->arr_players)>3)
			{
				//Teilnehmer ungerade?
				if($players_count % 2 != 0) { $this->add_player(1); }
				$this->status = "Started";
				$this->number_of_courts = ceil($players_count/2);
				$this->save();
				print "OK";
			}
			else
			{
				print "Zu wenig Teilnehmer für Spielsystem Schoch (min. 4 Spieler)";
			}
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
					$db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>'1'),'group2user');
					$players_count++;
				}
				$db->update(array('group_status'=>'Started','group_round'=>'1','group_courts'=>$anz_courts),'groups','group_id',$_GET['tournament_id']);
				print "OK";
			}
			else
			{
				print "Zu wenig Teilnehmer für Doppel (min. 4 Spieler)";
			}
		}
	
		if($this->system=='Doppel_fix')
		{
			if($players_count>7)
			{
				//Teilnehmer ungerade?
				if($players_count % 4 == 0)
				{
					$this->status = "Define_teams";
					$this->number_of_courts = ceil($players_count/4);
					$this->save();
					print "OK";
				}
				else
				{
					print "Teilnehmerzahl muss durch 4 teilbar sein (4,8,12,16, etc.)";
				}
			}
			else
			{
				print "Zu wenig Teilnehmer für Doppel (min. 8 Spieler)";
			}
		}
	
	}



}
?>
