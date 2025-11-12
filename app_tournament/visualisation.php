<?php
	namespace Tournament;

  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once("inc/php/class_tournament.php");

  try
  {
	$txt = "";
    $myPage = new \page();
    $myPage->permission_required=false;
    $myPage->set_title('Turnierübersicht');
  	if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }
    $myTournament = new tournament($_GET['tournament_id']);

    if(!IS_AJAX)
    {
			$myPage->add_css_link('inc/css/index.css');
			$myPage->add_css_link('inc/css/visualisation.css');
			$myPage->add_js_link('inc/js/visualisation.js');

      //Display page
      $myPage->add_content("<div id='logo'><img src='".level."inc/imgs/bcz_logo.jpg'/></div>");
			$myPage->add_content("<div id='title' data-tournament-ids='{$_GET['tournament_id']}'></div>");
      $myPage->add_content("<div id='main'>");
      $myPage->add_content("	<div id='left'>");
      $myPage->add_content("		<div id='users_title'><button>Teilnehmer</button></div>");
      $myPage->add_content("		<div id='users'></div>");
      $myPage->add_content("	</div>");
      $myPage->add_content("	<div id='middle'>");
      $myPage->add_content("		<div id='rounds'></div>");
      $myPage->add_content("		<div id='all_courts'></div>");
      $myPage->add_content("	</div>");
      $myPage->add_content("	<div id='right'>");
      $myPage->add_content("		<div id='news_title'><button>Neuigkeiten</button></div>");
      $myPage->add_content("		<div id='news'></div>");
      $myPage->add_content("	</div>");
      $myPage->add_content("</div>");
      print $myPage->get_html_code('small');
    }
    else
    {
			include("inc/php/ajax_visualisation.php");
    }
  }
  catch (\Exception $e)
  {
    $myPage = new \page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }
?>