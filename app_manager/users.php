<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  

  try
  {
    $myPage = new page();
    $myQuery = new query();
    $myQuery->set_default_order_by("user_account");
    $myQuery->set_default_sort_dir("ASC");
    $myQuery->set_sql_table("users");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, DATE_FORMAT(user_birthday,'%d.%m.%Y') as user_birthday_c FROM users");

    $myCol = new column("user_id","ID"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("user_gender","Geschlecht"); $myCol->set_selection('Herr,Frau'); $myQuery->add_column($myCol);
    $myCol = new column("user_account","Benutzername"); $myQuery->add_column($myCol);
    $myCol = new column("user_firstname","Vorname"); $myQuery->add_column($myCol);
    $myCol = new column("user_lastname","Nachname"); $myQuery->add_column($myCol);
    $myCol = new column("user_birthday_c","Geburtstag"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=true; $myCol->set_filter_column('user_birthday'); $myCol->set_save_column('user_birthday'); $myQuery->add_column($myCol);
    $myCol = new column("user_hide","Ausgeblendet"); $myCol->set_edit_typ('checkbox'); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("News");
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