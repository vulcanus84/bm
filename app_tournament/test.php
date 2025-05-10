<?php
namespace Tournament;

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

require_once('inc/php/class_tournament.php');

$myTournament = new tournament(348);
$myTournament->calc->calc_ranking();
print $myTournament->html->debug_out_class();