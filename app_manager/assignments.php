<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  

  try
  {
    $myPage = new page();
    $myPage->set_title("Manager > BHZ Zuordnungen");
    if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }
    $myQuery = new query();
    $myQuery->set_default_order_by("group_created");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("group2user");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_sql_select("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM group2user
                                LEFT JOIN groups ON group2user_group_id = group_id
                                LEFT JOIN users ON group2user_user_id = user_id");
    $myQuery->set_edit_mode("edit_remove");

    $myCol = new column("group_id","ID"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group_title","Titel"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group_created_c","Datum"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("user_lastname","Nachname"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("user_firstname","Vorname"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group2user_seeded","Setzplatz"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group2user_BHZ","BHZ"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group2user_wins","Gewinne"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);

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