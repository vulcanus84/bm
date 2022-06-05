<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myQuery = new query($db);
    $myQuery->set_default_order_by("permission_user_id");
    $myQuery->set_sql_table("permissions");
    $myQuery->set_sql_select("SELECT * FROM ".$myQuery->get_sql_table()." LEFT JOIN users ON permission_user_id = users.user_id");
    $myQuery->set_edit_mode("full");

    $myColumn = new column("user_firstname","Vorname"); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("user_lastname","Nachname"); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("user_account","Kurzz"); $myColumn->set_width(50); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_path","Pfad"); $myColumn->set_width(250); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_read","Read"); $myColumn->set_width(30); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_write","Write"); $myColumn->set_width(30); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_delete","Add/Delete"); $myColumn->set_width(50); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);

    if(!IS_AJAX)
    {
      //Display page
      $myPage = new page();
      $myPage->set_title("Administration");
      $myPage->set_subtitle("Auswertung");
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