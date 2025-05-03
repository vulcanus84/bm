<?php
namespace Tournament;

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
require_once("inc/php/class_tournament.php");	
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new \page();
	$myLogger = new \log();
	$myPage->add_js_link('inc/js/index.js');
	$myPage->add_css_link('inc/css/index.css');
	$myPage->add_css_link('inc/css/layout.css');

	$myTournament = new tournament();
	$myPage->set_title('Turniere');
	if(isset($_GET['tournament_id'])) {
		$myTournament->load($_GET['tournament_id']);
		$myPage->set_title($myTournament->title);
		//For default show award ceremony if tournament is closed or the last round if its running
		if($myTournament->status=='Closed') {
			if(!isset($_GET['mode'])) { $_GET['mode'] = 'award'; }
		} 
		if($myTournament->status=='Started') {
			if(!isset($_GET['round'])) { $_GET['round'] = $myTournament->arr_rounds[$myTournament->curr_round-1]->id; }
		}
	}

	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }
	
	include('inc/php/actions_by_get.php');	

	if(!IS_AJAX)
	{
		//Display page
		$myPage->permission_required=false;
		$myPage->add_data('tournament-id',$myTournament->id);
		$myPage->add_data('counting',$myTournament->counting);
		$myPage->add_data('system',$myTournament->system);
		$myPage->add_data('status',$myTournament->status);
		if(isset($_GET['round'])) {
			$myPage->add_data('round-id',$_GET['round']-1);
			$myPage->add_data('round-status',$myTournament->arr_rounds[$_GET['round']-1]->status);
		}
		

		$myPage->add_content("<div id='left_col'>");
		$myPage->add_content("	<div id='collapsed_label'>Spieler / Rangliste</div>");
		$myPage->add_content("	<div id='left_header'>");
		$myPage->add_content("		<span><a href='index.php'><button class='activated orange'>Turniere</button></a></span>");
		$myPage->add_content("		<span><a href='../app_user_admin/index.php'><button style='background-color:blue;'>Spieler</button></a></span>");
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='left_content'>");
		if($myTournament->id)
		{
			if($myTournament->status=='Started' OR $myTournament->status=='Closed')
			{
				$myPage->add_content($myTournament->html->get_users_from_tournament());
			}
			if($myTournament->status=='Define_teams')
			{
				$myPage->add_content($myTournament->html->get_users_for_teams());
			}

			if($myTournament->status=='define_seeded_players')
			{
				$myPage->add_content($myTournament->html->get_users_for_seedings());
			}

			if($myTournament->status=='New')
			{
				$myPage->add_content($myTournament->html->get_all_users($_GET['order_by']));
			}
		}
		else
		{
			$myPage->add_content($myTournament->html->get_all_tournaments());
		}
		$myPage->add_content("	</div>");
		$myPage->add_content("</div>");

		$myPage->add_content("<div id='right_col'>");
		$myPage->add_content("	<div id='right_header'>");
		$myPage->add_content($myTournament->html->get_buttons());
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='right_content'>");

		if($myTournament->id > 0)
		{
			switch ($myTournament->status) {
				case 'New':
					$myPage->add_content($myTournament->html->get_assigned_users());
					break;

				case 'Define_teams':
					$myPage->add_content($myTournament->html->get_partner_definition());
					break;
	
				case 'define_seeded_players':
					$myPage->add_content($myTournament->html->get_seeding_definition());
					break;

				case 'Started':
				case 'Closed':
					if(isset($_GET['round']))
					{
						$i=1;
						for($i;$i<=$myTournament->number_of_courts;$i++)
						{
							if(count($myTournament->arr_rounds)>$_GET['round']-1) {
								if(Count($myTournament->arr_rounds[$_GET['round']-1]->arr_games)>0)
								{
									$curr_game = $myTournament->arr_rounds[$_GET['round']-1]->arr_games[$i-1];
									$myPage->add_content("<div class='court' id='court{$i}'><img src='inc/php/court.php?action=fill&game_id={$curr_game->id}' class='img_court' data-game-id='{$curr_game->id}'/></div>");
									
								}
								else
								{
									$myPage->add_content("<div class='court' id='court$i'><img src='inc/php/court.php?action=clear' class='img_court'/></div>");
								}
							}
							else
							{
								$myPage->add_content("<div class='court' id='court$i'><img src='inc/php/court.php?action=clear' class='img_court'/></div>");
							}
						}
					}
					else {
						if($_GET['mode']=='details') { $myPage->add_content($myTournament->html->get_report()); }
						if($_GET['mode']=='award') { $myPage->add_content($myTournament->html->get_award_ceremony()); }	
					}
					break;
			}

			if(!isset($_GET['round']) && !isset($_GET['mode']))
			{
				if($myTournament->status=='Started' OR $myTournament->status=='Closed')
				{
					if($myTournament->system=='Gruppenspiele')
					{
						$myPage->add_content($myTournament->html->get_groupgame_overview());
					}
				}
			}

		}
		$myPage->add_content("</div>");
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
