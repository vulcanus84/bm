<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
    $myQuery = new query($db);
    $myQuery->set_default_order_by("group_title");
    $myQuery->set_sql_table("group2user");
    $sql_table = $myQuery->get_sql_table();
    $myQuery->set_edit_mode("full");
    $myQuery->set_sql_select("SELECT *,DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c, users.user_account as player_fullname, DATE_FORMAT(group2user_added_on,'%d.%m.%Y %H:%i:%s') as created_c
                                    FROM group2user
                                    LEFT JOIN groups ON group2user_group_id = groups.group_id
                                    LEFT JOIN users ON group2user_user_id = users.user_id
                                    LEFT JOIN locations ON group_created_by_location = locations.location_id
    													");

    $db->sql_query("SELECT * FROM locations");
    $myCol = new column("location_name","Organisator"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);

    $myCol = new column("group_title","Turnier"); $myCol->set_edit_typ('not_editable'); $myQuery->add_column($myCol);

    $myCol = new column("group_created_c","Datum"); $myCol->set_edit_typ('not_editable'); $myCol->show_on_edit=true; $myCol->set_filter_column('group_created'); $myCol->set_save_column('group_created'); $myQuery->add_column($myCol);
    $myCol = new column("group_status","Status"); $myCol->set_edit_typ('not_editable'); $myCol->set_selection('New,Started,Closed'); $myQuery->add_column($myCol);

    $db->sql_query("SELECT *, user_account as player_fullname FROM users ORDER BY user_account");
    $myCol = new column("group2user_user_id","Spieler"); $myCol->set_selection_by_sql($db,'player_fullname','user_id'); $myCol->set_filter_column('users.user_account'); $myQuery->add_column($myCol);


    if(!IS_AJAX)
    {
      //Display page
      include('menu.php');
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Turniere zuweisen");
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