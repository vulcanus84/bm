<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("news_date");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("news");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, DATE_FORMAT(news_date,'%d.%m.%Y') as news_date_c FROM news");

    $myCol = new column("news_title","Titel"); $myQuery->add_column($myCol);
    $myCol = new column("news_date_c","Datum"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=true; $myCol->set_filter_column('news_date'); $myCol->set_save_column('news_date'); $myQuery->add_column($myCol);
    $myCol = new column("news_text","Text"); $myCol->set_edit_typ('area'); $myCol->set_width(500); $myQuery->add_column($myCol);

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