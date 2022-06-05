<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  require_once(level."inc/php/class_tournament.php");
  
  if(isset($_GET['action']) && $_GET['action']=='get_number_of_rounds')
  {
    $db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
    print $db->count();
  }
  

?>