<?php
namespace Tournament;
require_once(level.'inc/php/class_user.php');

class html
{
  private $tournament;
  private $db;

  public function __construct(Tournament $tournament) {
      $this->tournament = $tournament;
      $this->db = $this->tournament->db;
  }

	function get_buttons() {
		$html = "";
		//Tournament loaded...
		if($this->tournament->id)
		{
			$html.= "<div class='menu_item'><button id='tournament_homepage' class='purple'>{$this->tournament->title}</button></div>";

			switch ($this->tournament->status) {
				case 'New':
					if($this->tournament->system=='Doppel_fix') { $txt = 'Teams definieren'; } else { $txt='Turnier starten'; }
					$html.="<div class='menu_item'><button class='green' id='start_tournament'>$txt</button></div>";
					$html.="<div class='menu_item'><button class='orange' id='stop_tournament'>Abbrechen</button></div>";
					if(substr($this->tournament->system,0,6)!='Doppel') { $html.="<div class='menu_item'><button id='define_seedings' class='blue'>Setzplätze definieren</button></div>"; }
					break;
				
				case 'define_seeded_players':
					$html.="<div class='menu_item'><button class='green' id='start_tournament'>Turnier starten</button></div>";
					$html.="<div class='menu_item'><button class='orange' id='stop_tournament'>Abbrechen</button></div>";
					if($this->tournament->number_of_seedings>0) { $html.="<div class='menu_item'><button id='delete_last_seeding' class='red'>Letzten Setzplatz löschen</button></div>"; }
					break;
				
				case 'Define_teams':
					$html.="<div class='menu_item'><button class='green' id='start_tournament'>Turnier starten</button></div>";
					$html.="<div class='menu_item'><button class='orange' id='stop_tournament'>Abbrechen</button></div>";
					break;
				
				case 'Started':
					foreach ($this->tournament->arr_rounds as $round) {
						$class = 'green';
						if(isset($_GET['round'])) { if($round->id==$_GET['round']) { $class = "orange"; }}
						$html.="<div class='menu_item'><button class='change_round $class' data-round='{$round->id}'>Runde {$round->id}</button></div>";
					}
	
					if($this->tournament->system!='Gruppenspiele') {
						$html.="<div class='menu_item'><button class='green' style='display:none;' id='draw'>Auslosen</button></div>";
						$html.="<div class='menu_item'><button class='green' style='display:none;' id='delete_draw'>Auslosung löschen</button></div>";
						$html.="<div class='menu_item'><button class='green' style='display:none;' id='close_round'>Runde abschliessen</button></div>";
						$html.="<div class='menu_item'><button class='red' style='display:none;' id='reset_round' data-round='{$_GET['round']}'>Auf Runde {$_GET['round']} zurücksetzen</button></div>";
					}
					$html.="<div class='menu_item' style='border-left:5px solid gray;margin-left:5px;'><button class='orange' style='display:none;' id='stop_tournament'>Abbrechen</button></div>";
					$html.="<div class='menu_item'><button class='purple' style='display:none;' id='close_tournament'>Abschliessen</button></div>";
	
					break;
	
				case 'Closed':
					foreach ($this->tournament->arr_rounds as $round) {
						if(Count($round->arr_games)==0) { break; }
						$class = 'green';
						if(isset($_GET['round'])) { if($round->id==$_GET['round']) { $class = "orange"; }}
						$html.="<div class='menu_item'><button class='change_round $class' data-round='{$round->id}'>Runde {$round->id}</button></div>";
					}
					$html.="<div class='menu_item'><button id='award_ceremony' class='olive'>Siegerehrung</button></div>";
					$html.="<div class='menu_item'><button id='tournament_report' class='olive'>Turnierbericht</button></div>";
					break;

				default:
					break;
			}
		}
		else
		{
			$html.="<div class='menu_item'><button class='green' onclick='get_tournament_form();'>Neues Turnier</button></div>";
		}
		return $html;
	}

  function get_users_for_teams() {
		$arr_undefined_players = $this->tournament->arr_players;
		foreach ($this->tournament->arr_teams as $team) {
			foreach ($team->arr_players as $player) {
				unset($arr_undefined_players[$player->id]);
			}
		}	

    $players_to_define = Count($arr_undefined_players)-1;
    if($players_to_define==-1) {
      $html = "<h1>Turnier kann gestartet werden</h1>";
    } else {
      $html = "<h1>Partner wählen ({$players_to_define}) </h1>";
    }
  	
		foreach ($arr_undefined_players as $key => $my_user) {
			if($key !== array_key_first($arr_undefined_players)) {
				$html.= $my_user->get_picture();
			}
		}
  	return $html;
  }

	function get_partner_definition() {
		$html = ""; $i=1;
		$arr_undefined_players = $this->tournament->arr_players;

		//Show all teams and calculated missing players
		foreach($this->tournament->arr_teams as $t) {
			unset($arr_undefined_players[$t->arr_players[0]->id]);
			unset($arr_undefined_players[$t->arr_players[1]->id]);
			$html.= "<div data-team-id='{$t->id}' class='team'>";
			$html.= "Team {$i}<p>";
			$html.= "<div class='teams-player-left'>{$t->arr_players[1]->get_picture()}</div>";
			$html.= "<div class='teams-player-right'>{$t->arr_players[0]->get_picture()}</div>";
			$html.= "<button class='red' id='delete_team'>Team löschen</button>";
			$html.= "</div>";
			$i++;
		}

    if(Count($arr_undefined_players)>1) {
      //show first missing player
      $html.= "<div class='team'>";
      $html.= "Team {$i}<p>";
      $html.= "<div class='teams-player-left'>{$arr_undefined_players[array_key_first($arr_undefined_players)]->get_picture()}</div>";
      $html.= "<div class='teams-player-right'><img src='".level."inc/imgs/undefined.png'/></div>";
      $html.= "</div>";
    }

		return $html;
	}

	function get_all_tournaments() {
		$db2 = clone($this->tournament->db);
		$w_str = "WHERE group_archived!='1'";
		//Check permissions
		$this->tournament->db->sql_query("SELECT * FROM location_permissions
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
		$html = "<div class='tournament-list'>";
		while($data = $this->db->get_next_res())
		{
				$html .= "<div class='tournament-item'>";
				$html .= "<div class='tournament-details'>";
				$html .= "<h3>{$data->group_title}</h3>";
				$html .= "<span class='date'>{$data->c_date}</span>";
				$html .= "</div>"; // End tournament-details
				$html .= "<div class='tournament-actions'>";
				$html .= "<button id='edit_tournament' class='gray' data-tournament-id='{$data->group_id}'>
										<img src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' />
									</button>";
				$html .= "<button id='open_tournament' class='gray' data-tournament-id='{$data->group_id}'>
										<img src='".level."inc/imgs/query/next.png' alt='Laden' />
									</button>";
				$html .= "<button id='delete_tournament_permission' class='gray' data-tournament-id='{$data->group_id}'>
										<img src='".level."inc/imgs/query/delete.png' alt='Löschen' />
									</button>";
				$html .= "</div>"; // End tournament-actions
				$html .= "</div>"; // End tournament-item
		}
		$html .= "</div>"; // End tournament-list
		return $html;
		
	}

	function get_tournament_form($id=null) {
		if($id!=null) { $this->tournament->load($id); }
		$html = "<form id='new_tournament' style='display:flex;flex-direction:column;gap:1em;width:90%;'>";

		$html .= "<input type='hidden' name='tournament_id' value='" . $this->tournament->id . "'>";
		
		$html .= "<h1>" . ($this->tournament->id != null ? "Turnier anpassen" : "Turnier erstellen") . "</h1>";
		
		if ($this->tournament->id == null) {
				$this->tournament->status = "New";
		}
		
		// Turniername
		$html .= "<div style='display:flex;flex-direction:column;'>";
		$html .= "<label for='tournament_title'>Turniername:</label>";
		$html .= "<input id='tournament_title' name='tournament_title' type='text' value='" . $this->tournament->title . "' required />";
		$html .= "</div>";
		
		// Spielsystem
		$html .= "<div style='display:flex;flex-direction:column;'>";
		$html .= "<label for='tournament_system'>Spielsystem:</label>";
		$html .= "<select id='tournament_system' name='tournament_system' " . ($this->tournament->status != 'New' ? "disabled" : "") . ">";
		$options = [
				'Schoch' => 'Schoch',
				'Gruppenspiele' => 'Gruppenspiele',
				'Doppel_dynamisch' => 'Doppel (dynamische Partner)',
				'Doppel_fix' => 'Doppel (fixe Partner)'
		];
		foreach ($options as $val => $label) {
				$selected = ($this->tournament->system == $val) ? " selected='1'" : "";
				$html .= "<option value='$val'$selected>$label</option>";
		}
		$html .= "</select></div>";
		
		// Zählweise
		$html .= "<div style='display:flex;flex-direction:column;'>";
		$html .= "<label for='tournament_counting'>Zählweise:</label>";
		$html .= "<select style='width:100%;' id='tournament_counting' name='tournament_counting' " . ($this->tournament->status != 'New' ? "disabled" : "") . ">";
		$countOptions = [
				'win' => 'Nur Sieg',
				'pointsOneSet' => 'Mit Punkten auf ein Satz',
				'official2sets' => 'Offiziell (21 Punkte, 2 Gewinnsätze)',
				'2sets11points' => 'Verkürzt (11 Punkte, 2 Gewinnsätze, keine Verlängerung)',
				'2setswinning' => '2 Gewinnsätze (Punkte frei)'
		];
		foreach ($countOptions as $val => $label) {
				$selected = ($this->tournament->counting == $val) ? " selected='1'" : "";
				$html .= "<option value='$val'$selected>$label</option>";
		}
		$html .= "</select></div>";
		
		// Beschreibung
		$html .= "<div style='display:flex;flex-direction:column;'>";
		$html .= "<label for='tournament_description'>Turnierbeschreibung:</label>";
		$html .= "<textarea id='tournament_description' name='tournament_description' style='height:100px;'>{$this->tournament->description}</textarea>";
		$html .= "</div>";
		
		// Organisator
		$html .= "<div style='display:flex;flex-direction:column;'>";
		$html .= "<label for='created_by_location'>Organisator:</label>";
		$html .= "<select id='created_by_location' name='created_by_location'>";
		$this->db->sql_query("SELECT * FROM location_permissions
				LEFT JOIN locations ON loc_permission_loc_id = location_id
				WHERE loc_permission_user_id = '" . $_SESSION['login_user']->id . "'
				ORDER BY location_name");
		while ($d = $this->db->get_next_res()) {
				$selected = ($this->tournament->location != null && $this->tournament->location->id == $d->location_id) ? " selected='1'" : "";
				$html .= "<option value='$d->location_id'$selected>$d->location_name</option>";
		}
		$html .= "</select></div>";
		
		// Buttons
		$html .= "<div style='display:flex;gap:1em;margin-top:1em;'>";
		
		$html .= "<button type='submit' class='green'>Speichern</button>";
		
		if ($this->tournament->status == 'Closed') {
				$html .= "<button type='button' class='orange' id='reactivate_tournament' data-tournament-id='{$this->tournament->id}'>Reaktivieren</button>";
		}
		
		$html .= "</div>"; // button row
		$html .= "</form>";
		return $html;
		
	}

	function get_assigned_users() {
		$html = "<h1>Teilnehmer (".Count($this->tournament->arr_players).")</h1>";
    foreach($this->tournament->arr_players as $player)
		{
      $html.= $player->get_picture(true);
    }

		return $html;
	}

	function get_users_for_seedings() {
		$html = "<h1>Teilnehmer (".Count($this->tournament->arr_players).")</h1>";
  	$this->db->sql_query("SELECT * FROM group2user
  												LEFT JOIN users ON group2user_user_id = user_id
  												WHERE group2user_group_id='{$this->tournament->id}' AND group2user_seeded = 99
  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
  	while($d = $this->db->get_next_res())
  	{
  		$my_user = new \user($d->group2user_user_id);
  		$html.= "<div class='user_pic' onclick='add_as_seeded($my_user->id);'>".$my_user->get_picture(false,null,'80px',true)."<br/>".$my_user->login."</div>";
  		$my_user = null;
  	}
  	return $html;
  }

  function get_users_from_tournament($mode='default') {
  	$html = "";
  	if($mode!='narrow')
  	{
			$html = "<h1>Teilnehmer (".Count($this->tournament->arr_players).")</h1>";
			if(!isset($_GET['round']))
 			{
 				$html.="<a href='inc/php/match_pdf.php?tournament_id={$this->tournament->id}' target='_blank'>Alle Matchblätter</a>";
 			}
 			else
 			{
 				$html.="<a href='inc/php/match_pdf.php?tournament_id={$this->tournament->id}&round={$_GET['round']}' target='_blank'>Matchblätter Runde {$_GET['round']}</a>";
 			}
			$html.= "<div style='clear:both;height:10px;margin-bottom:10px;border-bottom:2px solid #BBB;'></div>";
  	}

  	$last_wins = "";

    if($this->tournament->system=='Doppel_fix'){ 
      foreach ($this->tournament->arr_teams as $team) {
        if($last_wins!=$team->arr_players[0]->wins)
        {
					if($last_wins) { $html.= "<div style='clear:both;height:10px;margin-bottom:10px;border-bottom:2px solid #BBB;'></div>"; }
          if($team->arr_players[0]->wins<>1) { $txt = "Siege"; } else { $txt = "Sieg"; }
					$html.= "<div class='siege'>{$team->arr_players[0]->wins} {$txt}</div>";
        }
        $last_wins = $team->arr_players[0]->wins;
				$html.= $team->get_info();
        //$html.= "<div class='team_small' id='team_{$team->id}' data-team-id='{$team->id}'>{$team->arr_players[0]->get_picture()}{$team->arr_players[1]->get_picture()}<br/>{$team->arr_players[0]->login} & {$team->arr_players[1]->login}<br/>{$team->arr_players[0]->BHZ}.{$team->arr_players[0]->FBHZ}</div>";
      }

    } else {
      foreach ($this->tournament->arr_players as $player) {
        if($last_wins!=$player->wins)
        {
					if($last_wins) { $html.= "<div style='clear:both;height:10px;margin-bottom:10px;border-bottom:2px solid #BBB;'></div>"; }
          if($player->wins<>1) { $txt = "Siege"; } else { $txt = "Sieg"; }
					$html.= "<div class='siege'>{$player->wins} {$txt}</div>";
        }
        $last_wins = $player->wins;
        $html.= $player->get_picture();
      }
  
    }
  

  	return $html;
  }

  function get_all_users($group_by='alphabetical') {
		$html = "";
		$html.="<img id='alphabetical' class='img_sort' src='".level."inc/imgs/sort_az_descending.png' title='Alphabetisch' alt='Alphabetisch' />";
		$html.="<img id='gender' class='img_sort' src='".level."inc/imgs/male_female.png' title='Geschlecht' alt='Geschlecht' />";
		$html.="<img id='age' class='img_sort' src='".level."inc/imgs/sort_by_age.png' title='Alter' alt='Alter' />";
		$html.="<img id='location' class='img_sort' src='".level."inc/imgs/sort_by_location.png' title='Trainingsort' alt='Trainingsort' />";
		if(isset($_GET['show_hidden']) AND $_GET['show_hidden']=='1')
		{
			$html.="<a href='players.php'><img id='hidden' class='img_sort' src='".level."inc/imgs/show.png' title='Versteckte ausblenden' alt='Versteckte ausblenden' /></a>";
		}
		else
		{
			$html.="<a href='players.php?show_hidden=1'><img id='hidden' class='img_sort' src='".level."inc/imgs/hide.png' title='Ausgeblendete anzeigen' alt='Ausgeblendete anzeigen' /></a>";
		}	
		$html.="<hr>";
		$this->db->sql_query("SELECT * FROM location_permissions
										LEFT JOIN locations ON loc_permission_loc_id = location_id
										WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'
										ORDER BY location_name");
		if($this->db->count()>1)
		{
			if($group_by=='location') {
				$html.="<select name='location' style='width:90%;margin:2.5%;' onchange=\"$('#change_location_filter').submit();\">";
				$html.="<option value=''>-- Alle Standorte --</option>";
				while ($d=$this->db->get_next_res())
				{
					$html.="<option";
					if(isset($_GET['location_filter']) && $_GET['location_filter']==$d->location_id) {$html.=" selected='1'"; }
					$html.=" value='".$d->location_id."'>".$d->location_name."</option>";
				}
				$html.="</select>";
				$html.="<hr style='margin:0px;'>";
			}
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
	
			if($this->tournament->id!=null)
			{
				$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '{$this->tournament->id}'");
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
				$html.= "<h1>Spieler (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new \user($data->user_id);
					$html.= $my_user->get_picture(true,array($my_user->login));
					$my_user = null;
				}
			}
	
			if($group_by=='gender')
			{
				$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
						LEFT JOIN users ON location2user_user_id = users.user_id 
						$w_str AND user_gender='Frau' ORDER BY user_account ASC");

				$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str AND user_gender='Frau' ORDER BY user_account ASC");
				$html.= "<h1>Mädchen (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new \user($data->user_id);
					$html.= $my_user->get_picture(true,array($my_user->login));
					$my_user = null;
				}
				$html.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
				$this->db->sql_query("SELECT DISTINCT user_id, user_account FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str AND user_gender='Herr' ORDER BY user_account ASC");
				$html.= "<h1>Jungs (".$this->db->count().")</h1>";
				while($data = $this->db->get_next_res())
				{
					$my_user = new \user($data->user_id);
					$html.= $my_user->get_picture(true,array($my_user->login));
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
					$html.= "<section id='section_".$data2->location2user_location_id."' style='display:flex;flex-wrap:wrap;border-bottom:1px solid gray;'>";
					$html.= "<h1 style='flex-basis: 100%;'>$data2->location_name (".$this->db->count().")</h1><p/>";
					while($data = $this->db->get_next_res())
					{
						$my_user = new \user($data->user_id);
						$html.= $my_user->get_picture(true,array($my_user->login));
						$my_user = null;
					}
					$html.= "</section>";
				}
			}
	
			if($group_by=='age')
			{
				$db2 = clone($this->db);
				$db2->sql_query("SELECT 
										MIN(user_birthday) AS sort_birthday,
										YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) as diff_years
											FROM location2user 
										LEFT JOIN users ON location2user_user_id = users.user_id 
										$w_str 
										GROUP BY YEAR(CURRENT_DATE) - YEAR(user_birthday) - (DATE_FORMAT(CURRENT_DATE, '%m%d') < DATE_FORMAT(user_birthday, '%m%d')) 
										ORDER BY MIN(user_birthday) IS NULL, diff_years");
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
						$html.= "<h1>Unbekannt (".$this->db->count().")</h1>";
					}
					else
					{
						$html.= "<h1>".$data2->diff_years." Jahre (".$this->db->count().")</h1>";
					}
					while($data = $this->db->get_next_res())
					{
						$my_user = new \user($data->user_id);
						$html.= $my_user->get_picture(true,array($my_user->login));
						$my_user = null;
					}
					$html.="<div style='clear:both;border-bottom:1px solid gray;'>&nbsp;</div>";
				}
			}
		}				


		return $html;
	}

  function get_seeding_definition() {
		$anz_seeds = round(count($this->tournament->arr_players)/2,0);
		if($anz_seeds>8) { $anz_seeds = 8; }
    $html = "";
		for($i=1;$i <= $anz_seeds;$i++)
		{
			$html.= "<div style='text-align:center;float:left;width:140px;height:180px;border:1px solid gray;border-radius:1vw;padding:1vw;margin:0.5vw;'>";
			$html.= "Setzplatz $i<p>";
			$this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded= :curr_seed",array('t_id'=>$this->tournament->id, 'curr_seed'=>$i));

			if($this->db->count()>0)
			{
				$data = $this->db->get_next_res();
				$myUser = new \user($data->group2user_user_id);
				$html.= "<div style='margin:auto;width:120px;'>".$myUser->get_picture()."</div>";
			}
			else
			{
				$html.="<img style='width:120px;' src='".level."/inc/imgs/question.png' />";
			}
			$html.= "</div>";
		}
		return $html;
	}

  function get_report() {
    $html = "<h1>Rangliste</h1>";
    if($this->tournament->system=='Doppel_fix')
    {
      $this->db->sql_query("SELECT MAX(group2user_wins) as group2user_wins,MAX(group2user_BHZ) as group2user_BHZ,MAX(group2user_FBHZ) as group2user_FBHZ,
											GROUP_CONCAT(
												COALESCE(
													NULLIF(TRIM(CONCAT_WS(' ', user_firstname, user_lastname)), ''), 
													user_account
												) SEPARATOR ' & '
											) AS user_full
                      FROM group2user
                      LEFT JOIN users ON group2user_user_id = user_id
                      WHERE group2user_group_id = '$_GET[tournament_id]'
                      GROUP BY group2user_wins
                      ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC");
    }
    else
    {
      $this->db->sql_query("SELECT *, 
												COALESCE(
															NULLIF(CONCAT_WS(' ', user_firstname, user_lastname), ''), 
															user_account
														) AS user_full
                      FROM group2user
                      LEFT JOIN users ON group2user_user_id = user_id
                      WHERE group2user_group_id = '$_GET[tournament_id]'
                      ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
    }

    $html.= "<table style='width:100%; border-collapse: collapse;'>";
    $html.= "<tr>";
    $html.= "<th style='text-align:left;'>Rang</th>";
    $html.= "<th style='text-align:left;'>Spieler</th>";
    $html.= "<th style='text-align:left;'>Siege</th>";
    if($this->tournament->system=='Gruppenspiele')
    {
      $html.= "<th style='text-align:left;'>Satzdifferenz</th>";
      $html.= "<th style='text-align:left;'>Punktedifferenz</th>";
    }
    else
    {
      $html.= "<th style='text-align:left;'>BHZ</th>";
      $html.= "<th style='text-align:left;'>FBHZ</th>";
    }
    $html.= "</tr>";
    $rang = 1;
    while($d = $this->db->get_next_res())
    {
      $html.= "<tr>";
      $html.= "<td style='border-bottom:1px solid black;padding:5px;'>".$rang."</td>";
      $html.= "<td style='border-bottom:1px solid black;'>".$d->user_full."</td>";
      $html.= "<td style='border-bottom:1px solid black;'>".$d->group2user_wins."</td>";
      $html.= "<td style='border-bottom:1px solid black;'>".$d->group2user_BHZ."</td>";
      $html.= "<td style='border-bottom:1px solid black;'>".$d->group2user_FBHZ."</td>";
      $html.= "</tr>";
      $rang++;
    }
    $html.= "</table>";

    $html.= "<h1>Details</h1>";
    $this->db->sql_query("SELECT g.*,
														COALESCE(
															NULLIF(CONCAT_WS(' ', p1.user_firstname, p1.user_lastname), ''), 
															p1.user_account
														) AS p1_user,
														COALESCE(
															NULLIF(CONCAT_WS(' ', p2.user_firstname, p2.user_lastname), ''), 
															p2.user_account
														) AS p2_user,
														COALESCE(
															NULLIF(CONCAT_WS(' ', p3.user_firstname, p3.user_lastname), ''), 
															p3.user_account
														) AS p3_user,
														COALESCE(
															NULLIF(CONCAT_WS(' ', p4.user_firstname, p4.user_lastname), ''), 
															p4.user_account
														) AS p4_user
											FROM games g
											LEFT JOIN users AS p1 ON g.game_player1_id = p1.user_id
											LEFT JOIN users AS p2 ON g.game_player2_id = p2.user_id
											LEFT JOIN users AS p3 ON g.game_player3_id = p3.user_id
											LEFT JOIN users AS p4 ON g.game_player4_id = p4.user_id
											WHERE g.game_group_id = '$_GET[tournament_id]'
											ORDER BY g.game_round ASC, p1.user_account
");

    $html.= "<table style='width:100%;'>";
    $html.= "<th style='text-align:left;'>Spieler 1</th>";
    $html.= "<th style='text-align:left;'>Spieler 2</th>";
    if($this->tournament->counting!='win')
    {
      $html.= "<th style=''>Satz 1</th>";
      $html.= "<th style=''>Satz 2</th>";
      $html.= "<th style=''>Satz 3</th>";
    }
    $last_round = 0;
    while($d = $this->db->get_next_res())
    {
      if($last_round!=$d->game_round)
      {
        $html.= "<tr><td colspan='5' style='text-align:center;background-color:#DDD;font-size:12pt;border:1px solid black;'>Runde ".$d->game_round."</td></tr>";
        $last_round = $d->game_round;
      }
      $html.= "<tr>";
      if(substr($this->tournament->system,0,6)=='Doppel')
      {
        if($d->game_winner_id == $d->game_player1_id) 
        { 
          $zus_txt = 'font-weight:bold;'; 
          $html.= "<td><table><tr><td rowspan='2'><img style='height:25px;' src='".level."inc/imgs/crone.png'></td><td style=".$zus_txt.">".$d->p1_user."</td></tr><tr><td style=".$zus_txt.">".$d->p3_user."</td></tr></table></td>";
        } 
        else 
        { 
          $zus_txt =''; 
          $html.= "<td><table><tr><td rowspan='2'></td><td style=".$zus_txt.">".$d->p1_user."</td></tr><tr><td style=".$zus_txt.">".$d->p3_user."</td></tr></table></td>";
        }

        if($d->game_winner_id == $d->game_player2_id) 
        { 
          $zus_txt = 'font-weight:bold;'; 
          $html.= "<td><table><tr><td rowspan='2'><img style='height:25px;' src='".level."inc/imgs/crone.png'></td><td style=".$zus_txt.">".$d->p2_user."</td></tr><tr><td style=".$zus_txt.">".$d->p4_user."</td></tr></table></td>";
        } 
        else 
        { 
          $zus_txt =''; 
          $html.= "<td><table><tr><td rowspan='2'></td><td style=".$zus_txt.">".$d->p2_user."</td></tr><tr><td style=".$zus_txt.">".$d->p4_user."</td></tr></table></td>";
        }
      }
      else
      {
        if($d->game_winner_id == $d->game_player1_id) {$html.= "<td><img style='height:15px;' src='".level."inc/imgs/crone.png'><b>&nbsp;".$d->p1_user."</b></td>";} else { $html.= "<td>".$d->p1_user."</td>"; }
        if($d->game_winner_id == $d->game_player2_id) {$html.= "<td><img style='height:15px;' src='".level."inc/imgs/crone.png'><b>&nbsp;".$d->p2_user."</b></td>";} else { $html.= "<td>".$d->p2_user."</td>"; }
      }
      if($this->tournament->counting!='win')
      {
        $html.= "<td style='text-align:center;'>".$d->game_set1_p1.":".$d->game_set1_p2."</td>";
        $html.= "<td style='text-align:center;'>".$d->game_set2_p1.":".$d->game_set2_p2."</td>";
        if($d->game_set3_p1>0) { $html.= "<td style='text-align:center;'>".$d->game_set3_p1.":".$d->game_set3_p2."</td>"; }
      }
      $html.= "</tr>";

    }
    $html.= "</table>";
    return $html;
  }

  function get_award_ceremony() {
    $html = "<h1>Siegerehrung</h1>";
    $i = 0;
    $p = array();

    if($this->tournament->system=='Doppel_fix')
    {
      $limit = 6;
      $this->db->sql_query("SELECT * FROM group2user
                            LEFT JOIN users ON group2user_user_id = user_id
                            WHERE group2user_group_id = '$_GET[tournament_id]'
                            ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit");
      $arr_displayed = array();
      $curr_pos=1;
      while($d = $this->db->get_next_res())
      {
        $i++;
        if(!in_array($d->group2user_user_id,$p))
        {
          $p[] = $d->group2user_user_id;
          $p[] = $d->group2user_partner_id;
        }
      }
    }
    else
    {
      $limit = 3;
      $this->db->sql_query("SELECT * FROM group2user
                            LEFT JOIN users ON group2user_user_id = user_id
                            WHERE group2user_group_id = '$_GET[tournament_id]'
                            ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit");
      while($d = $this->db->get_next_res())
      {
        $p[] = $d->group2user_user_id;
      }
      //
      $p[3] = null; $p[4] = null; $p[5] = null;
    }


    $i=3;
    $html.= "<div style='width:100%;'><img style='width:100%;' src='inc/php/podest.php?p1=$p[0]&p2=$p[1]&p3=$p[2]&p4=$p[3]&p5=$p[4]&p6=$p[5]'/>";
    $html.= "</div>";

    $this->db->sql_query("SELECT * FROM group2user
                          LEFT JOIN users ON group2user_user_id = user_id
                          WHERE group2user_group_id = '$_GET[tournament_id]'
                          ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit,100");
    while($d = $this->db->get_next_res())
    {
      $my_user = new \user($d->group2user_user_id);

      if($this->tournament->system=='Doppel_fix')
      {
        if(!in_array($my_user->id,$arr_displayed))
        {
          $i++;
          $db2 = clone($this->db);
          $d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$my_user->id'");
          $partner = new \user($d2->group2user_partner_id);
          $html.= "<div class='ranking'><b>Rang {$i}</b><br/><img src='{$my_user->get_pic_path(true)}'/><img src='{$partner->get_pic_path(true)}'/><br/>{$my_user->login} & {$partner->login}</div>";
          $arr_displayed[] = $my_user->id;
          $arr_displayed[] = $partner->id;
        }
      }
      else
      {
        $i++;
        $html.= "<div class='ranking'><b>Rang ".$i."</b><br/><img src='{$my_user->get_pic_path(true)}'/><br/>{$my_user->login}</div>";
      }
      $my_user = null;
    }
    return $html;

  }

  function get_groupgame_overview() {
    //Show overview of Gruppenspiele
    $anz = Count($this->tournament->arr_players)+1;

    //Generate empty array with the right dimensions
    $arr_table = array();
    $i=0;
    for($i;$i<$anz;$i++)
    {
      $j=0;
      for($j;$j<$anz;$j++)
      {
        $arr_table[$i][$j] = "<td style='text-align:center;'></td>";
      }
    }

    //Fill static fields
    $i=1;
    $arr_table[0][0] = "<td></td>";
    for($i;$i<$anz;$i++)
    {
      $arr_table[$i][$i] = "<td style='background: repeating-linear-gradient(45deg, transparent, #CCC 10px);'></td>";
    }

    //Fill Players
    $i = 1;
    foreach($this->tournament->arr_players as $player) {
      $arr_table[0][$i] = "<td style='text-align:center;border-left:1px solid gray;'><img src='{$player->get_pic_path(true)}' style='width:100px;'/></td>";
      $arr_table[$i][0] = "<td style='text-align:center;border-top:1px solid gray;'><img src='{$player->get_pic_path(true)}' style='width:100px;'/></td>";
      $i++;
    }

    //Fill games
    $i = 1;
    foreach($this->tournament->arr_players as $player) {
      $this->db->sql_query("SELECT * FROM games WHERE game_group_id='{$this->tournament->id}' AND (game_player1_id='{$player->id}' OR game_player2_id='{$player->id}')");
      while($d = $this->db->get_next_res())
      {
        if($d->game_player1_id == $player->id) { $opponent = $d->game_player2_id; $player_is_first_player = true; } else { $opponent = $d->game_player1_id; $player_is_first_player = false; }
        $j = 1;
        foreach($this->tournament->arr_players as $player_tmp)
        {
          if($player_tmp->id==$opponent)
          {
            if($d->game_winner_id=='')
            {
              $arr_table[$i][$j] = "<td style='text-align:center;border:1px solid gray;'>Spiel in Runde<br/><span style='font-weight:bold;'>".$d->game_round."</span></td>";
            }
            else
            {
              if($player_is_first_player)
              {
                if($d->game_set1_p1<1 AND $d->game_set1_p2<1)
                {
                  $myUser = new \user($d->game_winner_id);
                  $txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
                }
                else
                {
                  $txt = "<span style='font-size:16pt;font-weight:bold;'>".$d->game_set1_p1.":".$d->game_set1_p2;
                  if($d->game_set2_p1>0 OR $d->game_set2_p2>0) {  $txt .= "<br/>".$d->game_set2_p1.":".$d->game_set2_p2; }
                  if($d->game_set3_p1>0 OR $d->game_set3_p2>0) {  $txt .= "<br/>".$d->game_set3_p1.":".$d->game_set3_p2; }
                  $txt.= "</span>";
                }
              }
              else
              {
                if($d->game_set1_p1<1 AND $d->game_set1_p2<1)
                {
                  $myUser = new \user($d->game_winner_id);
                  $txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
                }
                else
                {
                  $txt = "<span style='font-size:16pt;font-weight:bold;'>".$d->game_set1_p2.":".$d->game_set1_p1;
                  if($d->game_set2_p1>0 OR $d->game_set2_p2>0) {  $txt .= "<br/>".$d->game_set2_p2.":".$d->game_set2_p1; }
                  if($d->game_set3_p1>0 OR $d->game_set3_p2>0) {  $txt .= "<br/>".$d->game_set3_p2.":".$d->game_set3_p1; }
                  $txt.= "</span>";
                }
              }

              $arr_table[$i][$j] = "<td style='text-align:center;border-left:1px solid gray;border-top:1px solid gray;'>".$txt."</td>";
          }
          }
          $j++;
        }
      }
      $i++;
    }
    //Print array as table
    $html ="<table border='0' style='width:100%;cell-padding:0px;border-collapse: collapse;'>";
    foreach($arr_table as $row)
    {
      $html.="<tr>";
      foreach($row as $col)
      {
        $html.=$col;
      }
      $html.="</tr>";
    }
    $html.="</table>";
    return $html;
  }

  function debug_out_class() {
    //Debug out
    $html = "<h1>Teams</h1>";
    foreach ($this->tournament->arr_teams as $key => $value) {
      $html.= $key." -> ".$value->arr_players[0]->firstname." & ".$value->arr_players[1]->firstname."<br/>";
    }

    $html.= "<h1>Players</h1>";
    $html.= "<table cellpadding='5' border='1'>";
    $html.= "<tr><th>ID</th><th>Login</th><th>Wins</th><th>BHZ</th><th>FBHZ</th></tr>";
    foreach ($this->tournament->arr_players as $key => $value) {
      $html.= "<tr><td>$key</td><td>$value->login</td><td>$value->wins</td><td>$value->BHZ</td><td>$value->FBHZ</td></tr>";
    }
    $html.= "</table><p/>";

    $html.= "<h1>Rounds & Games</h1>";
    foreach ($this->tournament->arr_rounds as $key => $round) {
      $html.= "<h2>Runde ".$round->id."</h2>";
      foreach ($round->arr_games as $key => $game) {
        $html.= "<table cellpadding='5' border='1'>";
        $html.= "<tr><th>Court</th><th>Left side</th><th>Right side</th><th>Status</th><th>Winners</th></tr>";
        if(isset($game->p1)) { $p1 = $game->p1->firstname; } else { $p1 = "<i>Undefined</>"; }
        if(isset($game->p2)) { $p2 = $game->p2->firstname; } else { $p2 = "<i>Undefined</>"; }
        if(isset($game->p3)) { $p3 = $game->p3->firstname; } else { $p3 = "<i>Undefined</>"; }
        if(isset($game->p4)) { $p4 = $game->p4->firstname; } else { $p4 = "<i>Undefined</>"; }
        if(isset($game->t1)) { $t1 = $game->t1->id; } else { $t1 = "<i>Undefined</>"; }
        if(isset($game->t2)) { $t2 = $game->t2->id; } else { $t2 = "<i>Undefined</>"; }
        $html.= "<tr><td rowspan='3'>Court ".$game->location."</td><td>P1: $p1</td><td>P2: $p2</td><td rowspan='3'>".$game->status."</td><td>".$game->winner->login."</td></tr>";
        $html.= "<tr><td>P3: $p3</td><td>P4: $p4</td><td>".$game->winner2?->login."</td></tr>";
        $html.= "<tr><td>T1: $t1</td><td>T2: $t2</td></tr>";
        $html.= "</table><p/>";
      }
    }
    return $html;
  }
}