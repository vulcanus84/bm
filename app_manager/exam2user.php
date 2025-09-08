<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query();
    $myQuery->set_default_order_by("exam2user_created_on");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("exam2user");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, CONCAT(exam_category, ' > ', exam_level,' > ',exam_title) as exam_fullname, 
                                        player.user_account as player_fullname, 
                                        trainer.user_account as trainer_fullname, 
                                        DATE_FORMAT(exam2user_created_on,'%d.%m.%Y %H:%i:%s') as created_c
                                    FROM exam2user
                                    LEFT JOIN exams ON exam2user_exam_id = exams.exam_id
                                        LEFT JOIN users as player ON exam2user_user_id = player.user_id
                                        LEFT JOIN users as trainer ON exam2user_created_by = trainer.user_id
    													");

    $myCol = new column("created_c","am"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=false;  $myCol->set_filter_column('exam2user_created_on'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *,CONCAT(exam_category, ' > ', exam_level,' > ',exam_title) as exam_fullname FROM exams ORDER BY exam_category,exam_level");
    $myCol = new column("exam2user_exam_id","Prüfung"); $myCol->set_selection_by_sql($db,'exam_fullname','exam_id'); $myCol->set_filter_column("CONCAT(exam_category, ' > ', exam_level,' > ',exam_title)"); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as player_fullname FROM users ORDER BY user_account");
    $myCol = new column("exam2user_user_id","Spieler"); $myCol->set_selection_by_sql($db,'player_fullname','user_id'); $myCol->set_filter_column('player.user_account'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as trainer_fullname 
                    FROM location2user 
                    LEFT JOIN users ON location2user_user_id = users.user_id
                    WHERE location2user_location_id = 8 AND users.user_hide < '1'
                    ORDER BY user_account");
    $myCol = new column("exam2user_created_by","Trainer"); $myCol->set_selection_by_sql($db,'trainer_fullname','user_id'); $myCol->set_filter_column('trainer.user_account'); $myQuery->add_column($myCol);

    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Prüfungen zuweisen");
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