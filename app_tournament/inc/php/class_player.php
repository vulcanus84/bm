<?php
namespace Tournament;

class player extends \user
{
  public $tournament_user_id;
  public $tournament;
  public $wins=0;
  public $seeding_no=null;
  public $BHZ=0;
  public $FBHZ=0;
  public $partner;

	function __construct($tournament, $user_id)
	{
    parent::__construct($user_id);
    $this->tournament = $tournament;
    $this->db->sql_query("SELECT * FROM group2user WHERE group2user_group_id={$this->tournament->id} AND group2user_user_id={$this->id}");
    if($this->db->count()==1) 
    { 
      $d = $this->db->get_next_res();
      $this->tournament_user_id = $d->group2user_id;
      $this->wins = $d->group2user_wins;
      $this->seeding_no = $d->group2user_seeded;
      $this->BHZ = $d->group2user_BHZ;
      $this->FBHZ = $d->group2user_FBHZ;
      if($d->group2user_partner_id > 0) { $this->partner = new \user($d->group2user_partner_id); }
    }
	}

  function reset() {
    $this->wins = 0;
    $this->BHZ = 0;
    $this->FBHZ = 0;
    $this->partner = null;
    $this->save();
  }

  function get_picture($thumbnail=false,$arr_text_lines=array()) {
    return parent::get_picture(true, array_merge($arr_text_lines,array($this->login,$this->BHZ.".".$this->FBHZ)));
  }

  function save() {
    try {
      $arr_fields = [
          'group2user_user_id'    => $this->id,
          'group2user_group_id'   => $this->tournament->id,
          'group2user_wins'       => $this->wins ?? null,
          'group2user_seeded'     => $this->seeding_no ?? null,
          'group2user_BHZ'        => $this->BHZ ?? null,
          'group2user_FBHZ'       => $this->FBHZ ?? null,
          'group2user_partner_id' => $this->partner?->id ?? null
      ];

      if($this->tournament_user_id!==null)
      {
        $this->db->update($arr_fields,'group2user','group2user_id',$this->tournament_user_id);
      }
      else
      {
        $this->db->insert($arr_fields,'group2user');
      }
    } catch (\Throwable $th) {
      print $th->getMessage();
    }
  }

  function calc_wins() {
    $wins = 0;
    foreach ($this->tournament->arr_rounds as $round) {
      foreach ($round->arr_games as $game) {
        //Wins
        if($this->id==$game->winner?->id OR $this->id==$game->winner2?->id) { $wins++; }
      }
    }
    $this->wins = $wins;
  }

  function calc_BHZ($mode) {
    $BHZ = 0;
    $sets_won = 0; $sets_loose = 0;
    $points_won = 0; $points_loose = 0;
    foreach ($this->tournament->arr_rounds as $round) {
      foreach ($round->arr_games as $game) {
        switch ($this->tournament->system) {
          case 'Gruppenspiele':
            //Points
            if($this->id==$game->p1?->id) {
              $points_won = $points_won + $game->set1_p1_points + $game->set2_p1_points + $game->set3_p1_points;
              $points_loose = $points_loose + $game->set1_p2_points + $game->set2_p2_points + $game->set3_p2_points;
              if($game->set1_p1_points+$game->set1_p2_points > 0) { if($game->set1_p1_points>$game->set1_p2_points) { $sets_won++; } else { $sets_loose++; } }
              if($game->set2_p1_points+$game->set2_p2_points > 0) { if($game->set2_p1_points>$game->set2_p2_points) { $sets_won++; } else { $sets_loose++; } }
              if($game->set3_p1_points+$game->set3_p2_points > 0) { if($game->set3_p1_points>$game->set3_p2_points) { $sets_won++; } else { $sets_loose++; } }
            } 

            if($this->id==$game->p2?->id) {
              $points_won = $points_won + $game->set1_p2_points + $game->set2_p2_points + $game->set3_p2_points;
              $points_loose = $points_loose + $game->set1_p1_points + $game->set2_p1_points+ $game->set3_p1_points;
              if($game->set1_p1_points+$game->set1_p2_points > 0) { if($game->set1_p1_points<$game->set1_p2_points) { $sets_won++; } else { $sets_loose++; } }
              if($game->set2_p1_points+$game->set2_p2_points > 0) { if($game->set2_p1_points<$game->set2_p2_points) { $sets_won++; } else { $sets_loose++; } }
              if($game->set3_p1_points+$game->set3_p2_points > 0) { if($game->set3_p1_points<$game->set3_p2_points) { $sets_won++; } else { $sets_loose++; } }
            }
            break;
    
          case 'Doppel_fix':
          case 'Schoch':
            //Was player involved in this game?
            if($this->id==$game->p1?->id OR $this->id==$game->p2?->id OR $this->id==$game->p3?->id OR $this->id==$game->p4?->id) {
              //Search for opponent
              if($this->id==$game->p1?->id OR $this->id==$game->p3?->id) { $opponent = $game->p2; } else { $opponent = $game->p1; }
              if($mode=='main') { $BHZ = $BHZ + $opponent->wins; } else { $BHZ = $BHZ + $opponent->BHZ; }
            }
            break;

          case 'Doppel_dynamisch':
            //Get all games with current player involved
            if($this->id==$game->p1?->id OR $this->id==$game->p2?->id OR $this->id==$game->p3?->id OR $this->id==$game->p4?->id) {
              //Calculcate BHZ by subtracting wins of partner and add wins of both opponents
              if($mode=='main') {
                if($game->p1?->id==$this->id) { $BHZ = $BHZ - $game->p3?->wins + $game->p2?->wins + $game->p4?->wins; }
                if($game->p2?->id==$this->id) { $BHZ = $BHZ - $game->p4?->wins + $game->p1?->wins + $game->p3?->wins; }
                if($game->p3?->id==$this->id) { $BHZ = $BHZ - $game->p1?->wins + $game->p2?->wins + $game->p4?->wins; }
                if($game->p4?->id==$this->id) { $BHZ = $BHZ - $game->p2?->wins + $game->p1?->wins + $game->p3?->wins; }
              } else {
                if($game->p1?->id==$this->id) { $BHZ = $BHZ - $game->p3?->BHZ + $game->p2?->BHZ + $game->p4?->BHZ; }
                if($game->p2?->id==$this->id) { $BHZ = $BHZ - $game->p4?->BHZ + $game->p1?->BHZ + $game->p3?->BHZ; }
                if($game->p3?->id==$this->id) { $BHZ = $BHZ - $game->p1?->BHZ + $game->p2?->BHZ + $game->p4?->BHZ; }
                if($game->p4?->id==$this->id) { $BHZ = $BHZ - $game->p2?->BHZ + $game->p1?->BHZ + $game->p3?->BHZ; }
              }
            }
            break;
        }
      }
    }

    switch ($this->tournament->system) {
      case 'Gruppenspiele':
        $my_sets = $sets_won - $sets_loose;
        $my_points = $points_won - $points_loose;
        $this->BHZ = $my_sets;
        $this->FBHZ = $my_points;
        break;

      case 'Schoch':
      case 'Doppel_fix':
      case 'Doppel_dynamisch':
        if($mode=='main') { $this->BHZ = $BHZ; } else { $this->FBHZ = $BHZ; }
        break;
    }
  }

  function get_tournament_info() {
    $html = "<div style='width:100%;'>";
    $html.= "<h1>Bisherige Spiele von ".$this->login."</h1><button class='green' id='back_to_round'>Zurück zur Runde</button>";
    foreach ($this->tournament->arr_rounds as $round) {
      foreach ($round->arr_games as $game) {
        $u1 = null; $u2 = null; $u3 = null; $u4 = null;
        $winner = null;
        $invert = null;
        
        //Change left/positions in a way, that selected player is always left
        if($game->p1->id==$this->id OR $game->p3?->id==$this->id) {
          $invert = true;
          $u1 = $game->p1;
          $u2 = $game->p2;
          $u3 = $game->p3;
          $u4 = $game->p4;
        } 

        if($game->p2->id==$this->id OR $game->p4?->id==$this->id) {
          $invert = false;
          $u1 = $game->p2;
          $u2 = $game->p1;
          $u3 = $game->p4;
          $u4 = $game->p3;
        }

        //In invert isn't set, current player was not involved in game
        if($invert!==null) {
          $html_result = "";
          if($game->winner?->id!='')
          {
            if($this->tournament->counting=='win')
            {
              if($game->winner->id==$this->id OR $game->winner2?->id==$this->id) {
                $html_result.= "<span style='color:green;'>Gewonnen!</span>";
              } else {
                $html_result.= "<span style='color:red;'>Verloren!</span>";
              }
            }
            else
            {
              $txt = "<span style='font-size:16pt;font-weight:bold;'>";
              if($invert)
              {
                if($game->set1_p1_points > 0 OR $game->set1_p2_points > 0) {	$txt .= $game->set1_p1_points.":".$game->set1_p2_points; }
                if($game->set2_p1_points > 0 OR $game->set2_p2_points > 0) {  $txt .= "<br/>".$game->set2_p1_points.":".$game->set2_p2_points; }
                if($game->set3_p1_points > 0 OR $game->set3_p2_points > 0) {  $txt .= "<br/>".$game->set3_p1_points.":".$game->set3_p2_points; }
              }
              else
              {
                if($game->set1_p1_points > 0 OR $game->set1_p2_points > 0) {	$txt .= $game->set1_p2_points.":".$game->set1_p1_points; }
                if($game->set2_p1_points > 0 OR $game->set2_p2_points > 0) {  $txt .= "<br/>".$game->set2_p2_points.":".$game->set2_p1_points; }
                if($game->set3_p1_points > 0 OR $game->set3_p2_points > 0) {  $txt .= "<br/>".$game->set3_p2_points.":".$game->set3_p1_points; }
              }
              $txt.= "</span>";
      
              if($game->winner->id==$this->id OR $game->winner2?->id==$this->id)
              {
                $html_result.= "<span style='color:green;'>".$txt."</span>";
              }
              else
              {
                $html_result.= "<span style='color:red;'>".$txt."</span>";
              }
      
            }
            if($game->duration>0)
            {
              $html_result.= "<p><b>Spieldauer</b><br/>".gmdate("H:i:s", $game->duration)."</p>";
            }
          }
          else
          {
            $html_result.= "<h2 style='font-style:italic;'>Noch nicht gespielt</h2>";
          }

          $left_player = "<div style='display:inline-block;'><img style='width:20vw;max-width:100px;' src='" . $u1->get_pic_path(true) . "'><br/>" . $u1->login . "</div>";
          if (isset($u3)) {
              $left_player .= "<div style='display:inline-block; margin-left:10px;'><img style='width:20vw;max-width:100px;' src='" . $u3->get_pic_path(true) . "'><br/>" . $u3->login . "</div>";
          }
          
          $html_result = "<div><h2>Runde " . $game->round->id . "</h2>" . $html_result . "</div>";
          
          $right_player = "<div style='display:inline-block;'><img style='width:20vw;max-width:100px; cursor:pointer;' src='" . $u2->get_pic_path(true) . "' onclick=\"show_user_games('" . $u2->id . "');\"><br/>" . $u2->login . "</div>";
          if (isset($u4)) {
              $right_player .= "<div style='display:inline-block; margin-left:10px;'><img style='width:20vw;max-width:100px; cursor:pointer;' src='" . $u4->get_pic_path(true) . "' onclick=\"show_user_games('" . $u4->id . "');\"><br/>" . $u4->login . "</div>";
          }
          
          $html .= "<div style='display: flex; justify-content: center; align-items: center; width: 100%; overflow-x: auto;border-bottom:1px solid gray;'>";
          $html .= "<div style='flex: 1; text-align: center;'>{$left_player}</div>";
          $html .= "<div style='flex: 1; text-align: center;'>{$html_result}</div>";
          $html .= "<div style='flex: 1; text-align: center;'>{$right_player}</div>";
          $html .= "</div>";
                    
          
        }
      }
    }
    $html.= "</div>";
    print $html;
  }
}