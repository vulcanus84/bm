<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myPage->set_title("Administration");
    $myPage->set_subtitle("Trainingsorte berechtigen");
    if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

    $myQuery = new query();
    $myQuery->set_default_order_by("user_account");
    $myQuery->set_sql_table("location_permissions");
    $myQuery->set_sql_select("SELECT *,CONCAT(user_firstname,' ',user_lastname,' (',user_account,')') as user_fullname FROM ".$myQuery->get_sql_table()." 
    													LEFT JOIN users ON loc_permission_user_id = users.user_id
    													LEFT JOIN locations ON loc_permission_loc_id = locations.location_id
    													");
    $myQuery->set_edit_mode("full");


    $db->sql_query("SELECT *, CONCAT(user_firstname,' ',user_lastname,' (',user_account,')') as user_fullname FROM users ORDER BY user_account");
    $myCol = new column("loc_permission_user_id","Benutzer"); $myCol->set_selection_by_sql($db,'user_fullname','user_id'); $myCol->set_filter_column("CONCAT(user_firstname,' ',user_lastname,' (',user_account,')')"); $myQuery->add_column($myCol);

    $db->sql_query("SELECT * FROM locations ORDER BY location_name");
    $myCol = new column("loc_permission_loc_id","Trainingsort"); $myCol->set_selection_by_sql($db,'location_name','location_id'); $myCol->set_filter_column('location_name'); $myQuery->add_column($myCol);


    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
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