<?php
namespace Tournament;
/*

*/

class game
{
	public $id;
  public $round;
	private $db;
  public $status;
  public $location;
  
  public $p1;
  public $p2;
  public $p3; //Partner of Player 1
  public $p4; //Partner of Player 2
  public $winner;
  public $winner2;
  public $set1_p1_points;
  public $set1_p2_points;
  public $set2_p1_points;
  public $set2_p2_points;
  public $set3_p1_points;
  public $set3_p2_points;

  public $t1;
  public $t2;

  public $created_on;
  public $started_on;
  public $stopped_on;
  public $duration;
  
	function __construct($round, $id=null) {
    //Get database connection
		$this->db = \db::getInstance(); //with new db() each user displayed on page would get his own db connection (can be 100 users or even more)
    $this->round = $round;
		if($id!=null) { $this->load($id); }
	}

  function load($id) {
		$this->db->sql_query("SELECT * FROM games WHERE game_id= :id",array('id'=>$id));
		if($this->db->count()==1)
		{
			$data = $this->db->get_next_res();
			$this->id = $id;
			$this->status = $data->game_status;
			$this->location = $data->game_location;

      if($data->game_player1_id!==null) { $this->p1 = $this->round->tournament->arr_players[$data->game_player1_id];  }
      if($data->game_player2_id!==null) { $this->p2 = $this->round->tournament->arr_players[$data->game_player2_id]; }
      if($data->game_player3_id!==null) { 
        $this->p3 = $this->round->tournament->arr_players[$data->game_player3_id]; 
        if($data->game_player4_id>0) { $this->p4 = $this->round->tournament->arr_players[$data->game_player4_id]; }
        if(Count($this->round->tournament->arr_teams)>0) {
          $team_id1 = $this->round->tournament->calc->calc_team_id($this->p1->id,$this->p3->id);
          $this->t1 = $this->round->tournament->arr_teams[$team_id1];
          $team_id2 = $this->round->tournament->calc->calc_team_id($this->p2->id,$this->p4->id);
          $this->t2 = $this->round->tournament->arr_teams[$team_id2];
        }
      }
      if($data->game_winner_id!==null) { $this->winner = $this->round->tournament->arr_players[$data->game_winner_id]; }
      if($data->game_winner2_id!==null) { $this->winner2= $this->round->tournament->arr_players[$data->game_winner2_id]; }

      if($data->game_set1_p1!==null) { $this->set1_p1_points= $data->game_set1_p1; }
      if($data->game_set1_p2!==null) { $this->set1_p2_points= $data->game_set1_p2; }
      if($data->game_set2_p1!==null) { $this->set2_p1_points= $data->game_set2_p1; }
      if($data->game_set2_p2!==null) { $this->set2_p2_points= $data->game_set2_p2; }
      if($data->game_set3_p1!==null) { $this->set3_p1_points= $data->game_set3_p1; }
      if($data->game_set3_p2!==null) { $this->set3_p2_points= $data->game_set3_p2; }

      if($data->game_created_on!==null) { $this->created_on = new \DateTime($data->game_created_on); }
      if($data->game_started_on!==null) { $this->started_on = new \DateTime($data->game_started_on); }
      if($data->game_stopped_on!==null) { $this->stopped_on = new \DateTime($data->game_stopped_on); }

      if(isset($this->started_on) && isset($this->stopped_on)) {
        $interval = $this->started_on->diff($this->stopped_on);
        $this->duration = ($interval->days * 24 * 60 * 60) + 
                          ($interval->h * 60 * 60) + 
                          ($interval->i * 60) + 
                          $interval->s;
      }

    }
  }

  function delete() {
    $this->db->delete('games','game_id',$this->id);
    $this->round->remove_game($this);
  }

  function save() {
    $arr_fields = [];
    //Connection to tournament
    $arr_fields['game_group_id'] = $this->round->tournament->id;
    $arr_fields['game_round'] = $this->round->id;

    //Players (all of class \user)
    if($this->p1!==null) { $arr_fields['game_player1_id'] = $this->p1->id; } else { $arr_fields['game_player1_id'] = null; }
    if($this->p2!==null) { $arr_fields['game_player2_id'] = $this->p2->id; } else { $arr_fields['game_player2_id'] = null; }
    if($this->p3!==null) { $arr_fields['game_player3_id'] = $this->p3->id; } else { $arr_fields['game_player3_id'] = null; }
    if($this->p4!==null) { $arr_fields['game_player4_id'] = $this->p4->id; } else { $arr_fields['game_player4_id'] = null; }
    if($this->winner!==null) { $arr_fields['game_winner_id'] = $this->winner->id; } else { $arr_fields['game_winner_id'] = null; }
    if($this->winner2!==null) { $arr_fields['game_winner2_id'] = $this->winner2->id; } else { $arr_fields['game_winner2_id'] = null; }

    //General information
    $arr_fields['game_status'] = $this->status;
    $arr_fields['game_location'] = $this->location;

    //Points
    $arr_fields['game_set1_p1'] = $this->set1_p1_points;
    $arr_fields['game_set1_p2'] = $this->set1_p2_points;
    $arr_fields['game_set2_p1'] = $this->set2_p1_points;
    $arr_fields['game_set2_p2'] = $this->set2_p2_points;
    $arr_fields['game_set3_p1'] = $this->set3_p1_points;
    $arr_fields['game_set3_p2'] = $this->set3_p2_points;
  
    //Update or insert
		try {
			if($this->id!=null)
			{
				if(count($arr_fields)>0) { $this->db->update($arr_fields,'games','game_id',$this->id); }
			}
			else
			{
				$this->db->insert($arr_fields,'games');
			}
		} catch (\Throwable $th) {
			print $th->getMessage();
		}

  }
}