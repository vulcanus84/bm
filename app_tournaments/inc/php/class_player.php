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

  function save() {
    try 
    {
      try {
        $arr_fields['group2user_user_id'] = $this->id;
        $arr_fields['group2user_group_id'] = $this->tournament->id;
        if($this->wins!=null) { $arr_fields['group2user_wins'] = $this->wins; }
        if($this->seeding_no!=null) { $arr_fields['group2user_seeded'] = $this->seeding_no; }
        if($this->BHZ!=null) { $arr_fields['group2user_BHZ'] = $this->BHZ; }
        if($this->FBHZ!=null) { $arr_fields['group2user_FBHZ'] = $this->FBHZ; }
        if($this->partner!=null) { $arr_fields['group2user_partner_id'] = $this->partner->id; } else { $arr_fields['group2user_partner_id'] = 'NULL'; }

        if($this->tournament_user_id!=null)
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
    catch (\Throwable $th) 
    {
      print $th->getMessage();
    }
  }

  function calc_wins() {
    $wins = 0;
    foreach ($this->tournament->arr_rounds as $round) {
      foreach ($round->arr_games as $game) {
        //Wins
        if($this->id==$game->winner->id OR $this->id==$game->winner2?->id) { $wins++; }
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
            if($this->id==$game->p1->id OR $this->id==$game->p3->id) {
              $points_won = $points_won + $game->set1_p1 + $game->set2_p1 + $game->set3_p1;
              $points_loose = $points_loose + $game->set1_p2 + $game->set2_p2 + $game->set3_p2;
              if($game->set1_p1>$game->set1_p2) { $sets_won++; } else { $sets_loose++; }
              if($game->set2_p1>$game->set2_p2) { $sets_won++; } else { $sets_loose++; }
              if($game->set3_p1>$game->set3_p2) { $sets_won++; } else { $sets_loose++; }
            }
            else {
              $points_won = $points_won + $game->set1_p2 + $game->set2_p2 + $game->set3_p2;
              $points_loose = $points_loose + $game->set1_p1 + $game->set2_p1+ $game->set3_p1;
              if($game->set1_p1<$game->set1_p2) { $sets_won++; } else { $sets_loose++; }
              if($game->set2_p1<$game->set2_p2) { $sets_won++; } else { $sets_loose++; }
              if($game->set3_p1<$game->set3_p2) { $sets_won++; } else { $sets_loose++; }
            }
            break;
    
          case 'Schoch':
            if($this->id==$game->p1?->id OR $this->id==$game->p2?->id OR $this->id==$game->p3?->id OR $this->id==$game->p4?->id) {
              if($mode=='main') { $BHZ = $BHZ + $game->winner->wins; } else { $BHZ = $BHZ + $game->winner->BHZ; }
            }
            break;

          case 'Doppel_dynamisch':
            //Get all games with current player involved
            if($this->id==$game->p1->id OR $this->id==$game->p2->id OR $this->id==$game->p3->id OR $this->id==$game->p4->id) {
              //Calculcate BHZ by subtracting wins of partner and add wins of both opponents
              if($mode=='main') {
                if($game->p1->id==$this->id) { $BHZ = $BHZ - $game->p3->wins + $game->p2->wins + $game->p4->wins; }
                if($game->p2->id==$this->id) { $BHZ = $BHZ - $game->p4->wins + $game->p1->wins + $game->p3->wins; }
                if($game->p3->id==$this->id) { $BHZ = $BHZ - $game->p1->wins + $game->p2->wins + $game->p4->wins; }
                if($game->p4->id==$this->id) { $BHZ = $BHZ - $game->p2->wins + $game->p1->wins + $game->p3->wins; }
              } else {
                if($game->p1->id==$this->id) { $BHZ = $BHZ - $game->p3->BHZ + $game->p2->BHZ + $game->p4->BHZ; }
                if($game->p2->id==$this->id) { $BHZ = $BHZ - $game->p4->BHZ + $game->p1->BHZ + $game->p3->BHZ; }
                if($game->p3->id==$this->id) { $BHZ = $BHZ - $game->p1->BHZ + $game->p2->BHZ + $game->p4->BHZ; }
                if($game->p4->id==$this->id) { $BHZ = $BHZ - $game->p2->BHZ + $game->p1->BHZ + $game->p3->BHZ; }
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
      case 'Doppel_dynamisch':
        if($mode=='main') { $this->BHZ = $BHZ; } else { $this->FBHZ = $BHZ; }
        break;
    }


  }

  function get_player_info() {
    $html = "<div>";
    $html.= "<h1>Bisherige Spiele von ".$this->login."</h1>";
    $html.= "<table style='width:100%;'>";
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
          $html.= "<tr>";
          $html.= "<td style='text-align:center;'><h2>Runde ".$game->round->id."</h2></td>";
          $html.= "<td style='text-align:center;'><img style='width:100px;' src='".$u1->get_pic_path()."'><br/>".$u1->login."</td>";
          if(isset($u3)) { $html.= "<td style='text-align:center;'><img style='width:100px;' src='".$u3->get_pic_path()."'><br/>".$u3->login."</td>"; }
          $html.= "<td style='text-align:center;'><h2>gegen</h2></td>";
          $html.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u2->get_pic_path()."' onclick=\"show_user_games('".$u2->id."');\"><br/>".$u2->login."</td>";
          if(isset($u4)) { $html.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u4->get_pic_path()."' onclick=\"show_user_games('".$u4->id."');\"><br/>".$u4->login."</td>"; }
          if($game->winner->id!='')
          {
            if($this->tournament->counting=='win')
            {
              if($game->winner->id==$this->id OR $game->winner2?->id==$this->id) {
                $html.= "<td style='text-align:center;'><h1 style='color:green;'>Gewonnen!</h1></td>";
              } else {
                $html.= "<td style='text-align:center;'><h1 style='color:red;'>Verloren!</h1></td>";
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
                $html.= "<td style='text-align:center;'><h1 style='color:green;'>".$txt."</h1></td>";
              }
              else
              {
                $html.= "<td style='text-align:center;'><h1 style='color:red;'>".$txt."</h1></td>";
              }
      
            }
            if($game->duration>0)
            {
              $html.= "<td style='text-align:center;font-size:14pt;'>Spieldauer<br/>".gmdate("H:i:s", $game->duration)."</td>";
            }
          }
          else
          {
            $html.= "<td style='text-align:center;' colspan='2'><h2 style='font-style:italic;'>Noch nicht gespielt</h2></td>";
          }
          $html.= "</tr>";
          $html.= "<tr><td colspan='8'><hr/></td></tr>";
  
        }
  

      }
    }
    
    $html.= "</table>";
    $html.= "</div>";
    print $html;
  }
}