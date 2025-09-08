<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myQuery = new query();
    $myQuery->set_default_order_by("log_id");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("log");
    $myQuery->set_edit_mode("remove");

    $myColumn = new column("log_id","ID"); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("log_category","Kategorie"); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("log_date","Zeitpunkt");  $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("log_user","Benutzer"); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("log_text","Text");  $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);

    if(!IS_AJAX)
    {
      //Display page
      $myPage = new page();
      $myPage->set_title("Administration");
      $myPage->set_subtitle("Log");
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