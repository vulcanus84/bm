<?php

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
		$this->db=$tournament->db;
    $this->tournament = $tournament;
    if($id!==null && $id2!==null) { $this->load($id,$id2); }
	}

  function load($id,$id2)
  {
    $this->arr_players[] = new player($this->tournament,$id);
    $this->arr_players[] = new player($this->tournament,$id2);
    $this->id = $id."-".$id2;
  }

  function delete($key) {
    try 
    {
      $this->db->sql_query("UPDATE group2user SET group2user_partner_id=NULL WHERE group2user_user_id='".$this->arr_players[0]->id."' AND group2user_group_id='".$this->tournament->id."'");
      $this->db->sql_query("UPDATE group2user SET group2user_partner_id=NULL WHERE group2user_user_id='".$this->arr_players[1]->id."' AND group2user_group_id='".$this->tournament->id."'");
      unset($this->tournament->arr_teams[$key]);
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
      $this->db->sql_query("UPDATE group2user SET group2user_partner_id='".$this->arr_players[1]->id."' WHERE group2user_user_id='".$this->arr_players[0]->id."' AND group2user_group_id='".$this->tournament->id."'");
      $this->db->sql_query("UPDATE group2user SET group2user_partner_id='".$this->arr_players[0]->id."' WHERE group2user_user_id='".$this->arr_players[1]->id."' AND group2user_group_id='".$this->tournament->id."'");
    } 
    catch (\Throwable $th) 
    {
      print $th->getMessage();
    }
  }
}