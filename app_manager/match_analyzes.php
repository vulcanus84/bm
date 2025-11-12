
<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myPage->set_title("Manager > Match-Analysen");
    if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

    $myQuery = new query();
    $myQuery->set_default_order_by("ma_point_id");
    $myQuery->set_default_sort_dir("ASC");
    $myQuery->set_sql_table("match_analyzes_points");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, 
                              DATE_FORMAT(ma_created_on,'%d.%m.%Y %H:%i:%s') as created_c,
                              CONCAT(coach.user_firstname, ' ', coach.user_lastname) AS coach_name,
                              CONCAT(trainee.user_firstname, ' ', trainee.user_lastname) AS trainee_name,
                              CONCAT_WS(' / ', ma_reason_id, ma_reason_level1, ma_reason_level2, ma_reason_level3, ma_reason_level4) AS reason_desc
                                    FROM ".$sql_table."
                                    LEFT JOIN match_analyzes_reasons ON ma_point_reason_id = ma_reason_id
                                    LEFt JOIN match_analyzes ON ma_point_ma_id = ma_id
                                    LEFT JOIN users as trainee ON ma_trainee_id = trainee.user_id
                                    LEFT JOIN users as partner ON ma_trainee_partner_id = partner.user_id
                                    LEFT JOIN users as coach ON ma_created_by = coach.user_id
    													");

    $myCol = new column("coach_name","Trainer"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myCol->set_filter_column('coach.user_account'); $myQuery->add_column($myCol);
    $myCol = new column("trainee_name","Spieler"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myCol->set_filter_column('trainee.user_account'); $myQuery->add_column($myCol);
    $myCol = new column("ma_opponent_name","Gegner"); $myQuery->add_column($myCol);
    $myCol = new column("ma_point_set","Satz"); $myCol->set_selection("1,2,3"); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, CONCAT_WS(' / ', ma_reason_level1, ma_reason_level2, ma_reason_level3, ma_reason_level4) AS reason_desc FROM match_analyzes_reasons ORDER BY ma_reason_level1, ma_reason_level2, ma_reason_level3, ma_reason_level4");
    $myCol = new column("reason_desc","Begründung"); $myCol->set_selection_by_sql($db,'reason_desc','ma_reason_id'); $myCol->set_filter_column("CONCAT_WS(' / ', ma_reason_id, ma_reason_level1, ma_reason_level2, ma_reason_level3, ma_reason_level4) AS reason_desc"); $myCol->set_save_column('ma_point_reason_id'); $myQuery->add_column($myCol);

    $myCol = new column("ma_reason_level1","Level 1"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level2","Level 2"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level3","Level 3"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myQuery->add_column($myCol);
    $myCol = new column("ma_reason_level4","Level 4"); $myCol->set_edit_typ('not_editable'); $myCol->hide_from_edit(); $myQuery->add_column($myCol);

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