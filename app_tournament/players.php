<?php

namespace Tournament;

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
require_once("inc/php/class_tournament.php");	
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new \page();
	$myPage->set_title("Spielerverwaltung");
	if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

	$myLogger = new \log();
	$myPage->add_js_link('inc/js/players.js');
	$myPage->add_css_link('inc/css/index.css');
	$myPage->add_css_link('inc/css/layout.css');

	$myTournament = new tournament();
	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }

	if(!IS_AJAX)
	{
		//Display page
		//$myPage->set_title("Badminton Academy");
		$myPage->permission_required=false;
		$myPage->add_content("<div id='left_col' style='flex: 0 0 30vw;'>");
		$myPage->add_content("	<div id='collapsed_label'>Spieler</div>");
		$myPage->add_content("	<div id='left_header'>");
		$myPage->add_content("		<span><a href='index.php'><button class='orange'>Turniere</button></a></span>");
		$myPage->add_content("		<span><a href='players.php'><button class='activated blue'>Spieler</button></a></span>");
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='left_content'>");
		$myPage->add_content($myTournament->html->get_all_users($_GET['order_by']));
		$myPage->add_content("	</div>");
		$myPage->add_content("</div>");
		$myPage->add_content("<div id='right_col'>");
		$myPage->add_content("	<div id='right_header'>");
		$myPage->add_content("		<div class='menu_item'><button class='green' onclick='new_user();'>Neues Spieler</button></div>");
		$myPage->add_content("	</div>");		
		$myPage->add_content("	<div id='right_content'>");
		$myPage->add_content("	</div>");		
		$myPage->add_content("</div>");		
		print $myPage->get_html_code();
	}
	else
	{
		include('inc/php/ajax.php');
	}
}
catch (\Exception $e)
{
	$myPage = new \page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}
?>
