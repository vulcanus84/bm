<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("journal_created_on");
    $myQuery->set_default_sort_dir("DESC");
    $myQuery->set_sql_table("journal");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT 
                                  j.*,  -- alle Spalten aus journal
                                  CONCAT(trainer.user_firstname, ' ', trainer.user_lastname) AS trainer_name,
                                  DATE_FORMAT(journal_created_on,'%d.%m.%Y') as comment_date_c,
                                  GROUP_CONCAT(CONCAT(u.user_firstname, ' ', u.user_lastname) SEPARATOR '<br/> ') AS players
                              FROM 
                                  journal j
                              -- Trainer holen über journal_created_by
                              LEFT JOIN 
                                  users AS trainer ON j.journal_created_by = trainer.user_id
                              -- Verknüpfte Benutzer aus journal2user
                              LEFT JOIN 
                                  journal2user ju ON j.journal_id = ju.journal2user_journal_id
                              LEFT JOIN 
                                  users u ON ju.journal2user_user_id = u.user_id
                              GROUP BY 
                                  j.journal_id

    													");


    $db->sql_query("SELECT *, user_account as trainer_name FROM users ORDER BY user_account");
    $myCol = new column("journal_created_by","Trainer"); $myCol->set_selection_by_sql($db,'trainer_name','user_id'); $myQuery->add_column($myCol);
    $myCol = new column("players","Spieler"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myQuery->add_column($myCol);
    $myCol = new column("comment_date_c","Datum"); $myCol->set_edit_typ('date'); $myCol->set_save_column('journal_created_on'); $myCol->set_filter_column('journal_created_on'); $myQuery->add_column($myCol);
    $myCol = new column("journal_text","Kommentar"); $myCol->set_edit_typ('area'); $myCol->set_width(400); $myQuery->add_column($myCol);

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