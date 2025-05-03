<?php
namespace Tournament;

require_once("class_player.php");

class team
{
  private $db;
  private $tournament;
  public $id;
  public $arr_players = []; //Objects of class user
  public $wins=0;
  public $seeding_no=null;
  public $BHZ=0;
  public $FBHZ=0;
  public $team_name;

	function __construct($tournament,$id=null,$id2=null) {
    //Get database connection
		$this->db = new \db();
    $this->tournament = $tournament;
    if($id!==null && $id2!==null) { $this->load($id,$id2); }
	}

  function load(int $id,int $id2) {
    $this->arr_players[0] = $this->tournament->arr_players[$id];
    $this->arr_players[1] = $this->tournament->arr_players[$id2];
    $this->id = $this->tournament->calc->calc_team_id($id,$id2);
    //Get wins and BHZ from one player, both players have the same in DB
    $this->wins = $this->arr_players[0]->wins;
    $this->BHZ = $this->arr_players[0]->BHZ;
    $this->FBHZ = $this->arr_players[0]->FBHZ;
    $this->team_name = $this->arr_players[0]->firstname." & ".$this->arr_players[1]->firstname;
  }

  function delete() {
    try 
    {
      $this->arr_players[0]->reset();
      $this->arr_players[1]->reset();
      unset($this->tournament->arr_teams[$this->id]);
    } 
    catch (\Throwable $th) 
    {
      print $th->getMessage();
    }
  }

  function save() {
    try 
    {
      $this->arr_players[0]->partner = $this->arr_players[1]; $this->arr_players[0]->save();
      $this->arr_players[1]->partner = $this->arr_players[0]; $this->arr_players[1]->save();
    } 
    catch (\Throwable $th) 
    {
      print $th->getMessage();
    }
  }

  function get_tournament_info() {
    $html = "<div>";
    $html.= "<h1>Bisherige Spiele vom Team (".$this->arr_players[0]->firstname." / ".$this->arr_players[1]->firstname.")</h1><button class='green' id='back_to_round'>Zurück zur Runde</button>";
    $html.= "<table style='width:100%;'>";
    foreach ($this->tournament->arr_rounds as $round) {
      foreach ($round->arr_games as $game) {
        $u1 = null; $u2 = null; $u3 = null; $u4 = null;
        $winner = null;
        $invert = null;
        
        //Change left/positions in a way, that selected player is always left
        if($game->p1->id==$this->arr_players[0]->id OR $game->p1->id==$this->arr_players[1]->id) {
          $invert = true;
          $t1 = $game->t1;
          $t2 = $game->t2;
        } 

        if($game->p2->id==$this->arr_players[0]->id OR $game->p2->id==$this->arr_players[1]->id) {
          $invert = false;
          $t1 = $game->t2;
          $t2 = $game->t1;
        }

        //In invert isn't set, current player was not involved in game
        if($invert!==null) {
          $html.= "<tr>";
          $html.= "<td style='text-align:center;'><h2>Runde ".$game->round->id."</h2></td>";
          $html.= "<td style='text-align:center;'>".$t1->get_info()."</td>";
          $html.= "<td style='text-align:center;'><h2>gegen</h2></td>";
          $html.= "<td style='text-align:center;'>".$t2->get_info()."</td>";
          if($game->winner?->id!='')
          {
            if($this->tournament->counting=='win')
            {
              if($game->winner->id==$this->arr_players[0]->id OR $game->winner2?->id==$this->arr_players[0]->id) {
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
      
              if($game->winner->id==$this->arr_players[0]->id OR $game->winner2?->id==$this->arr_players[0]->id) {
                $html.= "<td style='text-align:center;'><h1 style='color:green;'>".$txt."</h1></td>";
              } else {
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

  function get_info() {
    $html = "<div class='team_small'>";
    foreach ($this->arr_players as $player) {
      $html.= $player->get_picture(false);
    }
    $html.= "<br/>{$this->team_name}";
    $html.= "</div>";
    return $html;
  }

}