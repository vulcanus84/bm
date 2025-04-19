<?php
class player extends user
{
  public $tournament_user_id;
  public $tournament;
  public $wins;
  public $seeding_no;
  public $BHZ;
  public $fBHZ;

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
      $this->fBHZ = $d->group2user_FBHZ;
    }
	}

  function save()
  {
    try 
    {
      try {
        if($this->wins!=null) { $arr_fields['group2user_wins'] = $this->$this->wins; }
        if($this->seeding_no!=null) { $arr_fields['group2user_seeded'] = $this->$this->seeding_no; }
        if($this->BHZ!=null) { $arr_fields['group2user_BHZ'] = $this->$this->BHZ; }
        if($this->fBHZ!=null) { $arr_fields['group2user_FBHZ'] = $this->$this->fBHZ; }

        if($this->id!=null)
        {
          if(count($arr_fields)>0) { $this->db->update($arr_fields,'group2user','group2user_id',$this->tournament_user_id); }
        }
        else
        {
          $this->db->insert($arr_fields,'games');
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
}