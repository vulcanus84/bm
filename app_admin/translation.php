<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myQuery = new query();
    $myQuery->set_default_order_by("trans_german");
    $myQuery->set_sql_table("translation");
    $myQuery->set_edit_mode("full");
    $sql_table = $myQuery->get_sql_table();
		$myQuery->set_sql_select("SELECT *,
															DATE_FORMAT(trans_last_used_on,'%d.%m.%Y %H:%i') as trans_last_used_on_c,
															DATE_FORMAT(trans_created_on,'%d.%m.%Y %H:%i') as trans_created_on_c
															FROM $sql_table");

    $myColumn = new column("trans_code","Code"); $myColumn->set_edit_typ('area'); $myColumn->set_width('150'); $myQuery->add_column($myColumn);
    $myColumn = new column("trans_german","Deutsch"); $myColumn->set_edit_typ('area'); $myColumn->set_width('250'); $myQuery->add_column($myColumn);
    $myColumn = new column("trans_english","Englisch"); $myColumn->set_edit_typ('area'); $myColumn->set_width('250');   $myQuery->add_column($myColumn);
    $myColumn = new column("trans_last_used_by","Zuletzt verwendet von"); $myColumn->set_edit_typ('not_editable'); $myColumn->set_width('250'); $myQuery->add_column($myColumn);
    $myColumn = new column("trans_last_used_on_c","Zuletzt verwendet am"); $myColumn->set_edit_typ('not_editable'); $myColumn->set_filter_column('last_used_on'); $myColumn->set_width('150'); $myQuery->add_column($myColumn);
    $myColumn = new column("trans_created_on_c","Erstellt am"); $myColumn->set_edit_typ('not_editable'); $myColumn->set_filter_column('created_on'); $myColumn->set_width('150'); $myQuery->add_column($myColumn);

    if(!IS_AJAX)
    {
      //Display page
      $myPage = new page();
      $myPage->set_title("Administration");
      $myPage->set_subtitle("Übersetzungen");
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