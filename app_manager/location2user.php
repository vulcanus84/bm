<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myPage->set_title("Manager > Trainingsorte zuweisen");
    if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

    $myQuery = new query();
    $myQuery->set_default_order_by("location_name");
    $myQuery->set_sql_table("location2user");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, users.user_account as player_fullname, DATE_FORMAT(location2user_created_on,'%d.%m.%Y %H:%i:%s') as created_c
                                    FROM location2user
                                    LEFT JOIN locations ON location2user_location_id = locations.location_id
                                    LEFT JOIN users ON location2user_user_id = users.user_id
    													");


    $myCol = new column("created_c","am"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=false;  $myCol->set_filter_column('location2user_created_on'); $myQuery->add_column($myCol);
    
    $db->sql_query("SELECT *  FROM locations ORDER BY location_name");
    $myCol = new column("location2user_location_id","Trainingsort"); $myCol->set_selection_by_sql($db,'location_name','location_id'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as player_fullname FROM users ORDER BY user_account");
    $myCol = new column("location2user_user_id","Spieler"); $myCol->set_selection_by_sql($db,'player_fullname','user_id'); $myCol->set_filter_column('users.user_account'); $myQuery->add_column($myCol);


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