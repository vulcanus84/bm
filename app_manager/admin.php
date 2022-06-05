<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  require_once(level."inc/php/class_tournament.php");

  try
  {
		$txt = "";
    $myPage = new page();
		
		if(isset($_GET['action']) && $_GET['action']=='BHZ')
  	{
			$db->sql_query("SELECT * FROM groups WHERE group_archived='0'");
  		while($d=$db->get_next_res())
  		{
		    $myTournament = new tournament(clone($db),$d->group_id);
				$myTournament->calc_BHZ();
				$myTournament = null;
				$txt.= $d->group_title.'<br/>';
  		}  		
  	}

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Gruppen");
      $myPage->add_content("<a href='admin.php?action=BHZ'><button>BHZ neu berechnen</button></a>");
      $myPage->add_content("<div style='border:1px solid green;'>Results<p>".$txt."</div>");
      print $myPage->get_html_code();
    }
    else
    {
      //Return the requested data
      if(isset($_GET['action']) && $_GET['action']=='BHZ')
      {
  	    $myTournament = new tournament(clone($db));
  			
    		$db->sql_query("SELECT * FROM groups WHERE group_archived='0'");
    		while($d=$db->get_next_res())
    		{
  				$myTournament->calc_BHZ($d->group_id);
  				$txt.= $d->group_title.'<br/>';
  				print $txt;
    		}  		
      }
    }
  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }
?>