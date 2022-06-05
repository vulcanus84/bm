<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("exam_category");
    $myQuery->set_sql_table("exams");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
//    $myQuery->set_sql_select("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM groups");

    $myCol = new column("exam_title","Titel"); $myCol->set_width(300); $myQuery->add_column($myCol);
    $myCol = new column("exam_category","Kategorie"); $myQuery->add_column($myCol);
    $myCol = new column("exam_level","Aufgabe"); $myCol->set_selection('1,2,3,4,5,6,7,8,9,10'); $myQuery->add_column($myCol);
    $myCol = new column("exam_description","Beschreibung"); $myCol->set_edit_typ('area'); $myCol->set_width(500); $myQuery->add_column($myCol);

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