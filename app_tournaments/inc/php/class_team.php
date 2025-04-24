<?php
namespace Tournament;

require_once("class_player.php");

class team
{
  private $db;
  private $tournament;
  public $id;
  public $arr_players = []; //Objects of class user

	function __construct($tournament,$id=null,$id2=null)
	{
    //Get database connection
		$this->db = new \db();
    $this->tournament = $tournament;
    if($id!==null && $id2!==null) { $this->load($id,$id2); }
	}

  function load($id,$id2)
  {
    $this->arr_players[] = new player($this->tournament,$id);
    $this->arr_players[] = new player($this->tournament,$id2);
    $this->id = $this->tournament->calc->calc_team_id($id,$id2);
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

  function save()
  {
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
}