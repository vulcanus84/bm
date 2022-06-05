<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)

  try
  {
		if(isset($_GET['user_id'])&& $_GET['user_id']!='')
		{
	    session_destroy();
			session_start();
			$_SESSION['login_user'] = new user($_GET['user_id']);
		}
		header("Location: ".level);
  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }

?>