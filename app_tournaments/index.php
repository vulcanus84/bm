<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
require_once("inc/php/class_tournament.php");	
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new page();
	$myLogger = new log();
	$myPage->add_js_link('inc/js/index.js');
	$myPage->add_css_link('inc/css/index.css');
	
	$myTournament = new tournament();
	$myPage->set_title('Turniere');
	if(isset($_GET['tournament_id'])) {
		$myTournament->load($_GET['tournament_id']);
		$myPage->set_title($myTournament->title);
	}
	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }
	
	
	include('inc/php/actions_by_get.php');	

	if(!IS_AJAX)
	{
		//Display page
		$myPage->permission_required=false;
		$myPage->add_data('tournament-id',$myTournament->id);

		//***********************************************************************************************************************************
		//Menu
		//***********************************************************************************************************************************
		$myPage->add_content("<div id='menu'>");
		$myPage->add_content("	<div id='menu_left'>");
		$myPage->add_content("		<span><a href='index.php'><button class='activated orange'>Turniere</button></a></span>");
		$myPage->add_content("		<span><a href='../app_user_admin/index.php'><button style='background-color:blue;'>Spieler</button></a></span>");
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='menu_right'>");
		$myPage->add_content($myTournament->get_buttons());
		$myPage->add_content("	</div>");
		$myPage->add_content("</div>");
		//***********************************************************************************************************************************

		$myPage->add_content("<div id='left_col'>");
		if($myTournament->id)
		{
			if($myTournament->status=='Started' OR $myTournament->status=='Closed')
			{
				$myPage->add_content($myTournament->get_users_from_tournament());
			}
			if($myTournament->status=='Define_teams')
			{
				$myPage->add_content($myTournament->get_users_for_teams());
			}

			if($myTournament->status=='define_seeded_players')
			{
				$myPage->add_content($myTournament->get_users_for_seedings());
			}

			if($myTournament->status=='New')
			{
				$myPage->add_content($myTournament->get_all_users('add_user',$_GET['order_by']));
			}
		}
		else
		{
			$myPage->add_content($myTournament->get_all_tournaments());
		}
		$myPage->add_content("</div>");

		$myPage->add_content("<div id='right_col'>");
		if($myTournament->id > 0)
		{
			switch ($myTournament->status) {
				case 'New':
					$myPage->add_content($myTournament->get_users('assigned'));
					break;

				case 'Define_teams':
					$myPage->add_content($myTournament->get_partner_definition());
					break;
	
				case 'define_seeded_player':
					$myPage->add_content($myTournament->get_seeding_definition());

				case 'Started':
					if(isset($_GET['round']))
					{
						$i=1;
						for($i;$i<=$myTournament->number_of_courts;$i++)
						{
							if(count($myTournament->arr_rounds)>$_GET['round']-1) {
								if(Count($myTournament->arr_rounds[$_GET['round']-1]->arr_games)>0)
								{
									$curr_game = $myTournament->arr_rounds[$_GET['round']-1]->arr_games[$i-1];
									if($curr_game->status=='Closed'){ $js = ""; } else { $js = "onclick='check_result($i,$curr_game->id);'"; } 
									$myPage->add_content("<div class='court' id='court$i'><img src='inc/php/court.php?action=fill&game_id=$curr_game->id' class='court' $js/></div>");
									
								}
								else
								{
									$myPage->add_content("<div class='court' id='court$i'><img src='inc/php/court.php?action=clear' class='court'/></div>");
								}
							}
							else
							{
								$myPage->add_content("<div class='court' id='court$i'><img src='inc/php/court.php?action=clear' class='court'/></div>");
							}
						}
					}
					break;
				case 'Closed':
					break;
			}


			if($myTournament->status=='Closed')
			{
				if(!isset($_GET['round']))
				{
					if(isset($_GET['mode']))
					{
						$x = "<h1>Rangliste</h1>";
						if($t_data->group_system=='Doppel_fix')
						{
							$db->sql_query("SELECT MAX(group2user_wins) as group2user_wins,MAX(group2user_BHZ) as group2user_BHZ,MAX(group2user_FBHZ) as group2user_FBHZ,
																GROUP_CONCAT(COALESCE(user_firstname,''),' ',COALESCE(user_lastname,'') SEPARATOR ' & ') as user_full
															FROM group2user
															LEFT JOIN users ON group2user_user_id = user_id
															WHERE group2user_group_id = '$_GET[tournament_id]'
															GROUP BY group2user_wins
															ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC");

						}
						else
						{
							$db->sql_query("SELECT *, 
																CONCAT(COALESCE(user_firstname,''),' ',COALESCE(user_lastname,'')) as user_full
															FROM group2user
															LEFT JOIN users ON group2user_user_id = user_id
															WHERE group2user_group_id = '$_GET[tournament_id]'
															ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC");
						}

						$x.= "<table style='width:100%; border-collapse: collapse;'>";
						$x.= "<tr>";
						$x.= "<th style='text-align:left;'>Rang</th>";
						$x.= "<th style='text-align:left;'>Spieler</th>";
						$x.= "<th style='text-align:left;'>Siege</th>";
						if($t_data->group_system=='Gruppenspiele')
						{
							$x.= "<th style='text-align:left;'>Satzdifferenz</th>";
							$x.= "<th style='text-align:left;'>Punktedifferenz</th>";
						}
						else
						{
							$x.= "<th style='text-align:left;'>BHZ</th>";
							$x.= "<th style='text-align:left;'>FBHZ</th>";
						}
						$x.= "</tr>";
						$rang = 1;
						while($d = $db->get_next_res())
						{
							$x.= "<tr>";
							$x.= "<td style='border-bottom:1px solid black;padding:5px;'>".$rang."</td>";
							$x.= "<td style='border-bottom:1px solid black;'>".$d->user_full."</td>";
							$x.= "<td style='border-bottom:1px solid black;'>".$d->group2user_wins."</td>";
							$x.= "<td style='border-bottom:1px solid black;'>".$d->group2user_BHZ."</td>";
							$x.= "<td style='border-bottom:1px solid black;'>".$d->group2user_FBHZ."</td>";
							$x.= "</tr>";
							$rang++;
						}
						$x.= "</table>";

						$x.= "<h1>Details</h1>";
						$db->sql_query("SELECT *, 
															CONCAT(COALESCE(p1.user_firstname,''),' ',COALESCE(p1.user_lastname,'')) as p1_user,
															CONCAT(COALESCE(p2.user_firstname,''),' ',COALESCE(p2.user_lastname,'')) as p2_user, 	 
															CONCAT(COALESCE(p3.user_firstname,''),' ',COALESCE(p3.user_lastname,'')) as p3_user,
															CONCAT(COALESCE(p4.user_firstname,''),' ',COALESCE(p4.user_lastname,'')) as p4_user 	 
														FROM games
															LEFT JOIN users as p1 ON game_player1_id = p1.user_id
															LEFT JOIN users as p2 ON game_player2_id = p2.user_id
															LEFT JOIN users as p3 ON game_player3_id = p3.user_id
															LEFT JOIN users as p4 ON game_player4_id = p4.user_id
															WHERE game_group_id = '$_GET[tournament_id]'
															ORDER BY game_round ASC, p1.user_account");

						$x.= "<table style='width:100%;'>";
						$x.= "<th style='text-align:left;'>Spieler 1</th>";
						$x.= "<th style='text-align:left;'>Spieler 2</th>";
						if($t_data->group_counting!='win')
						{
							$x.= "<th style=''>Satz 1</th>";
							$x.= "<th style=''>Satz 2</th>";
							$x.= "<th style=''>Satz 3</th>";
						}
						$last_round = 0;
						while($d = $db->get_next_res())
						{
							if($last_round!=$d->game_round)
							{
								$x.= "<tr><td colspan='5' style='text-align:center;background-color:#DDD;font-size:12pt;border:1px solid black;'>Runde ".$d->game_round."</td></tr>";
								$last_round = $d->game_round;
							}
							$x.= "<tr>";
							if(substr($t_data->group_system,0,6)=='Doppel')
							{
								if($d->game_winner_id == $d->game_player1_id) 
								{ 
									$zus_txt = 'font-weight:bold;'; 
									$x.= "<td><table><tr><td rowspan='2'><img style='height:25px;' src='".level."inc/imgs/crone.png'></td><td style=".$zus_txt.">".$d->p1_user."</td></tr><tr><td style=".$zus_txt.">".$d->p3_user."</td></tr></table></td>";
								} 
								else 
								{ 
									$zus_txt =''; 
									$x.= "<td><table><tr><td rowspan='2'></td><td style=".$zus_txt.">".$d->p1_user."</td></tr><tr><td style=".$zus_txt.">".$d->p3_user."</td></tr></table></td>";
								}

								if($d->game_winner_id == $d->game_player2_id) 
								{ 
									$zus_txt = 'font-weight:bold;'; 
									$x.= "<td><table><tr><td rowspan='2'><img style='height:25px;' src='".level."inc/imgs/crone.png'></td><td style=".$zus_txt.">".$d->p2_user."</td></tr><tr><td style=".$zus_txt.">".$d->p4_user."</td></tr></table></td>";
								} 
								else 
								{ 
									$zus_txt =''; 
									$x.= "<td><table><tr><td rowspan='2'></td><td style=".$zus_txt.">".$d->p2_user."</td></tr><tr><td style=".$zus_txt.">".$d->p4_user."</td></tr></table></td>";
								}
							}
							else
							{
								if($d->game_winner_id == $d->game_player1_id) {$x.= "<td><img style='height:15px;' src='".level."inc/imgs/crone.png'><b>&nbsp;".$d->p1_user."</b></td>";} else { $x.= "<td>".$d->p1_user."</td>"; }
								if($d->game_winner_id == $d->game_player2_id) {$x.= "<td><img style='height:15px;' src='".level."inc/imgs/crone.png'><b>&nbsp;".$d->p2_user."</b></td>";} else { $x.= "<td>".$d->p2_user."</td>"; }
							}
							if($t_data->group_counting!='win')
							{
								$x.= "<td style='text-align:center;'>".$d->game_set1_p1.":".$d->game_set1_p2."</td>";
								$x.= "<td style='text-align:center;'>".$d->game_set2_p1.":".$d->game_set2_p2."</td>";
								if($d->game_set3_p1>0) { $x.= "<td style='text-align:center;'>".$d->game_set3_p1.":".$d->game_set3_p2."</td>"; }
							}
							$x.= "</tr>";

						}
						$x.= "</table>";
					}
					else
					{
						$x = "<h1>Siegerehrung</h1>";
						$i = 0;
						$p = array();
	
						if($myTournament->system=='Doppel_fix')
						{
							$limit = 6;
							$db->sql_query("SELECT * FROM group2user
																		LEFT JOIN users ON group2user_user_id = user_id
																		WHERE group2user_group_id = '$_GET[tournament_id]'
																		ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit");
							$arr_displayed = array();
							$curr_pos=1;
							while($d = $db->get_next_res())
							{
								$i++;
								if(!in_array($d->group2user_user_id,$p))
								{
									$p[] = $d->group2user_user_id;
									$p[] = $d->group2user_partner_id;
								}
							}
						}
						else
						{
							$limit = 3;
							$db->sql_query("SELECT * FROM group2user
																		LEFT JOIN users ON group2user_user_id = user_id
																		WHERE group2user_group_id = '$_GET[tournament_id]'
																		ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit");
							while($d = $db->get_next_res())
							{
								$p[] = $d->group2user_user_id;
							}
							//
							$p[3] = null; $p[4] = null; $p[5] = null;
						}
	
	
						$i=3;
						$x.= "<div style='width:100%;'><img style='width:100%;' src='inc/php/podest.php?p1=$p[0]&p2=$p[1]&p3=$p[2]&p4=$p[3]&p5=$p[4]&p6=$p[5]'/>";
						$x.= "</div><div style='clear:both;margin-left:5vw;'>";
	
						$db->sql_query("SELECT * FROM group2user
																	LEFT JOIN users ON group2user_user_id = user_id
																	WHERE group2user_group_id = '$_GET[tournament_id]'
																	ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit,100");
						while($d = $db->get_next_res())
						{
							$my_user = new user($d->group2user_user_id);
	
							if($myTournament->system=='Doppel_fix')
							{
								if(!in_array($my_user->id,$arr_displayed))
								{
									$i++;
									$db2 = clone($db);
									$d2 = $db2->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$my_user->id'");
									$partner = new user($d2->group2user_partner_id);
									$x.= "<div style='float:left;text-align:center;margin:10px;'><b>Rang ".$i."</b><br/>".$my_user->get_picture(false,null,'90px',true).$partner->get_picture(false,null,'90px',true)."<br/>".$my_user->login." & ".$partner->login."</div>";
									$arr_displayed[] = $my_user->id;
									$arr_displayed[] = $partner->id;
								}
							}
							else
							{
								$i++;
								$x.= "<div class='ranking'><b>Rang ".$i."</b><br/>".$my_user->get_picture(true,null,false,false)."</div>";
							}
							$my_user = null;
						}
						$x.= "</div>";
					}
					$myPage->add_content($x);
				}
			}

			if(!isset($_GET['round']) && !isset($_GET['mode']))
			{
				if($myTournament->status=='Started' OR $myTournament->status=='Closed')
				{
					if($myTournament->system=='Gruppenspiele')
					{
						//Show overview of Gruppenspiele
						$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]'");
						$anz = $db->count()+1;

						//Generate empty array with the right dimensions
						$arr_table = array();
						$i=0;
						for($i;$i<$anz;$i++)
						{
							$j=0;
							for($j;$j<$anz;$j++)
							{
								$arr_table[$i][$j] = "<td style='text-align:center;'></td>";
							}
						}

						//Fill static fields
						$i=1;
						$arr_table[0][0] = "<td></td>";
						for($i;$i<$anz;$i++)
						{
							$arr_table[$i][$i] = "<td style='background: repeating-linear-gradient(45deg, transparent, #CCC 10px);'></td>";
						}

						//Fill Players
						$i = 1;
						$arr_players = array();
						$db_temp = clone($db);
						while($d = $db->get_next_res())
						{
							$myUser = new user($d->group2user_user_id);
							$arr_table[0][$i] = "<td style='text-align:center;'>".$myUser->get_picture(false,'show_user_games','80px',true)."<br/>".$myUser->login."</td>";
							$arr_table[$i][0] = "<td style='text-align:center;'>".$myUser->get_picture(false,'show_user_games','80px',true)."<br/>".$myUser->login."</td>";
							$arr_players[$i] = $d->group2user_user_id;
							$i++;
						}

						//Fill games
						$i = 1;
						foreach($arr_players as $player)
						{
							$db_temp->sql_query("SELECT * FROM games WHERE game_group_id='".$myTournament->id."' AND (game_player1_id='$player' OR game_player2_id='$player')");
							while($temp = $db_temp->get_next_res())
							{
								if($temp->game_player1_id == $player) { $opponent = $temp->game_player2_id; $player_is_first_player = true; } else { $opponent = $temp->game_player1_id; $player_is_first_player = false; }
								$j = 1;
								foreach($arr_players as $player_tmp)
								{
									if($player_tmp==$opponent)
									{
										if($temp->game_winner_id=='')
										{
											$arr_table[$i][$j] = "<td style='text-align:center;'>Spiel in Runde<br/><span style='font-weight:bold;'>".$temp->game_round."</span></td>";
										}
										else
										{
											if($player_is_first_player)
											{
												if($temp->game_set1_p1<1 AND $temp->game_set1_p2<1)
												{
													$myUser = new user($temp->game_winner_id);
													$txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
												}
												else
												{
													$txt = "<span style='font-size:16pt;font-weight:bold;'>".$temp->game_set1_p1.":".$temp->game_set1_p2;
													if($temp->game_set2_p1>0 OR $temp->game_set2_p2>0) {  $txt .= "<br/>".$temp->game_set2_p1.":".$temp->game_set2_p2; }
													if($temp->game_set3_p1>0 OR $temp->game_set3_p2>0) {  $txt .= "<br/>".$temp->game_set3_p1.":".$temp->game_set3_p2; }
													$txt.= "</span>";
												}
											}
											else
											{
												if($temp->game_set1_p1<1 AND $temp->game_set1_p2<1)
												{
													$myUser = new user($temp->game_winner_id);
													$txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
												}
												else
												{
													$txt = "<span style='font-size:16pt;font-weight:bold;'>".$temp->game_set1_p2.":".$temp->game_set1_p1;
													if($temp->game_set2_p1>0 OR $temp->game_set2_p2>0) {  $txt .= "<br/>".$temp->game_set2_p2.":".$temp->game_set2_p1; }
													if($temp->game_set3_p1>0 OR $temp->game_set3_p2>0) {  $txt .= "<br/>".$temp->game_set3_p2.":".$temp->game_set3_p1; }
													$txt.= "</span>";
												}
											}

											$arr_table[$i][$j] = "<td style='text-align:center;'>".$txt."</td>";
									}
									}
									$j++;
								}
							}
							$i++;
						}
						//Print array as table
						if($myTournament->status=='Closed') { $myPage->add_content("<hr style='clear:both;'/>"); }
						$myPage->add_content("<table border='0' style='width:100%;'>");
						foreach($arr_table as $row)
						{
							$myPage->add_content("<tr>");
							foreach($row as $col)
							{
								$myPage->add_content($col);
							}
							$myPage->add_content("</tr>");
						}
						$myPage->add_content("</table>");
					}
				}
			}

		}
		$myPage->add_content("</div>");

		print $myPage->get_html_code();
	}
	else
	{
		include('inc/php/ajax.php');
	}
}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}
?>
