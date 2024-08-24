<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }

try
{
	$myPage = new page();
	$myLogger = new log();
	if(isset($_GET['tournament_id']))
	{
		$myTournament = new tournament($db,$_GET['tournament_id']);
		$myPage->set_title($myTournament->get_title());
	}
	else
	{
		$myPage->set_title('Turniere');
		$myTournament = new tournament($db);
	}

	if(isset($_GET['action']) && $_GET['action']=='change_location_filter') 
	{ 
		$page->change_parameter('location_filter',$_POST['location']);
		$page->remove_parameter('action');
		header("Location: ".$page->get_link());
	}


	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }

	//Delete tournament
	if(isset($_GET['action']))
	{
		if($_GET['action']=='reactivate_tournament')
		{
			if(isset($_GET['tournament_id']))
			{
				$db->update(array('group_status'=>'Started'),'groups','group_id',$_GET['tournament_id']);
				header('Location: index.php');
			}
		}

		if($_GET['action']=='delete_tournament')
		{
			if(isset($_GET['tournament_id']))
			{
				$db->delete('groups','group_id',$_GET['tournament_id']);
				header('Location: index.php');
			}
		}
	}

	//Create or modify tournament
	if(isset($_POST['tournament_title']))
	{
		if(isset($_POST['tournament_id']))
		{
			$tournament_id = $_POST['tournament_id'];
			$db->update(array('group_title'=>$_POST['tournament_title'],'group_description'=>$_POST['tournament_description'],'group_system'=>$_POST['tournament_system'],'group_counting'=>$_POST['tournament_counting'],'group_created_by_location'=>$_POST['created_by_location']),'groups','group_id',$tournament_id);
		}
		else
		{
			$db->insert(array('group_title'=>$_POST['tournament_title'],'group_description'=>$_POST['tournament_description'],'group_system'=>$_POST['tournament_system'],'group_counting'=>$_POST['tournament_counting'],'group_created_by_location'=>$_POST['created_by_location']),'groups');
			$tournament_id = $db->last_inserted_id;
		}
		header("Location: index.php");
	}

	//Javascript links need at least one parameter because of the &param
	if(!isset($_GET['tournament_id'])) { $page->change_parameter('x','1'); }
	
	$_SERVER['link'] = $page->get_link();
	if(PLATFORM=='IPHONE') { $tmp = "$('#left_col').hide();"; } else { $tmp=''; }

	//Tournament loaded...
	if(isset($_GET['tournament_id']))
	{
		$tournament = $db->sql_query_with_fetch("SELECT * FROM groups WHERE group_id='".$_GET['tournament_id']."'");
		$anz_felder = $tournament->group_courts;

		//Loaded tournament is started...
		if($tournament->group_status=='Started' OR $tournament->group_status=='Define_teams' OR $tournament->group_status=='define_seeded_players')
		{
			if(PLATFORM=='IPHONE') { $myPage->add_css("div#left_col { display:none; }"); }
			$myPage->add_js("
				function stopp_tournament()
				{
					if (confirm('Bist du sicher, dass du das Turnier abbrechen wilst? \\n\\n(alle bisherigen Spiele und Partner werden gelöscht, die zugewiesen Spieler und Setzlisten bleiben erhalten)'))
					{
						var my_url = '$_SERVER[link]&ajax=stopp_tournament&tournament_id=$_GET[tournament_id]';
						$.ajax({ url: my_url }).done(
						function(data)
						{
							window.location = '$_SERVER[link]';
						});
					}
				}
			");

		}
		$myPage->add_js("

			function add_user(user_id)
			{
				var pos = $('#user'+user_id).parent()[0].id;
				if(pos=='left_col')
				{
					var my_url = '$_SERVER[link]&ajax=add_user&user_id=' + user_id + '&tournament_id=$_GET[tournament_id]';
					$.ajax({ url: my_url }).done(
					function(data)
					{
						$('#right_col').append($('#user'+user_id));

					});
				}
				else
				{
					var my_url = '$_SERVER[link]&ajax=remove_user&user_id=' + user_id + '&tournament_id=$_GET[tournament_id]';
					$.ajax({ url: my_url }).done(
					function(data)
					{
						window.location = '$_SERVER[link]';
						$('#left_col').append($('#user'+user_id));
					});
				}
			}

			function new_user()
			{
				$('#right_col').load('$_SERVER[link]&ajax=new_user&tournament_id=$_GET[tournament_id]');
			}

			function remove_user()
			{
				$('#right_col').load('$_SERVER[link]&ajax=remove_user&tournament_id=$_GET[tournament_id]');
			}

			function define_seeded_players()
			{
				var my_url = '$_SERVER[link]&ajax=define_seeded_players&tournament_id=$_GET[tournament_id]';
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}

			function start_tournament()
			{
				var my_url = '$_SERVER[link]&ajax=start_tournament&tournament_id=$_GET[tournament_id]';
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]&round=1';
					}
					else
					{
						alert(data);
					}
				});
			}
			function close_tournament()
			{
				var my_url = '$_SERVER[link]&ajax=close_tournament&tournament_id=$_GET[tournament_id]';
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data.substring(0, 2)=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}

			function show_user_games(user_id)
			{
				$('#right_col').load('$_SERVER[link]&ajax=show_user_info&tournament_id=$_GET[tournament_id]&user_id='+user_id);
			}

			function add_as_seeded(user_id)
			{
				var my_url = '$_SERVER[link]&ajax=add_as_seeded&tournament_id=$_GET[tournament_id]&user_id='+user_id;
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}

			function delete_last_seeding()
			{
				var my_url = '$_SERVER[link]&ajax=delete_last_seeding&tournament_id=$_GET[tournament_id]';
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}

			function add_as_partner(user_id)
			{
				var my_url = '$_SERVER[link]&ajax=add_as_partner&tournament_id=$_GET[tournament_id]&user_id='+user_id;
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}

			function delete_team(user_id)
			{
				var my_url = '$_SERVER[link]&ajax=delete_team&tournament_id=$_GET[tournament_id]&user_id='+user_id;
				$.ajax({ url: my_url }).done(
				function(data)
				{
					if(data=='OK')
					{
						window.location = '$_SERVER[link]';
					}
					else
					{
						alert(data);
					}
				});
			}


		");

		if(isset($_GET['round']))
		{
			$myPage->add_js
			("
				function check_result(court)
				{
					$('#court'+court).load('$_SERVER[link]&ajax=get_result&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court='+court);
				}

				function set_winner(user_id,court)
				{
					$('#court'+court).load('$_SERVER[link]&ajax=set_winner&tournament_id=$_GET[tournament_id]&round=$_GET[round]&winner_id='+user_id+'&court_id='+court,
					function()
					{
						$('#left_col').load('$_SERVER[link]&ajax=get_left_col&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
					});
				}

				function set_points_and_winner(modus,court,set1_p1,set1_p2,set2_p1=0,set2_p2=0,set3_p1=0,set3_p2=0)
				{
					//Check result
					var error = '';
					var wins_p1=0;
					var wins_p2=0;
					var points_p1=0;
					var points_p2=0;

					if(modus=='pointsOneSet')
					{
						if(set1_p1 == set1_p2)
						{
							if(set1_p1 > 0 || set1_p2 > 0)
							{
				      	error += 'Punkte dürfen nicht gleich sein';
							}
						}
					}

					if(modus=='official2sets')
					{
						for(var i=1;i<4;i++)
						{
							//Point differences and maxPoints
							var max_points = 0; var diff = 0;
							if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
							if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
							if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

						  if(i==3 && wins_p1 - wins_p2 !=0)
						  {
						    if(set3_p1!=0 || set3_p2!=0)
						    {
						      error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
						    }
						    break;
						  }

							if(i==1 && max_points==0)
							{
								$('#court'+court).load('$_SERVER[link]&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court);
								break;
							}

						  if(max_points>20 && max_points < 31)
						  {
						    if((diff < 2 && max_points!=30) || (diff > 2 && max_points>21))
						    {
						      error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
						      break;
						    }
						    else
						    {
						      if(points_p1>points_p2) { wins_p1++; }
						      if(points_p1<points_p2) { wins_p2++; }
						    }
						  }
						  else
						  {
						    error += 'Punkte in Satz ' + i + ' nicht gültig';
						    break;
						  }
						}
					}

					if(modus=='2sets11points')
					{
						for(var i=1;i<4;i++)
						{
							//Point differences and maxPoints
							var max_points = 0; var diff = 0;
							if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
							if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
							if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

						  if(i==3 && wins_p1 - wins_p2 !=0)
						  {
						    if(set3_p1!=0 || set3_p2!=0)
						    {
						      error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
						    }
						    break;
						  }

							if(i==1 && max_points==0)
							{
								$('#court'+court).load('$_SERVER[link]&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court);
								break;
							}
						  if(max_points==11)
						  {
						    if(diff < 1)
						    {
						      error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
						      break;
						    }
						    else
						    {
						      if(points_p1>points_p2) { wins_p1++; }
						      if(points_p1<points_p2) { wins_p2++; }
						    }
						  }
						  else
						  {
						    error += 'Punkte in Satz ' + i + ' nicht gültig';
						    break;
						  }
						}
					}

					if(modus=='2setswinning')
					{
						for(var i=1;i<4;i++)
						{
							//Point differences and maxPoints
							var max_points = 0; var diff = 0;
							if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
							if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
							if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

						  if(i==3 && wins_p1 - wins_p2 !=0)
						  {
						    if(set3_p1!=0 || set3_p2!=0)
						    {
						      error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
						    }
						    break;
						  }

							if(i==1 && max_points==0)
							{
								break;
							}
					    if(diff < 1)
					    {
					      error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
					      break;
					    }
					    else
					    {
					      if(points_p1>points_p2) { wins_p1++; }
					      if(points_p1<points_p2) { wins_p2++; }
					    }
						}
					}

					if(error!='')
					{
						alert(error);
					}
					else
					{
						$('#court'+court).load('$_SERVER[link]&ajax=set_points_and_winner&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court+'&set1_p1='+set1_p1+'&set1_p2='+set1_p2+'&set2_p1='+set2_p1+'&set2_p2='+set2_p2+'&set3_p1='+set3_p1+'&set3_p2='+set3_p2,
						function()
						{
							$('#left_col').load('$_SERVER[link]&ajax=get_left_col&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
						});
					}
				}


				function clear_it()
				{
					if (confirm('Bist du sicher, dass du die Auslosung löschen wilst? \\n\\n(alle eingetragen Spiele der aktuellen Runde und die Auslosungen werden gelöscht)'))
					{
						var i = 1;
						for(i;i<$anz_felder+1;i++)
						{
							$('#court'+i).load('$_SERVER[link]&ajax=clear&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
						}
						$('#loeschen').hide();
						$('#runde_schliessen').hide();
						$('#auslosen').show();
						$('#runde_starten').hide();
					}
				}

				function start_it()
				{
					$.ajax({ url: '$_SERVER[link]&ajax=define_games&tournament_id=$_GET[tournament_id]&round=$_GET[round]'  }).done(
					function(data)
					{
						if(data=='OK')
						{
							var i = 1;
							for(i;i<$anz_felder+1;i++)
							{
								do_it(i);
							}
							$('#loeschen').show();
							$('#runde_schliessen').show();
							$('#auslosen').hide();
							$('#runde_starten').show();
						}
						else
						{
							alert(data);
						}
					});
				}

				function do_it(court_no)
				{
					var delay = court_no*500;
					$('#court'+court_no).load('$_SERVER[link]&ajax=load',
					function()
					{
						$('#court'+court_no).delay(1000).fadeTo(delay,1,
						function (data)
						{
							$('#court'+court_no).load('$_SERVER[link]&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court_no);
						});
					});
				}
				function close_round()
				{
					var my_url = '$_SERVER[link]&ajax=close_round&tournament_id=$_GET[tournament_id]&round=$_GET[round]';
					$.ajax({ url: my_url }).done(
					function(data)
					{
						if(data.substring(0, 2)=='OK')
						{
							var myNewUrl = '$_SERVER[link]';
							myNewUrl = myNewUrl.replace('round=$_GET[round]','round='+ data.substring(3));
							window.location = myNewUrl;
						}
						else
						{
							alert(data);
						}
					});
				}

				function reset_round(round_nr)
				{
					var my_url = '$_SERVER[link]&ajax=reset_round&tournament_id=$_GET[tournament_id]&round='+round_nr;
					$.ajax({ url: my_url }).done(
					function(data)
					{
						if(data.substring(0, 2)=='OK')
						{
							window.location = '$_SERVER[link]';
						}
						else
						{
							alert(data);
						}
					});
				}

			"); //End add_js
		} //end if isset(round)
	}
	else //tournament id not set
	{
		$myPage->add_js("
			function new_tournament()
			{
				$('#right_col').load('$_SERVER[link]&ajax=new_tournament');
				".$tmp."
			}

			function create_tournament()
			{
				$.ajax({ url: '$_SERVER[link]&ajax=save_tournament&name=' + $('#tournament_name').val() +'&desc=' + $('#tournament_desc').val()  }).done(
				function(data)
				{
					window.location = 'index.php?tournament_id='+data;
					$('#left_col').html('<h1>'+ $('#tournament_name').val()+'</h1>');
					$('#right_col').html('');
				});
			}

			function show_infos(tournament_id)
			{
				$('#right_col').load('$_SERVER[link]&ajax=show_infos&tournament_id='+tournament_id);
				".$tmp."
			}

			function delete_tournament(tournament_id)
			{
				$('#right_col').load('$_SERVER[link]&ajax=delete_permission&tournament_id='+tournament_id);
				".$tmp."
			}

		");
	}

	if(!IS_AJAX)
	{
		//Display page
		//$myPage->set_title("Badminton Academy");
		$myPage->permission_required=false;

		//***********************************************************************************************************************************
		//Menu
		//***********************************************************************************************************************************
		$myPage->add_content("<div id='menu'>");
		$myPage->add_content("	<div id='menu_left'>");
		$myPage->add_content("		<div style='float:left;'><button style='background-color:orange;border-left:5px solid gray;border-right:5px solid gray;' onclick='window.location=\"".level."app_tournaments/index.php\"'>Turniere</button></div>");
		$myPage->add_content("		<div style='float:none;'><button style='background-color:blue;' onclick='window.location=\"".level."app_user_admin/index.php\"'>Spieler</button></div>");
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='menu_right'>");

		//Tournament loaded...
		if(isset($_GET['tournament_id']))
		{
			$t_data = $db->sql_query_with_fetch("SELECT * FROM groups WHERE group_id='".$_GET['tournament_id']."'");
			$myPage->add_content("<div class='menu_item'><button style='background-color:purple;' onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]\"';>".$t_data->group_title."</button></div>");

			//Loaded tournament is created...
			if($t_data->group_status=='New')
			{
				if($myTournament->get_system()=='Doppel_fix') { $txt = 'Teams definieren'; } else { $txt='Turnier starten'; }
				$myPage->add_content("<div class='menu_item'><button onclick='start_tournament();'>$txt</button></div>");
				if(substr($myTournament->get_system(),0,6)!='Doppel') { $myPage->add_content("<div class='menu_item'><button style='background-color:orange;' onclick='define_seeded_players();'>Setzplätze definieren</button></div>"); }
			}

			//Loaded tournament is created...
			if($t_data->group_status=='define_seeded_players')
			{
				$myPage->add_content("<div class='menu_item'><button onclick='start_tournament();'>Turnier starten</button></div>");
				$myPage->add_content("<div class='menu_item'><button style='background-color:orange;' id='turnier_abbrechen' onclick='stopp_tournament();'>Abbrechen</button></div>");
				if($myTournament->get_number_of_seedings()>0) { $myPage->add_content("<div class='menu_item'><button style='background-color:red;' id='delete_last_seeding' onclick='delete_last_seeding();'>Letzten Setzplatz löschen</button></div>"); }
			}
			//Loaded tournament is closed...
			if($t_data->group_status=='Closed')
			{
				if(PLATFORM=='IPHONE') { $myPage->add_css("div#left_col { display:none; }"); }
				$db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
				if($db->count()>0)
				{
					while($d = $db->get_next_res())
					{
						$zus_txt = "";
						if($d->game_status!='Closed') { $game_open = true; }
						if(isset($_GET['round'])) { if($d->game_round==$_GET['round']) { $zus_txt = "style='background-color:orange;'"; } }
						$myPage->add_content("<div class='menu_item'><button ".$zus_txt." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=$d->game_round\"';'>Runde $d->game_round</button></div>");
					}
				}
			}

			//Loaded tournament is started...
			if($t_data->group_status=='Started' OR $t_data->group_status=='Define_teams')
			{
				$zus = "";
				$game_open = false;
				$db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
				if($db->count()>0)
				{
					while($d = $db->get_next_res())
					{
						if($d->game_status!='Closed') { $game_open = true; }
						if(isset($_GET['round'])) { if($d->game_round==$_GET['round']) { $zus = "style='background-color:orange;'"; }  else { $zus = ""; } }

						$myPage->add_content("<div class='menu_item'><button ".$zus." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=$d->game_round\"';'>Runde $d->game_round</button></div>");
					}
				}
				if(!$game_open AND ($myTournament->get_system()=='Schoch' OR $myTournament->get_system()=='Doppel_dynamisch' OR $myTournament->get_system()=='Doppel_fix'))
				{
					if(isset($_GET['round'])) { if($t_data->group_round==$_GET['round']) { $zus = "style='background-color:orange;'"; } else { $zus = ""; } }
					$myPage->add_content("<div class='menu_item'><button ".$zus." onclick='window.location=\"index.php?tournament_id=$_GET[tournament_id]&round=$t_data->group_round\"';'>Runde $t_data->group_round</button></div>");
				}

				if(isset($_GET['round']))
				{
					if($myTournament->get_system()=='Schoch' OR $myTournament->get_system()=='Doppel_dynamisch' OR $myTournament->get_system()=='Doppel_fix')
					{
						$db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='".$_GET['round']."'");
						$round_closed = false;
						while($d=$db->get_next_res())
						{
							if($d->game_status=='Closed') { $round_closed = true; break; }
						}
						if($db->count()>0) { $is_gelost=true; } else { $is_gelost = false; }

						if(!$round_closed)
						{
							if($is_gelost) { $zus = "style='display:none;' "; }  else { $zus = ""; }
							$myPage->add_content("<div class='menu_item'><button id='auslosen' ".$zus."onclick='start_it();'>Auslosen</button></div>");

							if(!$is_gelost) { $zus = "style='display:none;' "; $zus2 = "style='display:none;' "; } else { $zus = ""; $zus2 = "style='background-color:red;'"; }
							$myPage->add_content("<div class='menu_item'><button id='loeschen' ".$zus2."onclick='clear_it();'>Auslosung löschen</button></div>");
							$myPage->add_content("<div class='menu_item'><button id='runde_schliessen' ".$zus."onclick='close_round();'>Runde abschliessen</button></div>");
						}
						else
						{
							$myPage->add_content("<div class='menu_item'><button style='background-color:red;' id='runde_schliessen' ".$zus."onclick='reset_round(".$_GET['round'].");'>Auf Runde ".$_GET['round']." zurücksetzen</button></div>");
						}
					}
				}
				else
				{
					$myPage->add_content("<div class='menu_item'><button style='background-color:olive;' id='turnier_schliessen' ".$zus."onclick='close_tournament();'>Abschliessen</button></div>");
					$myPage->add_content("<div class='menu_item'><button style='background-color:red;' id='turnier_abbrechen' ".$zus."onclick='stopp_tournament();'>Abbrechen</button></div>");
				}
			}
		}
		else
		{
			$myPage->add_content("<div class='menu_item'><button onclick='new_tournament();'>Neues Turnier</button></div>");
		}
		$myPage->add_content("	</div>");
		$myPage->add_content("</div>");
		//***********************************************************************************************************************************


		$myPage->add_content("<div id='left_col'>");
		if(isset($_GET['tournament_id']))
		{
			if($t_data->group_status=='Started' OR $t_data->group_status=='Closed')
			{
				$myPage->add_content($myTournament->get_users_from_tournament());
			}
			if($t_data->group_status=='Define_teams')
			{
				$myPage->add_content($myTournament->get_users_for_teams());
			}

			if($t_data->group_status=='define_seeded_players')
			{
				$myPage->add_content($myTournament->get_users_for_seedings());
			}

			if($t_data->group_status=='New')
			{
				$myPage->add_content("<a href='".$page->change_parameter('order_by','alphabetical')."'><img style='height:48px;' src='".level."inc/imgs/sort_az_descending.png' title='Alphabetisch' alt='Alphabetisch' /></a>");
				$myPage->add_content("<a href='".$page->change_parameter('order_by','gender')."'><img style='height:48px;' src='".level."inc/imgs/male_female.png' title='Geschlecht' alt='Geschlecht' /></a>");
				$myPage->add_content("<a href='".$page->change_parameter('order_by','age')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_age.png' title='Alter' alt='Alter' /></a>");
				$myPage->add_content("<a href='".$page->change_parameter('order_by','location')."'><img style='height:48px;' src='".level."inc/imgs/sort_by_location.png' title='Trainingsort' alt='Trainingsort' /></a>");
				$myPage->add_content("<hr>");
				$page->reset();
				$db->sql_query("SELECT * FROM location_permissions
												LEFT JOIN locations ON loc_permission_loc_id = location_id
												WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'
												ORDER BY location_name");
				if($db->count()>1)
				{
					$myPage->add_content("<form id='change_location_filter' action='".$page->change_parameter('action','change_location_filter')."' method='POST'>");
					$myPage->add_content("<select name='location' style='width:90%;margin:2.5%;' onchange=\"$('#change_location_filter').submit();\">");
					$myPage->add_content("<option value=''>-- Alle Standorte --</option>");
					while ($d=$db->get_next_res())
					{
						$myPage->add_content("<option");
						if(isset($_GET['location_filter']) && $_GET['location_filter']==$d->location_id) {$myPage->add_content(" selected='1'"); }
						$myPage->add_content(" value='".$d->location_id."'>".$d->location_name."</option>");
					}
					$myPage->add_content("</select>");
					$myPage->add_content("</form>");
					$myPage->add_content("<hr style='margin:0px;'>");
					$myPage->add_content($myTournament->get_all_users('add_user',$_GET['order_by']));
					$myPage->add_content("</div>");
				}				
			}
		}
		else
		{
			$myPage->add_content(get_all_tournaments($db));
		}
		$myPage->add_content("</div>");

		$myPage->add_content("<div id='right_col'>");
		if(isset($_GET['tournament_id']))
		{
			if(isset($_GET['round']))
			{
				$pre_checks='OK';

				if($myTournament->get_system()=='Doppel_fix')
				{
					//Check if partners are defined
					$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='".$_GET['tournament_id']."' AND group2user_partner_id IS NULL");
					if($db->count()>0) { $pre_checks='NOT_OK'; $myPage->add_content($myTournament->get_partner_definition()); }
				}

				if($pre_checks=='OK')
				{
					$i=1;
					for($i;$i<=$anz_felder;$i++)
					{
						$db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
						if($db->count()>0)
						{
						 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
							if($data->game_status=='Closed')
							{
								$myPage->add_content("<div class='court' id='court$i'><img src='court.php?action=fill&game_id=$data->game_id' class='court'/></div>");
							}
							else
							{
								$myPage->add_content("<div class='court' id='court$i'><img src='court.php?action=fill&game_id=$data->game_id' class='court' onclick='check_result($i);'/></div>");
							}
						}
						else
						{
							$myPage->add_content("<div class='court' id='court$i'><img src='court.php?action=clear' class='court'/></div>");
						}
					}
				}
			}

			if($t_data->group_status=='define_seeded_players')
			{
				$myPage->add_content($myTournament->get_seeding_definition());
			}

			if($t_data->group_status=='New')
			{
				$myPage->add_content(get_users($db,'assigned'));
			}

			if($t_data->group_status=='Closed')
			{
				if(!isset($_GET['round']))
				{
					$x = "<h1>Siegerehrung</h1>";
					$i = 0;
					$p = array();

					if($t_data->group_system=='Doppel_fix')
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
					$x.= "<div style='width:100%;'><img style='width:100%;' src='podest.php?p1=$p[0]&p2=$p[1]&p3=$p[2]&p4=$p[3]&p5=$p[4]&p6=$p[5]'/>";
					$x.= "</div><div style='clear:both;margin-left:5vw;'>";

			  	$db->sql_query("SELECT * FROM group2user
			  												LEFT JOIN users ON group2user_user_id = user_id
			  												WHERE group2user_group_id = '$_GET[tournament_id]'
			  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT $limit,100");
					while($d = $db->get_next_res())
					{
						$my_user = new user($d->group2user_user_id);

						if($t_data->group_system=='Doppel_fix')
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

					$myPage->add_content($x);
				}
			}

			if(!isset($_GET['round']))
			{
				if($myTournament->get_status()=='Started' OR $myTournament->get_status()=='Closed')
				{
					if($myTournament->get_system()=='Gruppenspiele')
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
						if($myTournament->get_status()=='Closed') { $myPage->add_content("<hr style='clear:both;'/>"); }
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
		//************************************************************************************
		//AJAX Handling
		//************************************************************************************
		if($_GET['ajax']=='load') { print "<img src='wuerfel.gif' class='wuefel'/>"; }
		if($_GET['ajax']=='get_left_col') { print $myTournament->get_users_from_tournament(); }

		if($_GET['ajax']=='delete_permission')
		{
			$data = $db->sql_query_with_fetch("SELECT * FROM groups WHERE group_id='$_GET[tournament_id]'");
			$x = "<h1>Willst du folgendes Turnier wirklich löschen?</h1>";
			$x.= "<h2>".$data->group_title."</h2><h3>".nl2br($data->group_description)."</h3>";
			$x.= "<button onclick='window.location=\"index.php?action=delete_tournament&tournament_id=$_GET[tournament_id]\"' style='background-color:red;'>Ja</button>";
			$x.= "<button onclick='window.location=\"index.php\"'>Nein</button>";
			print $x;
		}

		if($_GET['ajax']=='add_as_seeded')
		{
			$max_seeding_pos = round($myTournament->get_number_of_players()/2,0);
			if($max_seeding_pos>8) { $max_seeding_pos=8; }

			$db->sql_query("SELECT * FROM group2user
											WHERE group2user_group_id= :t_id AND group2user_seeded < 99",array('t_id'=>$myTournament->id));
			$seeding_pos = $db->count()+1;
			if($max_seeding_pos>=$seeding_pos)
			{
				$data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_user_id= :u_id",array('t_id'=>$myTournament->id,'u_id'=>$_GET['user_id']));
				$db->update(array('group2user_seeded'=>$seeding_pos),'group2user','group2user_id',$data->group2user_id);
				print "OK";
			}
			else
			{
				print "Alle Setzplätze sind gefüllt";
			}
		}

		if($_GET['ajax']=='delete_last_seeding')
		{
			$data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id= :t_id AND group2user_seeded < 99 ORDER BY group2user_seeded DESC LIMIT 1",array('t_id'=>$myTournament->id));
			if(isset($data->group2user_id)) { $db->sql_query("UPDATE group2user SET group2user_seeded=99 WHERE group2user_id= :d_id",array('d_id'=>$data->group2user_id)); }
			print "OK";
		}


		if($_GET['ajax']=='add_as_partner')
		{
			$arr_users = array();
			//Find current player who is searching a partner
			for($i=1;$i <= $myTournament->get_number_of_players()/2;$i++)
			{
				$w_str = "WHERE group2user_group_id='".$myTournament->id."'";
				foreach($arr_users as $u)
				{
					$w_str.= " AND group2user_user_id!='$u'";
				}
				$data = $db->sql_query_with_fetch("SELECT * FROM group2user LEFT JOIN users ON group2user_user_id=users.user_id $w_str ORDER BY user_account LIMIT 1");
				$myUser = new user($data->user_id);
				$arr_users[] = $data->user_id;
				if($data->group2user_partner_id!='')
				{
					$myUser = new user($data->group2user_partner_id);
					$arr_users[] = $data->group2user_partner_id;
				}
				else
				{
					break;
				}
			}
			$msg = "";
			foreach($arr_users as $u)
			{
				if($u==$_GET['user_id']) { $msg = 'Spieler hat schon einen Partner'; break; }
			}
			if($msg=='')
			{
				$db->update(array('group2user_partner_id'=>$_GET['user_id']),'group2user','group2user_id',$data->group2user_id);
				$data = $db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='".$myTournament->id."' AND group2user_user_id='".$_GET['user_id']."'");
				$db->update(array('group2user_partner_id'=>$myUser->id),'group2user','group2user_id',$data->group2user_id);

				//Check if all partner are defined, if yes set status to Started
				$db->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='".$myTournament->id."' AND group2user_partner_id IS NULL");
				if($db->count()==0) { $db->update(array('group_status'=>'Started'),'groups','group_id',$myTournament->id); }

				print "OK";
			}
			else
			{ print $msg; }
		}

		if($_GET['ajax']=='delete_team')
		{
			$db->sql_query("UPDATE group2user SET group2user_partner_id=NULL WHERE group2user_group_id='".$myTournament->id."' AND group2user_user_id='".$_GET['user_id']."'");
			$db->sql_query("UPDATE group2user SET group2user_partner_id=NULL WHERE group2user_group_id='".$myTournament->id."' AND group2user_partner_id='".$_GET['user_id']."'");
			print "OK";
		}


		if($_GET['ajax']=='show')
		{
			$db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
			if($db->count()>0)
			{
			 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
				print "<img src='court.php?action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
			}
			else
			{
				print "<img src='court.php?action=clear' class='court'/>";
			}
		}

		if($_GET['ajax']=='set_winner')
		{
		 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");
			if($_GET['winner_id']=='0')
			{
				$db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL'),'games','game_id',$data->game_id);
			}
			else
			{
				if(substr($myTournament->get_system(),0,6)=='Doppel')
				{
					if($data->game_player1_id==$_GET['winner_id']) { $winner2 = $data->game_player3_id; }
					if($data->game_player2_id==$_GET['winner_id']) { $winner2 = $data->game_player4_id; }
					if($data->game_player3_id==$_GET['winner_id']) { $winner2 = $data->game_player1_id; }
					if($data->game_player4_id==$_GET['winner_id']) { $winner2 = $data->game_player2_id; }
					$db->update(array('game_winner_id'=>$_GET['winner_id'],'game_winner2_id'=>$winner2),'games','game_id',$data->game_id);
				}
				else
				{
					$db->update(array('game_winner_id'=>$_GET['winner_id']),'games','game_id',$data->game_id);
				}

				//Insert information in tournament log
				$winner = new user($_GET['winner_id']);
				$game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
				if(substr($myTournament->get_system(),0,6)=='Doppel')
				{
					$winner2 = new user($winner2);
					if($game->game_player1_id==$_GET['winner_id']) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
					$winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
				}
				else
				{
					if($game->game_player1_id==$_GET['winner_id']) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
					$winner_txt = $winner->login; $looser_txt = $looser->login;
				}

				$db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
			}
		 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

			//Show court
			if($db->count()>0)
			{
				print "<img src='court.php?created_on=".time()."&action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
			}
			else
			{
				print "<img src='court.php?action=clear' class='court' onclick='check_result($_GET[court_id]);'/>";
			}

			//Update winners in group table
			if($myTournament->get_system()=='Gruppenspiele')
			{
				$myTournament->update_winners();
			}
		}

		if($_GET['ajax']=='set_points_and_winner')
		{
		 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

			if($myTournament->get_counting()=='pointsOneSet')
			{
				$winner_id=0;
				if($_GET['set1_p1']>$_GET['set1_p2']) { $winner_id = $data->game_player1_id; $looser = new user($data->game_player2_id); }
				if($_GET['set1_p1']<$_GET['set1_p2']) { $winner_id = $data->game_player2_id; $looser = new user($data->game_player1_id); }

				//If double is played, set winner2
				if($data->game_player3_id>0) { if($data->game_player1_id==$winner_id) { $winner2_id=$data->game_player3_id; } else { $winner2_id = $data->game_player4_id; } } else { $winner2_id=null; }

				if($winner_id!=0)
				{
					$db->update(array('game_winner_id'=>$winner_id,'game_winner2_id'=>$winner2_id,'game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2']),'games','game_id',$data->game_id);

					//Insert information in tournament log
					$winner = new user($winner_id);
					$game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
					if($data->game_player3_id>0)
					{
						$winner2 = new user($winner2_id);
						if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
						$winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
					}
					else
					{
						if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
						$winner_txt = $winner->login; $looser_txt = $looser->login;
					}
					$db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');

				}
				else
				{
					$db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL','game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2']),'games','game_id',$data->game_id);
				}
			}


			if($myTournament->get_counting()=='official2sets' OR $myTournament->get_counting()=='2sets11points' OR $myTournament->get_counting()=='2setswinning')
			{
				$wins_p1=0; $wins_p2=0;
				for($i=1;$i<4;$i++)
				{
		      if($_GET['set'.$i.'_p1']>$_GET['set'.$i.'_p2']) { $wins_p1++; }
		      if($_GET['set'.$i.'_p1']<$_GET['set'.$i.'_p2']) { $wins_p2++; }
				}

				if(max($wins_p1,$wins_p2)>0)
				{
					if($wins_p1>$wins_p2) { $winner_id=$data->game_player1_id; $looser = new user($data->game_player2_id); } else { $winner_id=$data->game_player2_id; $looser = new user($data->game_player1_id); }

					//If double is played, set winner2
					if($data->game_player3_id>0) { if($data->game_player1_id==$winner_id) { $winner2_id=$data->game_player3_id; } else { $winner2_id = $data->game_player4_id; } } else { $winner2_id=null; }

					$db->update(array('game_winner_id'=>$winner_id,'game_winner2_id'=>$winner2_id,'game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2'],'game_set2_p1'=>$_GET['set2_p1'],'game_set2_p2'=>$_GET['set2_p2'],'game_set3_p1'=>$_GET['set3_p1'],'game_set3_p2'=>$_GET['set3_p2']),'games','game_id',$data->game_id);

					//Insert information in tournament log
					$winner = new user($winner_id);
					$game = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='".$data->game_id."'");
					if($data->game_player3_id>0)
					{
						$winner2 = new user($winner2_id);
						if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); $looser2 = new user($game->game_player4_id); } else { $looser = new user($game->game_player1_id); $looser2 = new user($game->game_player3_id); }
						$winner_txt = $winner->login."/".$winner2->login; $looser_txt=$looser->login."/".$looser2->login;
					}
					else
					{
						if($game->game_player1_id==$winner_id) { $looser = new user($game->game_player2_id); } else { $looser = new user($game->game_player1_id); }
						$winner_txt = $winner->login; $looser_txt = $looser->login;
					}
					$db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>$winner_txt.' hat gewonnen','news_text'=>'Im Turnier '.$myTournament->get_title().' hat soeben '.$winner_txt.' gegen '.$looser_txt.' gewonnen. Herzliche Gratulation!'),'news');
				}
				else
				{
					$db->update(array('game_winner_id'=>'NULL','game_winner2_id'=>'NULL','game_set1_p1'=>$_GET['set1_p1'],'game_set1_p2'=>$_GET['set1_p2'],'game_set2_p1'=>$_GET['set2_p1'],'game_set2_p2'=>$_GET['set2_p2'],'game_set3_p1'=>$_GET['set3_p1'],'game_set3_p2'=>$_GET['set3_p2']),'games','game_id',$data->game_id);
				}
			}

		 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$_GET['court_id']."'");

			if($db->count()>0)
			{
				print "<img src='court.php?created_on=".time()."&action=fill&game_id=".$data->game_id."' class='court' onclick='check_result($_GET[court_id]);'/>";
			}
			else
			{
				print "<img src='court.php?action=clear' class='court' onclick='check_result($_GET[court_id]);'/>";
			}
			if($myTournament->get_system()=='Gruppenspiele')
			{
				$myTournament->update_winners();
			}

		}


		if($_GET['ajax']=='clear')
		{
			$db->sql_query("DELETE FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='".$_GET['round']."'");
			print "<img src='court.php?action=clear' class='court'/>";
		}
		if($_GET['ajax']=='new_tournament') { print get_new_tournament($db); }
		if($_GET['ajax']=='show_infos') { print get_tournament_infos($db,$_GET['tournament_id']); }
		if($_GET['ajax']=='new_user') { print get_new_user($_GET['tournament_id']); }
		if($_GET['ajax']=='delete_user') { print get_users_to_delete($_GET['tournament_id'],$db); }
		if($_GET['ajax']=='start_tournament')
		{
			$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]'");
			$anz_teilnehmer = $db->count();
			if($myTournament->get_system()=='Gruppenspiele')
			{
				if($anz_teilnehmer<3 OR $anz_teilnehmer>6)
				{
					print "Teilnehmeranzahl für Gruppenspiele muss zwischen 3 und 6 liegen";
				}
				else
				{
					//Save Players to array
					$players = array();
					while($d = $db->get_next_res())
					{
						$players[] = $d->group2user_user_id;
					}
					if($anz_teilnehmer=='3')
					{
						$anz_courts = 1;
						//Round 1
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'1','game_player1_id'=>$players[0],'game_player2_id'=>$players[1]),'games');
						//Round 2
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'2','game_player1_id'=>$players[0],'game_player2_id'=>$players[2]),'games');
						//Round 3
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'3','game_player1_id'=>$players[1],'game_player2_id'=>$players[2]),'games');
					}

					if($anz_teilnehmer=='4')
					{
						$anz_courts = 2;
						//Round 1
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'1','game_player1_id'=>$players[0],'game_player2_id'=>$players[1]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'1','game_player1_id'=>$players[2],'game_player2_id'=>$players[3]),'games');
						//Round 2
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'2','game_player1_id'=>$players[0],'game_player2_id'=>$players[2]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'2','game_player1_id'=>$players[1],'game_player2_id'=>$players[3]),'games');
						//Round 3
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'3','game_player1_id'=>$players[0],'game_player2_id'=>$players[3]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'3','game_player1_id'=>$players[1],'game_player2_id'=>$players[2]),'games');
					}

					if($anz_teilnehmer=='5')
					{
						$anz_courts = 2;
						//Round 1
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'1','game_player1_id'=>$players[0],'game_player2_id'=>$players[1]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'1','game_player1_id'=>$players[2],'game_player2_id'=>$players[3]),'games');
						//Round 2
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'2','game_player1_id'=>$players[0],'game_player2_id'=>$players[2]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'2','game_player1_id'=>$players[1],'game_player2_id'=>$players[4]),'games');
						//Round 3
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'3','game_player1_id'=>$players[0],'game_player2_id'=>$players[3]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'3','game_player1_id'=>$players[2],'game_player2_id'=>$players[4]),'games');
						//Round 4
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'4','game_player1_id'=>$players[0],'game_player2_id'=>$players[4]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'4','game_player1_id'=>$players[1],'game_player2_id'=>$players[3]),'games');
						//Round 5
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'5','game_player1_id'=>$players[1],'game_player2_id'=>$players[2]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'5','game_player1_id'=>$players[3],'game_player2_id'=>$players[4]),'games');
					}

					if($anz_teilnehmer=='6')
					{
						$anz_courts = 3;
						//Round 1
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'1','game_player1_id'=>$players[0],'game_player2_id'=>$players[1]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'1','game_player1_id'=>$players[2],'game_player2_id'=>$players[3]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'3','game_round'=>'1','game_player1_id'=>$players[4],'game_player2_id'=>$players[5]),'games');
						//Round 2
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'2','game_player1_id'=>$players[0],'game_player2_id'=>$players[2]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'2','game_player1_id'=>$players[1],'game_player2_id'=>$players[4]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'3','game_round'=>'2','game_player1_id'=>$players[3],'game_player2_id'=>$players[5]),'games');
						//Round 3
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'3','game_player1_id'=>$players[0],'game_player2_id'=>$players[3]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'3','game_player1_id'=>$players[1],'game_player2_id'=>$players[5]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'3','game_round'=>'3','game_player1_id'=>$players[2],'game_player2_id'=>$players[4]),'games');
						//Round 4
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'4','game_player1_id'=>$players[0],'game_player2_id'=>$players[4]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'4','game_player1_id'=>$players[1],'game_player2_id'=>$players[3]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'3','game_round'=>'4','game_player1_id'=>$players[2],'game_player2_id'=>$players[5]),'games');
						//Round 5
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'1','game_round'=>'5','game_player1_id'=>$players[0],'game_player2_id'=>$players[5]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'2','game_round'=>'5','game_player1_id'=>$players[1],'game_player2_id'=>$players[2]),'games');
						$db->insert(array('game_group_id'=>$_GET['tournament_id'],'game_location'=>'3','game_round'=>'5','game_player1_id'=>$players[3],'game_player2_id'=>$players[4]),'games');
					}

					$db->update(array('group_status'=>'Started','group_round'=>'1','group_courts'=>$anz_courts),'groups','group_id',$_GET['tournament_id']);
					print "OK";
				}
			}

			if($myTournament->get_system()=='Schoch')
			{
				if($anz_teilnehmer>3)
				{
					//Teilnehmer ungerade?
					if($anz_teilnehmer % 2 != 0)
					{
						$db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>'1'),'group2user');
						$anz_teilnehmer++;
					}
					$db->update(array('group_status'=>'Started','group_round'=>'1','group_courts'=>$anz_teilnehmer/2),'groups','group_id',$_GET['tournament_id']);
					print "OK";
				}
				else
				{
					print "Zu wenig Teilnehmer für Spielsystem Schoch (min. 4 Spieler)";
				}
			}

			if($myTournament->get_system()=='Doppel_dynamisch')
			{
				if($anz_teilnehmer>3)
				{
					$anz_courts = ceil($anz_teilnehmer/4);
					$rest = $anz_teilnehmer % 4;
					if($rest==3) { $anz_courts = $anz_courts + 1; } //one court for the single game and one to show the freilos
					//Teilnehmer ungerade?, Freilos hinzufügen
					if($anz_teilnehmer % 2 != 0)
					{
						$db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>'1'),'group2user');
						$anz_teilnehmer++;
					}
					$db->update(array('group_status'=>'Started','group_round'=>'1','group_courts'=>$anz_courts),'groups','group_id',$_GET['tournament_id']);
					print "OK";
				}
				else
				{
					print "Zu wenig Teilnehmer für Doppel (min. 4 Spieler)";
				}
			}

			if($myTournament->get_system()=='Doppel_fix')
			{
				if($anz_teilnehmer>7)
				{
					//Teilnehmer ungerade?
					if($anz_teilnehmer % 4 == 0)
					{
						$db->update(array('group_status'=>'Define_teams','group_round'=>'1','group_courts'=>ceil($anz_teilnehmer/4)),'groups','group_id',$_GET['tournament_id']);
						print "OK";
					}
					else
					{
						print "Teilnehmerzahl muss durch 4 teilbar sein (4,8,12,16, etc.)";
					}
				}
				else
				{
					print "Zu wenig Teilnehmer für Doppel (min. 8 Spieler)";
				}
			}


		}
		if($_GET['ajax']=='close_round')
		{
			$db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
			$all_games_ok = true;
			while($d = $db->get_next_res())
			{
				if($d->game_winner_id==0) { $all_games_ok = false; break; }
			}
			if($all_games_ok)
			{
				$d = $db->sql_query("UPDATE games SET game_status='Closed' WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
				$new_round = $_GET['round']+1;
				$db->update(array('group_round'=>$new_round),'groups','group_id',$_GET['tournament_id']);
				$myTournament->calc_BHZ();
				print "OK;".$new_round;
			}
			else
			{
				print "Nicht alle Spiele eingetragen";
			}
		}

		if($_GET['ajax']=='reset_round')
		{
			$db->sql_query("DELETE FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round > '".$_GET['round']."'");
			$db->sql_query("UPDATE games SET game_status='New' WHERE game_group_id='".$_GET['tournament_id']."' AND game_round ='".$_GET['round']."'");
			$myTournament->calc_BHZ();

			print "OK;".$_GET['round'];
		}

		if($_GET['ajax']=='define_seeded_players')
		{
			$db->update(array('group_status'=>'define_seeded_players'),'groups','group_id',$_GET['tournament_id']);
			print "OK";
		}

		if($_GET['ajax']=='close_tournament')
		{

			$db->sql_query("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."'");
			$all_games_ok = true;
			while($d = $db->get_next_res())
			{
				if($d->game_status!='Closed')
				{
					if($myTournament->get_system()=='Schoch') { $all_games_ok = false; break; }
					if($myTournament->get_system()=='Gruppenspiele' AND $d->game_winner_id=='') { $all_games_ok = false; break; }
				}
			}
			if($all_games_ok)
			{
				if($myTournament->get_system()=='Gruppenspiele')
				{
					$db->update(array('game_status'=>'Closed'),'games','game_group_id',$_GET['tournament_id']);
				}
				$db->update(array('group_status'=>'Closed'),'groups','group_id',$_GET['tournament_id']);
				print "OK";
			}
			else
			{
				print "Nicht alle Runden abgeschlossen";
			}
		}

		if($_GET['ajax']=='delete_tournament')
		{
			$db->delete('groups','group_id',$_GET['tournament_id']);
			print "OK";
		}

		if($_GET['ajax']=='stopp_tournament')
		{
			$db->sql_query("DELETE FROM games WHERE game_group_id='$_GET[tournament_id]'");
			$db->sql_query("DELETE FROM news WHERE news_tournament_id='$_GET[tournament_id]'");
			$db->sql_query("DELETE FROM group2user WHERE group2user_user_id='1' AND group2user_group_id='$_GET[tournament_id]'"); //Remove Dummy user if exist
			$db->update(array('group_status'=>'New','group_round'=>'1'),'groups','group_id',$_GET['tournament_id']);
			$db->update(array('group2user_partner_id'=>'NULL'),'group2user','group2user_group_id',$_GET['tournament_id']); //Reset Partner definition (only used in "Doppel_fix")
			//$db->update(array('group2user_seeded'=>'99'),'group2user','group2user_group_id',$_GET['tournament_id']); //Reset Partner definition (only used in "Doppel_fix")
			$myTournament->calc_BHZ();
		}

		if($_GET['ajax']=='save_tournament')
		{
			$db->insert(array('group_title'=>$_GET['name'],'group_description'=>$_GET['desc'],'group_status'=>'New'),'groups');
			print $db->last_inserted_id;
		}

		if($_GET['ajax']=='define_games')
		{
			//Tournament with Freilos
			$with_freilos=false;
			$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='1'");
			if($db->count()==1) { $with_freilos=true; $myLogger->write_to_log("Tournament","Freilos vorhanden"); }

			if(substr($myTournament->get_system(),0,6)=='Doppel')
			{
				$users_on_court = null;
				$arr_ids = null;
				$arr_opponents = null;
				$w_str = null;
				$w_str2 = null;
				$db2 = clone($db);
				$db3 = clone($db);
				$court_nr=1;

				//Get players order by wins
				$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY group2user_wins DESC,group2user_BHZ DESC, RAND()");
				if($myTournament->get_system()=='Doppel_dynamisch')
				{
					$p1=null;$p2=null;$p3=null;$p4=null;
					//If a single should be played, define first
	
					if($db->count()%4 != 0 OR $with_freilos)
					{
						$myLogger->write_to_log("Tournament","Anzahl Teilnehmer nicht durch 4 teilbar");
						//select all player, but not Freilos
						$w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1'";

						//get all single games of current tournament and extract users
						$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id IS NULL AND (game_player1_id='1' OR game_player2_id='1')");
						if($db3->count()>0)
						{
							while($d = $db3->get_next_res())
							{
								$w_str .= " AND group2user_user_id!='".$d->game_player1_id."' AND group2user_user_id!='".$d->game_player2_id."'";
							}
						}

						//if Freilos is activated, set always one of the single players with this user
						if($with_freilos) { $p1 = '1'; $limit = '1'; } else { $limit = '2'; }
						$db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, rand() LIMIT $limit");
						//if there are no more player which are not played single, choose randomly
						if($db3->count()<$limit) 
						{ 
							$myLogger->write_to_log("Tournament","Keine Person fürs Freilos gefunden, suche per Zufall jemanden aus");
							$db3->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY rand() LIMIT $limit"); 
						}
						else
						{
							$myLogger->write_to_log("Tournament","Freilos sauber zugeteilt, folgender SQL String wurde verwendet");
							$myLogger->write_to_log("Tournament","SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, rand() LIMIT $limit");
						}
						while($d = $db3->get_next_res())
						{
							if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
						}

						//insert games and set players on court, that they are not available anymore for the other games
						if($limit==1) { $win_id = $p2; } else { $win_id = null; }
						$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_winner_id'=>$win_id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
						$court_nr++;
						$users_on_court.= $p1.'/'.$p2.'/';
						$p1=null;$p2=null;$p3=null;$p4=null;

						$open_players = $myTournament->get_number_of_players() - 2;
						//Do we need a single game?
						if($open_players % 4 > 0)
						{
							$myLogger->write_to_log("Tournament","Einzelspiel definieren");
							//Combine where-string which excludes all player, which are allready assigned
							$w_str = "WHERE group2user_group_id='$_GET[tournament_id]'";
							$arr_users = explode('/',$users_on_court);
							foreach($arr_users as $user)
							{
								if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
							}

							//get all single games of current tournament and extract users
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id IS NULL AND game_player1_id!='1' AND game_player2_id!='1'");
							if($db3->count()>0)
							{
								while($d = $db3->get_next_res())
								{
									$w_str .= " AND group2user_user_id!='".$d->game_player1_id."' AND group2user_user_id!='".$d->game_player2_id."'";
								}
							}

							$db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins ASC, group2user_BHZ ASC, rand() LIMIT 2");
							if($db3->count()==2)
							{
								//Found two players with no single games
								while($d = $db3->get_next_res())
								{
									if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
								}
								$myLogger->write_to_log("Tournament","2 Spieler gefunden, die noch keine Einzel gespielt haben");

								//insert games and set players on court, that they are not available anymore for the other games
								$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
								$court_nr++;
								$users_on_court.= $p1.'/'.$p2.'/';
								$p1=null;$p2=null;$p3=null;$p4=null;
							}
							else
							{
								$limit = 2;
								if($db3->count()==1)
								{
									//Found one player with no single games
									$d = $db3->get_next_res();
									$p1 = $d->group2user_user_id;
									$limit = 1;
									$myLogger->write_to_log("Tournament","1 Spieler gefunden, der noch keine Einzel gespielt hat, suche noch jemanden per Zufall");
								}
								else
								{
									$myLogger->write_to_log("Tournament","Keine Spieler gefunden, die noch keine Einzel gespielt haben, suche per Zufall aus");
								}
								$db3->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1' AND group2user_user_id!=$p1 ORDER BY rand() LIMIT $limit");								
								//Get another player randomly
								while($d = $db3->get_next_res())
								{
									if($p1==0) { $p1 = $d->group2user_user_id; } else { $p2 = $d->group2user_user_id; }
								}
								//insert games and set players on court, that they are not available anymore for the other games
								$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
								$court_nr++;
								$users_on_court.= $p1.'/'.$p2.'/';
								$p1=null;$p2=null;$p3=null;$p4=null;
							}
						}
					}

					while($d = $db->get_next_res())
					{
						$w_str2 = null;
						//Check if current player allready is assigned to a game
						if(strpos($users_on_court,$d->group2user_user_id)===false)
						{
							$curr_user_id = $d->group2user_user_id;
							if($p1==null) { $p1 = $curr_user_id; } else { $p2 = $curr_user_id; }
							//Combine where-string which excludes all player, which are allready assigned
							$w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$curr_user_id'";
							$arr_users = explode('/',$users_on_court);
							foreach($arr_users as $user)
							{
								if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
							}

							//Combine where-string with all players which are allready partner of current player
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player1_id = '$curr_user_id'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player3_id."'"; }
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player2_id = '$curr_user_id'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player4_id."'"; }
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player3_id = '$curr_user_id'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player4_id = '$curr_user_id'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }


							//Search best opponent which is not allready assigned and has not allready played against current player
							$db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins ASC, rand() LIMIT 1");

							//If someone has been found take it
							if($db3->count()==1)
							{
								$d3 = $db3->get_next_res();
								if($p3==null) { $p3 = $d3->group2user_user_id; } else { $p4 = $d3->group2user_user_id; }
								$users_on_court.= $curr_user_id.'/'.$d3->group2user_user_id.'/';
							}
							//if nobody has been found, take one of the not allready assigned opponent by random
							else
							{
								$db3->sql_query("SELECT * FROM group2user $w_str ORDER BY rand() LIMIT 1");
								if($db3->count()==1)
								{
									$d3 = $db3->get_next_res();
									if($p3==null) { $p3 = $d3->group2user_user_id; } else { $p4 = $d3->group2user_user_id; }
									$users_on_court.= $curr_user_id.'/'.$d3->group2user_user_id.'/';
								}
							}
							if($p4>0)
							{
								$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_player3_id'=>$p3,'game_player4_id'=>$p4,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
								$court_nr++;
								$p1=null;$p2=null;$p3=null;$p4=null;
							}
						}
					}
				}

				if($myTournament->get_system()=='Doppel_fix')
				{
					$arr_on_court = array();

					while($d = $db->get_next_res())
					{
						$w_str2 = null;
						//Check if current player allready is assigned to a game
						if(!in_array($d->group2user_user_id,$arr_on_court))
						{
							$p1 = $d->group2user_user_id;
							$arr_on_court[] = $p1;
							$d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p1'");
							$p3 = $d3->group2user_partner_id;
							$arr_on_court[] = $p3;
							//Combine where-string which excludes all player, which are allready assigned
							$w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$p1' AND group2user_user_id!='$p3'";
							foreach($arr_on_court as $user)
							{
								if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
							}

							//Combine where-string with all players which are allready opponents of current player
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND (game_player1_id = '$p1' OR game_player3_id = '$p1')");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."' AND group2user_user_id != '".$d3->game_player4_id."'"; }
							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND (game_player2_id = '$p1' OR game_player4_id = '$p1')");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."' AND group2user_user_id != '".$d3->game_player3_id."'"; }

							//Search best opponent which is not allready assigned and has not allready played against current player
							$db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins DESC, rand() LIMIT 1");

							//If someone has been found take it
							if($db3->count()==1)
							{
								$d3 = $db3->get_next_res();
								$p2 = $d3->group2user_user_id;
								$arr_on_court[] = $p2;
								$d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p2'");
								$p4 = $d3->group2user_partner_id;
								$arr_on_court[] = $p4;
							}
							//if nobody has been found, take one of the not allready assigned opponent by random
							else
							{
								$db3->sql_query("SELECT * FROM group2user $w_str ORDER BY rand() LIMIT 1");
								if($db3->count()==1)
								{
									$d3 = $db3->get_next_res();
									$p2 = $d3->group2user_user_id;
									$arr_on_court[] = $p2;
									$d3 = $db3->sql_query_with_fetch("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='$p2'");
									$p4 = $d3->group2user_partner_id;
									$arr_on_court[] = $p4;
								}
							}

							//Insert players
							$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$p1,'game_player2_id'=>$p2,'game_player3_id'=>$p3,'game_player4_id'=>$p4,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');
							$court_nr++;
							$p1=null;$p2=null;$p3=null;$p4=null;
						}
					}
				}

				print "OK";
			}
			else
			{
				$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' ORDER BY group2user_wins DESC, group2user_seeded ASC, RAND()");
				if($db->count()>1)
				{
					if(isset($_GET['round']))
					{
						$users_on_court = null;
						$my_user = null;
						$w_str = null;
						$w_str2 = null;
						$db2 = clone($db);
						$db3 = clone($db);
						$court_nr=1;

						//Hat es ein Freilos?
						$db2->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id='1'");
						if($db2->count()>0)
						{
							$w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='1'";

							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player1_id = '1'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }

							$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player2_id = '1'");
							while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }

							$data = $db3->sql_query_with_fetch("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_seeded DESC, group2user_wins ASC, group2user_BHZ ASC, group2user_FBHZ ASC, rand() LIMIT 1");

							$my_user = new user(1);
							$opponent = new user($data->group2user_user_id);
							if($myTournament->get_counting()=='win') { $db3->insert(array('game_winner_id'=>$opponent->id,'game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round'],'game_duration'=>'0'),'games'); }
							if($myTournament->get_counting()=='pointsOneSet') { $db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round'],'game_duration'=>'0'),'games'); }
							if($myTournament->get_counting()=='official2sets') { $db3->insert(array('game_set1_p1'=>'10','game_set1_p2'=>'21','game_set2_p1'=>'10','game_set2_p2'=>'21','game_winner_id'=>$opponent->id,'game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round'],'game_duration'=>'0'),'games'); }
							if($myTournament->get_counting()=='2sets11points') { $db3->insert(array('game_set1_p1'=>'5','game_set1_p2'=>'11','game_set2_p1'=>'5','game_set2_p2'=>'11','game_winner_id'=>$opponent->id,'game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round'],'game_duration'=>'0'),'games'); }
							if($myTournament->get_counting()=='2setswinning') { $db3->insert(array('game_set1_p1'=>'10','game_set1_p2'=>'21','game_set2_p1'=>'10','game_set2_p2'=>'21','game_winner_id'=>$opponent->id,'game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round'],'game_duration'=>'0'),'games'); }

							$db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>'Freilos ausgelost','news_text'=>'Im Turnier '.$myTournament->get_title().' hat '.$opponent->login.' das Freilos bekommen.'),'news');

							$users_on_court.= $my_user->id.'/'.$opponent->id.'/';
							$opponent = null;
							$my_user = null;

							$court_nr++;
						}

						$x = $users_on_court;

						while($d = $db->get_next_res())
						{
							$w_str2 = null;
							if(strpos($users_on_court,$d->group2user_user_id)===false)
							{
								$curr_user_id = $d->group2user_user_id;
								$w_str = "WHERE group2user_group_id='$_GET[tournament_id]' AND group2user_user_id!='$curr_user_id'";
								$arr_users = explode('/',$users_on_court);
								foreach($arr_users as $user)
								{
									if($user!='') { $w_str.= " AND group2user_user_id != '$user'"; }
								}

								$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player1_id = '$curr_user_id'");
								while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player2_id."'"; }
								$db3->sql_query("SELECT * FROM games WHERE game_group_id='$_GET[tournament_id]' AND game_player2_id = '$curr_user_id'");
								while($d3 = $db3->get_next_res()) { $w_str2.= " AND group2user_user_id != '".$d3->game_player1_id."'"; }
								$db3->sql_query("SELECT * FROM group2user $w_str $w_str2 ORDER BY group2user_wins DESC, group2user_seeded DESC, rand() LIMIT 1");

								if($db3->count()==1)
								{
									$d3 = $db3->get_next_res();
									$opponent_user_id = $d3->group2user_user_id;

									$my_user = new user($curr_user_id);
									$opponent = new user($opponent_user_id);
									$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');

									$users_on_court.= $my_user->id.'/'.$opponent->id.'/';
									$opponent = null;
									$my_user = null;

									$court_nr++;
								}
								else
								{
									$db3->sql_query("SELECT * FROM group2user $w_str ORDER BY group2user_wins DESC, group2user_seeded DESC, rand() LIMIT 1");
									if($db3->count()==1)
									{
										$d3 = $db3->get_next_res();
										$opponent_user_id = $d3->group2user_user_id;

										$my_user = new user($curr_user_id);
										$opponent = new user($opponent_user_id);
										$db3->insert(array('game_group_id'=>$_GET['tournament_id'],'game_player1_id'=>$my_user->id,'game_player2_id'=>$opponent->id,'game_location'=>$court_nr,'game_round'=>$_GET['round']),'games');

										$users_on_court.= $my_user->id.'/'.$opponent->id.'/';
										$opponent = null;
										$my_user = null;

										$court_nr++;
									}
								}
							}
						}
						$db->insert(array('news_tournament_id'=>$_GET['tournament_id'],'news_title'=>'Neue Runde ausgelost','news_text'=>'Im Turnier '.$myTournament->get_title().' wurde eine neue Runde ausgelost.'),'news');
						print "OK";
					}
				}
				else
				{
					print "Zu wenig Teilnehmer";
				}
			}
		} //end of define games

		if($_GET['ajax']=='get_all_users') { print get_all_users($db); }

		if($_GET['ajax']=='get_result')
		{
			$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_round='$_GET[round]' AND game_group_id='$_GET[tournament_id]' AND game_location='$_GET[court]'");
			$u1 = new user($data->game_player1_id);
			$u2 = new user($data->game_player2_id);
			if($data->game_player3_id>0) { $u3 = new user($data->game_player3_id); }
			if($data->game_player4_id>0) { $u4 = new user($data->game_player4_id); }

			$x = "<div class='result'>";
			$x.= "<h2>Wer hat in Runde ".$_GET['round']." gewonnen?</h2>";
			$x.= "<table style='width:100%;'><tr>";
			if($myTournament->get_counting()=='win')
			{
				if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
				if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "<td style='text-align:center;font-size:35pt;' rowspan='2' onclick='set_winner(0,$data->game_location);'>VS</td>";

				if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
				if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "</tr><tr>";
				$x.= "<td style='text-align:center;'>".$user1_name."</td>";
				$x.= "<td style='text-align:center;'>".$user2_name."</td>";
				$x.= "</tr></table>";
			}
			if($myTournament->get_counting()=='pointsOneSet')
			{
				if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
				if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
				$myHTML = new HTML($db);
				$_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
				$_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
				$x.= "<p/><button onclick=\"set_points_and_winner('pointsOneSet','".$data->game_location."',$('#".$data->game_location."_set1_p1').val(),$('#".$data->game_location."_set1_p2').val());\">Speichern</button>";
				$x.= "</td>";

				if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
				if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "</tr><tr>";
				$x.= "<td style='text-align:center;'>".$user1_name."</td>";
				$x.= "<td style='text-align:center;'>".$user2_name."</td>";
				$x.= "</tr></table>";
			}
			if($myTournament->get_counting()=='official2sets')
			{
				if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
				if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
				$myHTML = new HTML($db);
				$_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
				$_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
				$_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
				$_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
				$_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
				$_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,21,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p2',false);
				$x.= "<br/>";
				$x.= "<p/><button onclick=\"set_points_and_winner('official2sets','".$data->game_location."',
								$('#".$data->game_location."_set1_p1').val(),
								$('#".$data->game_location."_set1_p2').val(),
								$('#".$data->game_location."_set2_p1').val(),
								$('#".$data->game_location."_set2_p2').val(),
								$('#".$data->game_location."_set3_p1').val(),
								$('#".$data->game_location."_set3_p2').val()
								); \">Speichern</button>";
				$x.= "</td>";

				if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
				if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "</tr><tr>";
				$x.= "<td style='text-align:center;'>".$user1_name."</td>";
				$x.= "<td style='text-align:center;'>".$user2_name."</td>";
				$x.= "</tr></table>";
			}

			if($myTournament->get_counting()=='2sets11points')
			{
				if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
				if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
				$myHTML = new HTML($db);
				$_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
				$_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
				$_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
				$_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
				$_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
				$_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set1_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set1_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set2_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set2_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set3_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,11,1,2,3,4,5,6,7,8,9,10,11',$data->game_location.'_set3_p2',false);
				$x.= "<br/>";
				$x.= "<p/><button onclick=\"set_points_and_winner('2sets11points','".$data->game_location."',
								$('#".$data->game_location."_set1_p1').val(),
								$('#".$data->game_location."_set1_p2').val(),
								$('#".$data->game_location."_set2_p1').val(),
								$('#".$data->game_location."_set2_p2').val(),
								$('#".$data->game_location."_set3_p1').val(),
								$('#".$data->game_location."_set3_p2').val()
								); \">Speichern</button>";
				$x.= "</td>";

				if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
				if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "</tr><tr>";
				$x.= "<td style='text-align:center;'>".$user1_name."</td>";
				$x.= "<td style='text-align:center;'>".$user2_name."</td>";
				$x.= "</tr></table>";
			}

			if($myTournament->get_counting()=='2setswinning')
			{
				if(isset($u3)) { $pic_width='50px'; $user1_name = $u1->login.'/'.$u3->login; } else { $user1_name = $u1->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u1->get_pic_path()."'>";
				if(isset($u3)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u1->id,$data->game_location)'; src='".$u3->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "<td style='text-align:center;font-size:12pt;' rowspan='2'>";
				$myHTML = new HTML($db);
				$_POST[$data->game_location.'_set1_p1']=$data->game_set1_p1;
				$_POST[$data->game_location.'_set1_p2']=$data->game_set1_p2;
				$_POST[$data->game_location.'_set2_p1']=$data->game_set2_p1;
				$_POST[$data->game_location.'_set2_p2']=$data->game_set2_p2;
				$_POST[$data->game_location.'_set3_p1']=$data->game_set3_p1;
				$_POST[$data->game_location.'_set3_p2']=$data->game_set3_p2;
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set1_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set2_p2',false);
				$x.= "<br/>";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p1',false);
				$x.= ":";
				$x.= $myHTML->get_selection_with_array('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30',$data->game_location.'_set3_p2',false);
				$x.= "<br/>";
				$x.= "<p/><button onclick=\"set_points_and_winner('2setswinning','".$data->game_location."',
								$('#".$data->game_location."_set1_p1').val(),
								$('#".$data->game_location."_set1_p2').val(),
								$('#".$data->game_location."_set2_p1').val(),
								$('#".$data->game_location."_set2_p2').val(),
								$('#".$data->game_location."_set3_p1').val(),
								$('#".$data->game_location."_set3_p2').val()
								); \">Speichern</button>";
				$x.= "</td>";

				if(isset($u4)) { $pic_width='50px'; $user2_name = $u2->login.'/'.$u4->login; } else { $user2_name = $u2->login; $pic_width='100px'; }
				$x.= "<td style='text-align:center;'><img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u2->get_pic_path()."'>";
				if(isset($u4)) { $x.= "<img style='width:$pic_width;cursor:pointer;' onclick='set_winner($u2->id,$data->game_location)'; src='".$u4->get_pic_path()."'>"; }
				$x.= "</td>";

				$x.= "</tr><tr>";
				$x.= "<td style='text-align:center;'>".$user1_name."</td>";
				$x.= "<td style='text-align:center;'>".$user2_name."</td>";
				$x.= "</tr></table>";
			}


			$x.= "</div>";
			print $x;
		}

		if($_GET['ajax']=='add_user')
		{
			$db->insert(array('group2user_group_id'=>$_GET['tournament_id'],'group2user_user_id'=>$_GET['user_id']),'group2user');
			print $db->last_inserted_id;
		}

		if($_GET['ajax']=='remove_user')
		{
			$db->sql_query("DELETE FROM group2user WHERE group2user_group_id = '$_GET[tournament_id]' AND group2user_user_id='$_GET[user_id]'");
		}

		if($_GET['ajax']=='show_user_info')
		{
			$u1 = new user($_GET['user_id']);
			$db->sql_query("SELECT * FROM games WHERE game_group_id = '$_GET[tournament_id]' AND (game_player1_id='$_GET[user_id]' OR game_player2_id='$_GET[user_id]' OR game_player3_id='$_GET[user_id]' OR game_player4_id='$_GET[user_id]')");
			$x = "<div>";
			$x.= "<h1>Bisherige Spiele von ".$u1->login."</h1>";
			$x.= "<table style='width:100%;'>";
			while($data = $db->get_next_res())
			{
				$u1 = null; $u2 = null; $u3 = null; $u4 = null;
				$winner = null;
				$invert = null;

				if($data->game_player1_id==$_GET['user_id'] OR $data->game_player3_id==$_GET['user_id'])
				{
					$invert = true;
					$u1 = new user($data->game_player1_id);
					$u2 = new user($data->game_player2_id);
					if($data->game_player3_id>0)
					{
						$u3 = new user($data->game_player3_id);
						$u4 = new user($data->game_player4_id);
					}
				}

				if($data->game_player2_id==$_GET['user_id'] OR $data->game_player4_id==$_GET['user_id'])
				{
					$invert = false;
					$u2 = new user($data->game_player1_id);
					$u1 = new user($data->game_player2_id);
					if($data->game_player3_id>0)
					{
						$u3 = new user($data->game_player4_id);
						$u4 = new user($data->game_player3_id);
					}
				}

				$x.= "<tr>";
				$x.= "<td style='text-align:center;'><h2>Runde ".$data->game_round."</h2></td>";
				$x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u1->get_pic_path()."'><br/>".$u1->login."</td>";
				if(isset($u3)) { $x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u3->get_pic_path()."'><br/>".$u3->login."</td>"; }
				$x.= "<td style='text-align:center;'><h2>gegen</h2></td>";
				$x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u2->get_pic_path()."' onclick=\"show_user_games('".$u2->id."');\"><br/>".$u2->login."</td>";
				if(isset($u4)) { $x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u4->get_pic_path()."' onclick=\"show_user_games('".$u4->id."');\"><br/>".$u4->login."</td>"; }
				if($data->game_winner_id!='')
				{
					if($myTournament->get_counting()=='win')
					{
						if($data->game_winner_id==$_GET['user_id'] OR $data->game_winner2_id==$_GET['user_id'])
						{
							$x.= "<td style='text-align:center;'><h1 style='color:green;'>Gewonnen!</h1></td>";
						}
						else
						{
							$x.= "<td style='text-align:center;'><h1 style='color:red;'>Verloren!</h1></td>";
						}
					}
					else
					{
						$txt = "<span style='font-size:16pt;font-weight:bold;'>";
						if($invert)
						{
							if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p1.":".$data->game_set1_p2; }
							if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p1.":".$data->game_set2_p2; }
							if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p1.":".$data->game_set3_p2; }
						}
						else
						{
							if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p2.":".$data->game_set1_p1; }
							if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p2.":".$data->game_set2_p1; }
							if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p2.":".$data->game_set3_p1; }
						}
						$txt.= "</span>";

						if($data->game_winner_id==$_GET['user_id'] OR $data->game_winner2_id==$_GET['user_id'])
						{
							$x.= "<td style='text-align:center;'><h1 style='color:green;'>".$txt."</h1></td>";
						}
						else
						{
							$x.= "<td style='text-align:center;'><h1 style='color:red;'>".$txt."</h1></td>";
						}

					}
					if($data->game_duration>0)
					{
						$x.= "<td style='text-align:center;font-size:14pt;'>Spieldauer<br/>".gmdate("H:i:s", $data->game_duration)."</td>";
					}
				}
				else
				{
					$x.= "<td style='text-align:center;' colspan='2'><h2 style='font-style:italic;'>Noch nicht gespielt</h2></td>";
				}
				$x.= "</tr>";
				$x.= "<tr><td colspan='8'><hr/></td></tr>";
			}
			$x.= "</table>";
			$x.= "</div>";
			print $x;
		}

		//************************************************************************************
	}
}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

//************************************************************************************
//own PHP Functions
//************************************************************************************



function get_users($db,$category,$with_link=true,$group_by='alphabetical')
{
	$db2 = clone($db);
	if($category=='unassigned') { $x = "<h1>Spieler</h1>"; }
	if($category=='assigned') { $x = "<h1>Teilnehmer</h1>"; }
	$db->sql_query("SELECT * FROM users ORDER by user_account");
	while($data = $db->get_next_res())
	{
		$db2->sql_query("SELECT * FROM group2user WHERE group2user_group_id = '".$_GET['tournament_id']."' AND group2user_user_id='".$data->user_id."'");
		if($category=='assigned')
		{
			if($db2->count()>0)
			{
				$my_user = new user($data->user_id);
				$x.= $my_user->get_picture($with_link,'add_user');
				$my_user = null;
			}
		}
		if($category=='unassigned')
		{
			if($data->user_id!='1')
			{
				if($db2->count()==0)
				{
					$my_user = new user($data->user_id);
					$x.= $my_user->get_picture($with_link,'add_user');
					$my_user = null;
				}
			}
		}
	}
	return $x;
}

function get_teilnehmer($tournament_id)
{
	return null;
}

function get_games($tournament_id,$round)
{
	return null;
}

function get_all_tournaments($db)
{
	$db2 = clone($db);
	$w_str = "WHERE group_archived!='1'";
	//Check permissions
	$db->sql_query("SELECT * FROM location_permissions
												LEFT JOIN locations ON loc_permission_loc_id = location_id
												WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
	if($db->count()==0) { $w_str.= " AND user_id=0"; } else { $w_str.= " AND ("; }
	$i=0;
	while($d = $db->get_next_res())
	{
		if($i==0) { $w_str.= "group_created_by_location='$d->location_id'"; } else { $w_str.= " OR group_created_by_location='$d->location_id'"; }
		$i++;
	}
	if($db->count()>0) { $w_str.= ")"; }

	$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as c_date FROM groups $w_str ORDER BY group_created DESC");
	$x = "<table style='width:95%;'><tr><td colspan='2'><h1>Turniere</h1></td></tr>";
	while($data = $db->get_next_res())
	{
		$x.= "<tr><td><h3 style='margin-bottom:5px;'>".$data->group_title."</h3><span style='font-style:italic;'>".$data->c_date."</span></td>";
		$x.= "<td style='text-align:right;width:100px;'><button style='background-color:#EEE;margin-right:1px;border:1px solid #CCC;' onclick='show_infos($data->group_id)'><img src='".level."inc/imgs/query/edit.png' alt='Bearbeiten' /></button>";
		$x.= "<button style='background-color:#EEE;margin-right:1px;border:1px solid #CCC;' onclick='window.location = \"index.php?tournament_id=$data->group_id\"'><img src='".level."inc/imgs/query/next.png' alt='Laden' /></button>";
		$x.= "<button style='background-color:#EEE;margin:0px;border:1px solid #CCC;' onclick='delete_tournament(".$data->group_id.");'><img src='".level."inc/imgs/query/delete.png' alt='Löschen' /></button></td></tr>";
		$x.= "<tr><td colspan='2'><hr/></td></tr>";
		$my_user = null;
	}
	$x.="</table>";
	return $x;
}

function get_tournament_infos($db,$tournament_id)
{
	$data = $db->sql_query_with_fetch("SELECT * FROM groups WHERE group_id='$tournament_id'");
	$x = "<form id='new_tournament' action='index.php?action=modify_tournament' method='post'>";
	$x.= "<input type='hidden' name='tournament_id' value='$tournament_id'>";
	$x.= "<h1>Turnier anpassen</h1>";
	$x.= "	<table style='width:100%;'>";
	$x.= "	<tr><td style='width:150px;'>Turniername:</td><td><input id='tournament_title' name='tournament_title' style='width:80%;' type='text' value='$data->group_title'/></td></tr>";
	$x.= "	<tr><td style='width:150px;'>Spielsystem:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='tournament_system' name='tournament_system' style='width:80%;'"; if($data->group_status!='New') { $x.= " disabled"; } $x.=">";
  $x.= "	   <option value='Schoch' "; if($data->group_system=='Schoch') { $x.= " selected='1'"; } $x.= ">Schoch</option>";
  $x.= "	   <option value='Gruppenspiele' "; if($data->group_system=='Gruppenspiele') { $x.= " selected='1'"; } $x.= ">Gruppenspiele</option>";
  $x.= "	   <option value='Doppel_dynamisch' "; if($data->group_system=='Doppel_dynamisch') { $x.= " selected='1'"; } $x.= ">Doppel (dynamische Partner)</option>";
  $x.= "	   <option value='Doppel_fix' "; if($data->group_system=='Doppel_fix') { $x.= " selected='1'"; } $x.= ">Doppel (fixe Partner)</option>";
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td style='width:150px;'>Zählweise:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='tournament_counting' name='tournament_counting' style='width:80%;'";  if($data->group_status!='New') { $x.= " disabled"; } $x.=">";
  $x.= "	   <option value='win' "; if($data->group_counting=='win') { $x.= " selected='1'"; } $x.= ">Nur Sieg</option>";
  $x.= "	   <option value='pointsOneSet' "; if($data->group_counting=='pointsOneSet') { $x.= " selected='1'"; } $x.= ">Mit Punkten auf ein Satz</option>";
  $x.= "	   <option value='official2sets' "; if($data->group_counting=='official2sets') { $x.= " selected='1'"; } $x.= ">Offiziell (21 Punkte, 2 Gewinnsätze)</option>";
  $x.= "	   <option value='2sets11points' "; if($data->group_counting=='2sets11points') { $x.= " selected='1'"; } $x.= ">Verkürzt (11 Punkte, 2 Gewinnsätze, keine Verlängerung)</option>";
  $x.= "	   <option value='2setswinning' "; if($data->group_counting=='2setswinning') { $x.= " selected='1'"; } $x.= ">2 Gewinnsätze (Punkte frei)</option>";
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td style='vertical-align:top;'>Turnierbeschreibung:</td><td><textarea id='tournament_description' name='tournament_description' style='width:80%;height:100px;'>$data->group_description</textarea></td></tr>";
	$x.= "	<tr><td style='width:150px;'>Organisator:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='created_by_location' name='created_by_location' style='width:80%;'>";
  $db->sql_query("SELECT * FROM location_permissions
  								LEFT JOIN locations ON loc_permission_loc_id = location_id
  								WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
  								ORDER BY location_name");
  while($d = $db->get_next_res())
  {
	  $x.= "	   <option value='$d->location_id' "; if($data->group_created_by_location==$d->location_id) { $x.= " selected='1'"; } $x.= ">$d->location_name</option>";
  }
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td><button onclick='$(\"#new_tournament\").submit();'>Speichern</button></td></tr>";
	$x.= "</table>";
	$x.= "</form>";
	if($data->group_status=='Closed') { $x.= "<td><button style='background-color:orange;' onclick='window.location=\"index.php?action=reactivate_tournament&tournament_id=$tournament_id\";'>Reaktivieren</button></td>"; }
	return $x;
}


function get_new_tournament($db)
{
	$x = "<h1>Neues Turnier</h1>";
	$x.= "<form id='new_tournament' action='index.php?action=create_new_tournament' method='post'>";
	$x.= "	<table style='width:100%;'>";
	$x.= "	<tr><td style='width:150px;'>Turniername:</td><td><input id='tournament_title' name='tournament_title' style='width:80%;' type='text'/></td></tr>";
	$x.= "	<tr><td style='width:150px;'>Spielsystem:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='tournament_system' name='tournament_system' style='width:80%;'>";
  $x.= "	   <option value='Schoch'>Schoch</option>";
  $x.= "	   <option value='Gruppenspiele'>Gruppenspiele</option>";
  $x.= "	   <option value='Doppel_dynamisch'>Doppel (dynamische Partner)</option>";
  $x.= "	   <option value='Doppel_fix'>Doppel (fixe Partner)</option>";
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td style='width:150px;'>Zählweise:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='tournament_counting' name='tournament_counting' style='width:80%;'>";
  $x.= "	   <option value='win'>Nur Sieg</option>";
  $x.= "	   <option value='pointsOneSet'>Mit Punkten auf ein Satz </option>";
  $x.= "	   <option value='official2sets'>Offiziell (21 Punkte, 2 Gewinnsätze)</option>";
  $x.= "	   <option value='2sets11points'>Verkürzt (11 Punkte, 2 Gewinnsätze, keine Verlängerung)</option>";
  $x.= "	   <option value='2setswinning'>2 Gewinnsätze (Punkte frei)</option>";
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td style='vertical-align:top;'>Turnierbeschreibung:</td><td><textarea id='tournament_description' name='tournament_description' style='width:80%;height:100px;'></textarea></td></tr>";
	$x.= "	<tr><td style='width:150px;'>Organisator:</td>";
  $x.= "	<td>";
  $x.= "	 <select id='created_by_location' name='created_by_location' style='width:80%;'>";
  $db->sql_query("SELECT * FROM location_permissions
  								LEFT JOIN locations ON loc_permission_loc_id = location_id
  								WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
  								ORDER BY location_name");
  while($d = $db->get_next_res())
  {
	  $x.= "	   <option value='$d->location_id'>$d->location_name</option>";
  }
  $x.= "	 </select>";
  $x.= "	</td>";
  $x.= "	</tr>";
	$x.= "	<tr><td><button onclick='$(\"#new_tournament\").submit();'>Erstellen</button></td></tr>";
	$x.= "</table>";
	$x.= "</form>";
	return $x;
}


?>
