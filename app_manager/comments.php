<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("user_account");
    $myQuery->set_sql_table("comments");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, p1.user_account as p1_fullname, DATE_FORMAT(comment_date,'%d.%m.%Y') as comment_date_c
        											FROM ".$myQuery->get_sql_table()." 
    													LEFT JOIN users as p1 ON comment_user_id = p1.user_id
    													");

    $db->sql_query("SELECT *, user_account as p1_fullname FROM users ORDER BY user_account");
    $myCol = new column("comment_user_id","Spieler"); $myCol->set_selection_by_sql($db,'p1_fullname','user_id'); $myCol->set_filter_column('p1.user_account'); $myQuery->add_column($myCol);
    $myCol = new column("comment_date_c","Datum"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=false; $myCol->set_filter_column('comment_date'); $myQuery->add_column($myCol);
    $myCol = new column("comment_text","Kommentar"); $myCol->set_edit_typ('area'); $myCol->set_width(400); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Spiele");
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