<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("game_date");
    $myQuery->set_sql_table("games");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *, p1.user_account as p1_fullname, p2.user_account as p2_fullname, p3.user_account as p3_fullname, p4.user_account as p4_fullname, winner.user_account as winner_fullname, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c
        											FROM games
    													LEFT JOIN groups ON game_group_id = group_id
    													LEFT JOIN users as p1 ON game_player1_id = p1.user_id
    													LEFT JOIN users as p2 ON game_player2_id = p2.user_id
    													LEFT JOIN users as p3 ON game_player3_id = p3.user_id
    													LEFT JOIN users as p4 ON game_player4_id = p4.user_id
    													LEFT JOIN users as winner ON game_winner_id = winner.user_id
    													");

    $myCol = new column("group_title","Turnier");$myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);
    $myCol = new column("group_created_c","am"); $myCol->set_edit_typ('date'); $myCol->show_on_edit=false;  $myCol->set_filter_column('group_created'); $myQuery->add_column($myCol);
    $myCol = new column("game_round","Runde"); $myCol->set_selection('1,2,3,4,5,6,7,8,9,10,11,12'); $myQuery->add_column($myCol);
    $myCol = new column("game_status","Status"); $myCol->set_selection('New,Assigned,Closed'); $myQuery->add_column($myCol);
    $db->sql_query("SELECT *, user_account as p1_fullname FROM users ORDER BY user_account");
    $myCol = new column("game_player1_id","Spieler 1"); $myCol->set_selection_by_sql($db,'p1_fullname','user_id'); $myCol->set_filter_column('p1.user_account'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as p2_fullname FROM users ORDER BY user_account");
    $myCol = new column("game_player2_id","Spieler 2"); $myCol->set_selection_by_sql($db,'p2_fullname','user_id'); $myCol->set_filter_column('p2.user_account'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as p3_fullname FROM users ORDER BY user_account");
    $myCol = new column("game_player3_id","Spieler 3"); $myCol->set_selection_by_sql($db,'p3_fullname','user_id'); $myCol->set_filter_column('p2.user_account'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as p4_fullname FROM users ORDER BY user_account");
    $myCol = new column("game_player4_id","Spieler 4"); $myCol->set_selection_by_sql($db,'p4_fullname','user_id'); $myCol->set_filter_column('p2.user_account'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as winner_fullname FROM users ORDER BY user_account");
    $myCol = new column("game_winner_id","Gewinner"); $myCol->set_selection_by_sql($db,'winner_fullname','user_id'); $myCol->set_filter_column('winner.user_account'); $myQuery->add_column($myCol);

    $myCol = new column("game_location","Spielfeld"); $myCol->set_selection('1,2,3,4,5,6,7,8,9,10,11,12'); $myQuery->add_column($myCol);
    $myCol = new column("game_duration","Spielzeit [s]");  $myQuery->add_column($myCol);

    $myCol = new column("game_set1_p1","Satz 1, P1");  $myQuery->add_column($myCol);
    $myCol = new column("game_set1_p2","Satz 1, P2");  $myQuery->add_column($myCol);
    $myCol = new column("game_set2_p1","Satz 2, P1");  $myQuery->add_column($myCol);
    $myCol = new column("game_set2_p2","Satz 2, P2");  $myQuery->add_column($myCol);
    $myCol = new column("game_set3_p1","Satz 3, P1");  $myQuery->add_column($myCol);
    $myCol = new column("game_set3_p2","Satz 3, P2");  $myQuery->add_column($myCol);


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