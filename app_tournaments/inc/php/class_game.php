<?php
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

  public $created_on;
  public $started_on;
  public $stopped_on;
  
	function __construct($round, $id=null)
	{
    //Get database connection
		$this->db=$round->tournament->db;
    $this->round = $round;
		if($id!=null) { $this->load($id); }
	}

  function load($id)
  {
		$this->db->sql_query("SELECT * FROM games WHERE game_id= :id",array('id'=>$id));
		if($this->db->count()==1)
		{
			$data = $this->db->get_next_res();
			$this->id = $id;
			$this->status = $data->game_status;
			$this->location = $data->game_location;

      if($data->game_player1_id>0) { $this->p1 = new user($data->game_player1_id); }
      if($data->game_player2_id>0) { $this->p2 = new user($data->game_player2_id); }
      if($data->game_player3_id>0) { $this->p3 = new user($data->game_player3_id); }
      if($data->game_player4_id>0) { $this->p4 = new user($data->game_player4_id); }
      if($data->game_winner_id>0) { $this->winner = new user($data->game_winner_id); }
      if($data->game_winner2_id>0) { $this->winner2= new user($data->game_winner2_id); }

      if($data->game_set1_p1>0) { $this->set1_p1_points= $data->game_set1_p1; }
      if($data->game_set1_p2>0) { $this->set1_p2_points= $data->game_set1_p2; }
      if($data->game_set2_p1>0) { $this->set2_p1_points= $data->game_set2_p1; }
      if($data->game_set2_p2>0) { $this->set2_p2_points= $data->game_set2_p2; }
      if($data->game_set3_p1>0) { $this->set3_p1_points= $data->game_set3_p1; }
      if($data->game_set3_p2>0) { $this->set3_p2_points= $data->game_set3_p2; }

      if($data->game_created_on>0) { $this->created_on = new DateTime($data->game_created_on); }
      if($data->game_started_on>0) { $this->started_on = new DateTime($data->game_started_on); }
      if($data->game_stopped_on>0) { $this->stopped_on = new DateTime($data->game_stopped_on); }

    }
  }

  function delete()
  {
    $this->db->delete('games','game_id',$this->id);
    $this->round->remove_game($this);
  }

  function save()
  {
    $arr_fields = [];
    //Connection to tournament
    $arr_fields['game_group_id'] = $this->round->tournament->id;
    $arr_fields['game_round'] = $this->round->id;

    //Players (all of class user)
    if($this->p1!=null) { $arr_fields['game_player1_id'] = $this->p1->id; }
    if($this->p2!=null) { $arr_fields['game_player2_id'] = $this->p2->id; }
    if($this->p3!=null) { $arr_fields['game_player3_id'] = $this->p3->id; }
    if($this->p4!=null) { $arr_fields['game_player4_id'] = $this->p4->id; }
    if($this->winner!=null) { $arr_fields['game_winner'] = $this->winner->id; }
    if($this->winner2!=null) { $arr_fields['game_winner2'] = $this->winner2->id; }

    //General information
    if($this->status!=null) { $arr_fields['game_status'] = $this->status; }
    if($this->location!=null) { $arr_fields['game_location'] = $this->location; }

    //Points
    if($this->set1_p1_points!=null) { $arr_fields['game_set1_p1'] = $this->$this->set1_p1_points; }
    if($this->set1_p2_points!=null) { $arr_fields['game_set1_p2'] = $this->$this->set1_p2_points; }
    if($this->set2_p1_points!=null) { $arr_fields['game_set2_p1'] = $this->$this->set2_p1_points; }
    if($this->set2_p2_points!=null) { $arr_fields['game_set2_p2'] = $this->$this->set2_p2_points; }
    if($this->set3_p1_points!=null) { $arr_fields['game_set3_p1'] = $this->$this->set3_p1_points; }
    if($this->set3_p2_points!=null) { $arr_fields['game_set3_p2'] = $this->$this->set3_p2_points; }
  
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