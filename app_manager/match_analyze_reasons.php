
<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query();
    $myQuery->set_default_order_by("ma_reason_level1");
    $myQuery->set_default_sort_dir("ASC");
    $myQuery->set_sql_table("match_analyzes_reasons");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");

    $myCol = new column("ma_reason_level1","Level 1"); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level2","Level 2"); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level3","Level 3"); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level4","Level 4"); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Match-Analyse");
      $myPage->set_subtitle("Fehlergründe verwalten");
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