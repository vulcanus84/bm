<?php 
class location
{
	public $name;
  private $db;

	//Read-only konfiguriert
	protected $id;

	public function __get($name)
	{
		if (isset($this->$name)) { return $this->$name; } else { return null;  }
	}

	public function __set($name, $value)
	{
		if ($name === 'login' OR $name === 'id') { throw new Exception("Error in class User: <br>Not allowed to change property $name"); }
		else { $this->$name = $value; }
	}

	public function __construct($location_id=null)
	{
		$this->db = db::getInstance(); // Immer Singleton
    if($location_id!=null) { $this->load($location_id); }
	}

  public function load($id)
  {
		$data = $this->db->sql_query_with_fetch("SELECT * FROM locations WHERE location_id=:uid",array('uid'=>$id));
    $this->name = $data->location_name;
    $this->id = $id;
  }
}