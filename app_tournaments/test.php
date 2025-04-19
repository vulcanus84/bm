<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

require_once('inc/php/class_tournament.php');

$myTournament = new tournament(302);
$myTournament->title = 'Test1234567';
//$myTournament->arr_rounds[0]->delete();

//$curr_round = $myTournament->add_round();
//$curr_round->add_game();
//$curr_round->add_game();
$myTournament->arr_rounds[0]->arr_games[0]->delete();

foreach ($myTournament->arr_rounds as $round) {
  print "<h1>".$round->id."</h1>";
  foreach ($round->arr_games as $game) {
    print "<h2>".$game->status."</h2>";
  }
}

$myTournament->save();