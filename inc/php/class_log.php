<?php
//*****************************************************************************
//26.03.2013 Claude Hübscher
//-----------------------------------------------------------------------------
//*****************************************************************************
class log
{
  private $db;
  function __construct($db=null)
  {
    if($db) { $this->db = $db; } else { $this->db = new db(); }
  }

	public function write_to_log($category, $text)
	{
    if(isset($_SESSION['login_user'])) { $log_user = $_SESSION['login_user']->fullname; } else { $log_user='Unknown'; }
  	$arr_fields = array('log_category'=>$category,'log_user'=>$log_user,'log_text'=>$text);
    $this->db->insert($arr_fields,'log');
	}
  
}

?>