<?php
namespace Tournament;

class round
{
	public $id;
  public $tournament;
  public $round_no;
  public $status = "New";
  public $arr_games = []; //Objects of class game

	function __construct($tournament)
	{
    $this->tournament = $tournament;
    $this->id = Count($this->tournament->arr_rounds)+1;
    $this->tournament->curr_round++;
	}

  function delete()
  {
    //Delete all game of this round
    $this->tournament->db->sql_query("DELETE FROM games WHERE game_round='".$this->id."' AND game_group_id='".$this->tournament->id."'");
  }
  
  function add_game($id=null)
  {
    $curr_game = new game($this,$id);
    $curr_game->location = count($this->arr_games)+1;
    if($curr_game->status=='Closed') { $this->status = 'Closed'; } else { $this->status = 'Drawn'; }
    $this->arr_games [] = $curr_game;
		return $curr_game;
  }

  function remove_game($game)
  {
    $this->arr_games = array_filter($this->arr_games,function($curr_game) use ($game) { return $curr_game != $game; });
    $this->arr_games = array_values($this->arr_games);
  }

  function close() {
		foreach ($this->arr_games as $game) {
      $game->status = "Closed";
			$game->save();
		}
    $this->tournament->calc->calc_ranking();
  }

  function save() {
		foreach ($this->arr_games as $game) {
			$game->save();
		}	
  }
}