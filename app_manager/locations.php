<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  

  try
  {
    $myPage = new page();
    $myQuery = new query();
    $myQuery->set_default_order_by("location_name");
    $myQuery->set_default_sort_dir("ASC");
    $myQuery->set_sql_table("locations");
    $myQuery->set_edit_mode("full");

    $myCol = new column("location_name","Trainingsort"); $myQuery->add_column($myCol);
    $myCol = new column("location_description","Beschreibung"); $myCol->set_edit_typ('area'); $myCol->set_width(500); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Trainingsorte");
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