<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  

  try
  {
    $myPage = new page();
    $myQuery = new query();
    $myQuery->set_default_order_by("group_created");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("groups");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM groups LEFT JOIN locations ON group_created_by_location = location_id");

    $myCol = new column("group_title","Titel"); $myQuery->add_column($myCol);
    $myCol = new column("group_created_c","Datum"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=true; $myCol->set_filter_column('group_created'); $myCol->set_save_column('group_created'); $myQuery->add_column($myCol);
    $myCol = new column("group_status","Status"); $myCol->set_selection('New,Started,Closed'); $myQuery->add_column($myCol);
		$db->sql_query("SELECT * FROM locations");
    $myCol = new column("group_created_by_location","Organisator"); $myCol->set_selection_by_sql($db,'location_name','location_id',''); $myQuery->add_column($myCol);
    $myCol = new column("group_system","Spielsystem"); $myCol->set_selection('Gruppenspiele,Schoch,Doppel_dynamisch,Doppel_fix'); $myQuery->add_column($myCol);
    $myCol = new column("group_counting","Zählweise"); $myCol->set_selection('win,pointsOneSet,official2sets,2sets11points,2setswinning'); $myQuery->add_column($myCol);
    $myCol = new column("group_archived","Archiviert"); $myCol->set_edit_typ('checkbox'); $myQuery->add_column($myCol);
    $myCol = new column("group_description","Beschreibung"); $myCol->set_edit_typ('area'); $myCol->set_width(500); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Gruppen");
      $myPage->add_content($myQuery->get_list());
      print $myPage->get_html_code();
    }
    else
    {
      //Return the requested data
      print $myQuery->check_actions();
    }
  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }
?>